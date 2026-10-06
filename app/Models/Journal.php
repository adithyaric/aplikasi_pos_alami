<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Journal extends Model
{
    protected $fillable = [
        'journal_number',
        'transaction_date',
        'ref_type',
        'ref_id',
        'source_key',
        'description',
        'is_manual',
        'outlet_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'is_manual' => 'boolean',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(JournalDetail::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class)->withTrashed();
    }

    public function scopeExcludeReturns(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->whereNull('ref_type')
                ->orWhere(function (Builder $nested) {
                    $nested->where('ref_type', 'not like', '%RETUR%')
                        ->where('ref_type', 'not like', '%RETURN%')
                        ->where('ref_type', 'not like', '%REFUND%');
                });
        });
    }

    public function scopeForLocation(Builder $query, string $location): Builder
    {
        if ($location === 'pusat') {
            return $query->whereNull('outlet_id');
        }

        return $location === 'all' ? $query : $query->where('outlet_id', (int) $location);
    }

    public function totalDebit(): float
    {
        return (float) $this->details->sum('debit');
    }

    public function totalCredit(): float
    {
        return (float) $this->details->sum('credit');
    }
}
