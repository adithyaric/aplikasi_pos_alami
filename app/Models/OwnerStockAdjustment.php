<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerStockAdjustment extends Model
{
    protected $fillable = [
        'owner_id',
        'product_id',
        'owner_stock_id',
        'adjustment_date',
        'system_qty',
        'physical_qty',
        'quantity',
        'reason',
        'keterangan',
        'status',
        'user_id',
    ];

    protected $casts = [
        'adjustment_date' => 'date',
        'system_qty' => 'float',
        'physical_qty' => 'float',
        'quantity' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(Outlet::class, 'owner_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ownerStock()
    {
        return $this->belongsTo(OwnerStock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
