<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type_code',
        'parent_id',
        'is_header',
        'is_active',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('code');
    }

    public function journalDetails(): HasMany
    {
        return $this->hasMany(JournalDetail::class);
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type_code, ['BANK', 'AREC', 'INTR', 'OCAS', 'FASS', 'OASS', 'EXPS', 'COGS'], true);
    }

    public function scopePosting($query)
    {
        return $query->where('is_header', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
