<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'kas_id',
        'customer_id',
        'penjualan_id',
        'outlet_id',
        'buyer_type',
        'buyer_id',
        'buyer_name',
        'user_id',
        'tanggal',
        'total',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function refundItems()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'buyer_id');
    }

    public function canvasBuyer()
    {
        return $this->belongsTo(Canvas::class, 'buyer_id');
    }

    public function outletBuyer()
    {
        return $this->belongsTo(Outlet::class, 'buyer_id');
    }

    public function buyerEntity()
    {
        return match ($this->buyer_type) {
            'agent' => $this->relationLoaded('agent') ? $this->agent : $this->agent()->first(),
            'canvas' => $this->relationLoaded('canvasBuyer') ? $this->canvasBuyer : $this->canvasBuyer()->first(),
            'outlet' => $this->relationLoaded('outletBuyer') ? $this->outletBuyer : $this->outletBuyer()->first(),
            default => null,
        };
    }

    public function getBuyerDisplayNameAttribute(): string
    {
        if ($this->buyer_name) {
            return $this->buyer_name;
        }

        if ($this->buyer_type) {
            return $this->buyerEntity()?->name ?? '-';
        }

        return $this->customer?->name ?? '-';
    }

    public function getBuyerTypeLabelAttribute(): string
    {
        return match ($this->buyer_type) {
            'agent' => 'Agen',
            'canvas' => 'Canvas',
            'outlet' => 'Cabang',
            default => $this->customer_id ? 'Customer' : '-',
        };
    }

    protected $casts = [
        'tanggal' => 'datetime',
    ];
}
