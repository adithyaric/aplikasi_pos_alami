<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OwnerStockMovement extends Model
{
    protected $fillable = [
        'owner_id',
        'product_id',
        'owner_stock_id',
        'stock_id',
        'user_id',
        'type',
        'reference_type',
        'reference_id',
        'qty_in',
        'qty_out',
        'balance',
        'notes',
    ];

    protected $casts = [
        'qty_in' => 'float',
        'qty_out' => 'float',
        'balance' => 'float',
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

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reference()
    {
        return $this->morphTo();
    }
}
