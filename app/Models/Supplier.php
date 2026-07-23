<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use SoftDeletes;

    public const DEFAULT_PO_NUMBER_FORMAT = 'PO-{SUPPLIER_CODE}-{YYYY}{MM}-{SEQ}';

    protected $fillable = [
        'name',
        'kode_supplier',
        'alamat',
        'no_telp',
        'deadline_days',
        'deadline_interval_weeks',
        'deadline_reference_date',
        'po_number_prefix',
        'po_number_padding',
    ];

    protected $casts = [
        'deadline_days'              => 'array',
        'deadline_reference_date'    => 'date',
        'deadline_interval_weeks'    => 'integer',
        'po_number_padding'          => 'integer',
    ];

    public static function generateNextKode(): string
    {
        $last = static::withTrashed()
            ->where('kode_supplier', 'like', 'S%')
            ->orderByRaw('CAST(SUBSTRING(kode_supplier, 2) AS UNSIGNED) DESC')
            ->value('kode_supplier');

        $next = $last ? ((int) substr($last, 1)) + 1 : 1;

        return 'S'.str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier');
    }

    public function pembelians()
    {
        return $this->hasMany(Pembelian::class);
    }

    public function poNumberPrefix(): string
    {
        return $this->poNumberFormat();
    }

    public function poNumberFormat(): string
    {
        return $this->po_number_prefix ?: self::DEFAULT_PO_NUMBER_FORMAT;
    }

    public function generateNextPoCode(?Carbon $date = null): string
    {
        $date = $date ?: now();
        $padding = max(3, (int) ($this->po_number_padding ?: 5));
        $format = $this->poNumberFormat();
        $tokenValues = $this->poNumberTokenValues($date);

        if (! str_contains($format, '{SEQ}')) {
            $prefix = strtr($format, $tokenValues);

            $lastCode = Pembelian::where('supplier_id', $this->id)
                ->where('code', 'like', $prefix.'%')
                ->latest('id')
                ->value('code');

            $nextNumber = 1;

            if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return $prefix.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
        }

        $likePattern = strtr($format, array_merge($tokenValues, [
            '{SEQ}' => '%',
        ]));
        $regex = '/^'.str_replace(
            '\{SEQ\}',
            '(\d+)',
            preg_quote(strtr($format, $tokenValues), '/')
        ).'$/';
        $codes = Pembelian::where('supplier_id', $this->id)
            ->where('code', 'like', $likePattern)
            ->pluck('code');

        $nextNumber = 1;

        foreach ($codes as $code) {
            if (preg_match($regex, (string) $code, $matches)) {
                $nextNumber = max($nextNumber, ((int) $matches[1]) + 1);
            }
        }

        return strtr($format, array_merge($tokenValues, [
            '{SEQ}' => str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT),
        ]));
    }

    public function previewPoCode(?Carbon $date = null, ?string $supplierCode = null, int $sequence = 1): string
    {
        $date = $date ?: now();
        $padding = max(3, (int) ($this->po_number_padding ?: 5));
        $format = $this->poNumberFormat();
        $rendered = strtr($format, $this->poNumberTokenValues(
            $date,
            $supplierCode,
            str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT),
        ));

        if (! str_contains($format, '{SEQ}')) {
            return $rendered.str_pad((string) $sequence, $padding, '0', STR_PAD_LEFT);
        }

        return $rendered;
    }

    public function poNumberBuilderConfig(): array
    {
        $format = $this->poNumberFormat();
        $separator = $this->detectPoBuilderSeparator($format);
        $parts = $separator ? array_values(array_filter(explode($separator, $format), fn ($part) => $part !== '')) : [];
        $defaults = self::defaultPoNumberBuilderConfig();

        if (empty($parts)) {
            return array_merge($defaults, [
                'custom_format' => $format,
                'show_advanced' => true,
            ]);
        }

        $sequencePosition = '{SEQ}' === ($parts[0] ?? null) ? 'prefix' : 'suffix';

        if ('prefix' === $sequencePosition) {
            array_shift($parts);
        } elseif ('{SEQ}' === end($parts)) {
            array_pop($parts);
        }

        $prefixText = '';
        if (! empty($parts) && ! $this->isPoBuilderTokenPart($parts[0])) {
            $prefixText = array_shift($parts);
        }

        $includeSupplierCode = false;
        if (! empty($parts) && '{SUPPLIER_CODE}' === ($parts[0] ?? null)) {
            $includeSupplierCode = true;
            array_shift($parts);
        }

        $dateFormat = 'none';
        if (! empty($parts)) {
            $dateFormat = $this->poBuilderDateFormatKey((string) $parts[0]) ?? 'none';
            if ('none' !== $dateFormat || '{SUPPLIER_CODE}' !== ($parts[0] ?? null)) {
                array_shift($parts);
            }
        }

        $isSupported = '' !== $separator
            && empty($parts)
            && in_array($separator, ['-', '/', '.'], true);

        return [
            'prefix_text' => $isSupported ? $prefixText : $defaults['prefix_text'],
            'separator' => $separator ?: $defaults['separator'],
            'include_supplier_code' => $isSupported ? $includeSupplierCode : $defaults['include_supplier_code'],
            'date_format' => $isSupported ? $dateFormat : $defaults['date_format'],
            'sequence_position' => $isSupported ? $sequencePosition : $defaults['sequence_position'],
            'custom_format' => $format,
            'show_advanced' => ! $isSupported,
        ];
    }

    public static function defaultPoNumberBuilderConfig(): array
    {
        return [
            'prefix_text' => 'PO',
            'separator' => '-',
            'include_supplier_code' => true,
            'date_format' => 'yyyy_mm',
            'sequence_position' => 'suffix',
            'custom_format' => self::DEFAULT_PO_NUMBER_FORMAT,
            'show_advanced' => false,
        ];
    }

    /**
     * True if a pembelian already exists within the current ordering interval window.
     * Pass the pre-computed next deadline to avoid a redundant nextDeadlineDate() call.
     */
    public function hasPembelianInCurrentInterval(Carbon $nextDeadline): bool
    {
        $intervalStart = $nextDeadline->copy()->subWeeks($this->deadline_interval_weeks);

        if ($this->relationLoaded('pembelians')) {
            return $this->pembelians->contains(fn ($p) => $p->created_at >= $intervalStart);
        }

        return Pembelian::where('supplier_id', $this->id)
            ->where('created_at', '>=', $intervalStart)
            ->exists();
    }

    /**
     * Returns the next deadline date >= today, or null if no deadline configured.
     *
     * deadline_days: ISO weekday array e.g. [1] = Monday, [1,4] = Mon+Thu (1=Mon,7=Sun)
     * deadline_interval_weeks: 1/2/3
     * deadline_reference_date: anchor week (any past Monday works)
     */
    public function nextDeadlineDate(): ?Carbon
    {
        if (empty($this->deadline_days) || ! $this->deadline_interval_weeks) {
            return null;
        }

        $days     = array_map('intval', $this->deadline_days);
        $interval = (int) $this->deadline_interval_weeks;
        $ref      = Carbon::parse($this->deadline_reference_date ?? $this->created_at)->startOfWeek();
        $today    = Carbon::today();
        $limit    = $today->copy()->addDays($interval * 7 + 14);

        for ($d = $today->copy(); $d->lte($limit); $d->addDay()) {
            if (! in_array($d->dayOfWeekIso, $days)) {
                continue;
            }
            $weeksSinceRef = (int) abs($ref->diffInWeeks($d->copy()->startOfWeek()));
            if ($weeksSinceRef % $interval === 0) {
                return $d->copy();
            }
        }

        return null;
    }

    /** True if next deadline is within 3 days (but not past). */
    public function isDeadlineUrgent(): bool
    {
        $next = $this->nextDeadlineDate();
        if (! $next) {
            return false;
        }
        // nextDeadlineDate() always returns today or later, so daysUntil is always >= 0
        return Carbon::today()->diffInDays($next, false) <= 3;
    }

    private function poNumberTokenValues(?Carbon $date = null, ?string $supplierCode = null, ?string $sequence = null): array
    {
        $date = $date ?: now();
        $values = [
            '{SUPPLIER_CODE}' => Str::upper((string) ($supplierCode ?: ($this->kode_supplier ?: 'SUP'))),
            '{YYYY}' => $date->format('Y'),
            '{YY}' => $date->format('y'),
            '{MM}' => $date->format('m'),
            '{ROMAN_MM}' => $this->romanMonth($date->month),
            '{DD}' => $date->format('d'),
        ];

        if (null !== $sequence) {
            $values['{SEQ}'] = $sequence;
        }

        return $values;
    }

    private function romanMonth(int $month): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$month] ?? '';
    }

    private function detectPoBuilderSeparator(string $format): string
    {
        foreach (['-', '/', '.'] as $separator) {
            if (str_contains($format, $separator)) {
                return $separator;
            }
        }

        return '';
    }

    private function isPoBuilderTokenPart(string $part): bool
    {
        return '{SUPPLIER_CODE}' === $part
            || '{SEQ}' === $part
            || null !== $this->poBuilderDateFormatKey($part);
    }

    private function poBuilderDateFormatKey(string $part): ?string
    {
        return match ($part) {
            '{YYYY}{MM}' => 'yyyy_mm',
            '{YY}{MM}' => 'yy_mm',
            '{YYYY}{ROMAN_MM}' => 'yyyy_roman',
            '{YY}{ROMAN_MM}' => 'yy_roman',
            '{YYYY}' => 'yyyy',
            '{YY}' => 'yy',
            '{MM}' => 'mm',
            '{ROMAN_MM}' => 'roman',
            default => null,
        };
    }
}
