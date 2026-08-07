<?php

namespace App\Support;

use App\Models\Product;

class ProductUnitConverter
{
    public function normalize(Product $product, int|float|string $qty, ?string $unit = null): int
    {
        $factor = $this->factorForUnit($product, $unit);
        $amount = $this->parseNumeric($qty);

        return (int) round($amount * $factor);
    }

    public function factorForUnit(Product $product, ?string $unit = null): int
    {
        $unitKey = $this->normalizeUnitName($unit ?: $product->satuan);
        $factors = $this->unitMultipliers($product);

        return $factors[$unitKey] ?? 1;
    }

    public function unitMultipliers(Product $product): array
    {
        $baseUnit = $product->satuan ?: 'PCS';
        $factors = [
            $this->normalizeUnitName($baseUnit) => 1,
        ];

        if ($product->satuan_besar && $product->konversi_qty) {
            $factors[$this->normalizeUnitName($product->satuan_besar)] = (int) round((float) $product->konversi_qty);
        }

        if ($product->satuan_terbesar && $product->konversi_qty && $product->konversi_qty_terbesar) {
            $factors[$this->normalizeUnitName($product->satuan_terbesar)] = (int) round((float) $product->konversi_qty * (float) $product->konversi_qty_terbesar);
        }

        return $factors;
    }

    public function defaultInputUnit(Product $product, string $channel = 'warehouse'): string
    {
        $units = $this->inputUnits($product, $channel);

        return $units[0]['value'] ?? ($product->satuan ?: 'PCS');
    }

    public function inputUnits(Product $product, string $channel = 'warehouse'): array
    {
        $baseUnit = $product->satuan ?: 'PCS';
        $orderedUnits = match ($channel) {
            'warehouse', 'supplier', 'distribution' => [
                $product->satuan_besar,
                $product->satuan_terbesar,
                $baseUnit,
            ],
            'branch', 'sales', 'customer' => [$baseUnit],
            default => [
                $baseUnit,
                $product->satuan_besar,
                $product->satuan_terbesar,
            ],
        };

        $options = [];
        $seen = [];

        foreach ($orderedUnits as $unit) {
            if (! $unit) {
                continue;
            }

            $key = $this->normalizeUnitName($unit);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $options[] = [
                'value' => $unit,
                'label' => $this->inputUnitLabel($product, $unit),
            ];
        }

        return $options;
    }

    public function inputUnitLabel(Product $product, ?string $unit): string
    {
        $resolvedUnit = $unit ?: ($product->satuan ?: 'PCS');
        $baseUnit = $product->satuan ?: 'PCS';
        $factor = $this->factorForUnit($product, $resolvedUnit);

        if ($factor <= 1 || $this->normalizeUnitName($resolvedUnit) === $this->normalizeUnitName($baseUnit)) {
            return $resolvedUnit;
        }

        return sprintf('%s (1 = %s %s)', $resolvedUnit, number_format($factor, 0, ',', '.'), $baseUnit);
    }

    public function display(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $baseUnit = $product->satuan ?: 'PCS';
        $bigUnit = $product->satuan_besar;
        $largestUnit = $product->satuan_terbesar;
        $bigFactor = $product->konversi_qty ? (int) round((float) $product->konversi_qty) : null;
        $largestFactor = ($bigFactor && $product->konversi_qty_terbesar)
            ? $bigFactor * (int) round((float) $product->konversi_qty_terbesar)
            : null;

        if (! $bigFactor || ! $bigUnit) {
            return $this->formatPart($qty, $baseUnit);
        }

        $remaining = $qty;
        $parts = [];

        if ($largestFactor && $largestUnit) {
            $largestQty = intdiv($remaining, $largestFactor);
            if ($largestQty > 0) {
                $parts[] = $this->formatPart($largestQty, $largestUnit);
                $remaining %= $largestFactor;
            }
        }

        $bigQty = intdiv($remaining, $bigFactor);
        if ($bigQty > 0) {
            $parts[] = $this->formatPart($bigQty, $bigUnit);
            $remaining %= $bigFactor;
        }

        if ($remaining > 0 || $parts === []) {
            $parts[] = $this->formatPart($remaining, $baseUnit);
        }

        return implode(' ', $parts);
    }

    /**
     * Keep the total base quantity and calculate equivalent larger-unit quantities.
     *
     * For example, with 10 Pack per Slop and 25 Slop per Ball,
     * 265 Pack becomes 265 Pack, 26 Slop, and 1 Ball.
     */
    public function breakdown(Product $product, int|float $qty): array
    {
        $total = (int) round($qty);
        $bigFactor = $product->konversi_qty
            ? (int) round((float) $product->konversi_qty)
            : null;
        $largestFactor = ($bigFactor && $product->konversi_qty_terbesar)
            ? $bigFactor * (int) round((float) $product->konversi_qty_terbesar)
            : null;

        $qtyTerbesar = 0;
        if ($largestFactor && $product->satuan_terbesar) {
            $qtyTerbesar = intdiv($total, $largestFactor);
        }

        $qtyBesar = 0;
        if ($bigFactor && $product->satuan_besar) {
            $qtyBesar = intdiv($total, $bigFactor);
        }

        return [
            'qty' => $total,
            'qty_besar' => $qtyBesar,
            'qty_terbesar' => $qtyTerbesar,
            'qty_total' => $total,
        ];
    }

    public function detailedDisplay(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $base = $this->formatPart($qty, $product->satuan ?: 'PCS');
        $converted = $this->display($product, $qty);

        return $converted === $base ? $base : "{$base} | {$converted}";
    }

    public function stockSummaryDisplay(Product $product, int|float $qty): string
    {
        $qty = (int) round($qty);
        $baseUnit = $product->satuan ?: 'PCS';
        $bigUnit = $product->satuan_besar;
        $largestUnit = $product->satuan_terbesar;
        $bigFactor = $product->konversi_qty ? (int) round((float) $product->konversi_qty) : null;
        $largestFactor = ($bigFactor && $product->konversi_qty_terbesar)
            ? $bigFactor * (int) round((float) $product->konversi_qty_terbesar)
            : null;

        $parts = [
            $this->formatPart($qty, $baseUnit),
        ];

        if ($bigFactor && $bigUnit) {
            $parts[] = $this->formatFractionalPart($qty / $bigFactor, $bigUnit);
        }

        if ($largestFactor && $largestUnit) {
            $parts[] = $this->formatFractionalPart($qty / $largestFactor, $largestUnit);
        }

        return implode(' | ', array_filter($parts));
    }

    protected function parseNumeric(int|float|string $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $normalized = trim($value);
        $normalized = str_replace([' ', ','], ['', '.'], $normalized);

        return (float) $normalized;
    }

    protected function normalizeUnitName(?string $unit): string
    {
        return mb_strtolower(trim((string) $unit));
    }

    protected function formatPart(int $qty, string $unit): string
    {
        return number_format($qty, 0, ',', '.').' '.$unit;
    }

    protected function formatFractionalPart(float $qty, string $unit): string
    {
        if ((float) (int) $qty === $qty) {
            return number_format($qty, 0, ',', '.').' '.$unit;
        }

        return rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',').' '.$unit;
    }
}
