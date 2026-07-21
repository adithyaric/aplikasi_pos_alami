<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'sale_channel',
        'buyer_type',
        'buyer_id',
        'buyer_name',
        'customer_id',
        'outlet_id',
        'kasir_id',
        'user_id',
        'kas_id',
        'voucher_id',
        'salesman_id',
        'sale_date',
        'payment_type',
        'payment_status',
        'due_date',
        'notes',
        'discount',
        'total',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'sale_date' => 'date',
        'due_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id');
    }

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class);
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

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function paymentTransaction()
    {
        return $this->hasOne(PenjualanPayment::class);
    }

    public function items()
    {
        return $this->hasMany(PenjualanItem::class);
    }

    public function getFinalTotalAttribute()
    {
        return $this->total - $this->discount;
    }

    public function scopeWarehouseSales($query)
    {
        return $query->where('sale_channel', 'warehouse');
    }

    public function scopeRetailSales($query)
    {
        return $query->where(function ($builder) {
            $builder->whereNull('sale_channel')
                ->orWhere('sale_channel', 'retail');
        });
    }

    public function isWarehouseSale(): bool
    {
        return $this->sale_channel === 'warehouse';
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

        if ($this->isWarehouseSale()) {
            return $this->buyerEntity()?->name ?? '-';
        }

        return $this->customer?->name ?? '-';
    }

    public function getBuyerAddressAttribute(): ?string
    {
        if ($this->isWarehouseSale()) {
            return $this->buyerEntity()?->alamat;
        }

        return $this->customer?->alamat;
    }

    public function getBuyerPhoneAttribute(): ?string
    {
        if ($this->isWarehouseSale()) {
            return $this->buyerEntity()?->no_telp;
        }

        return $this->customer?->no_telp;
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
}
