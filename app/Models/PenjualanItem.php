<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'penjualan_id',
        'product_id',
        'stock_id',
        'qty',
        'qty_input',
        'unit',
        'price',
        'subtotal',
        'serial_number',
    ];

    protected $casts = [
        'qty_input' => 'decimal:2',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function allocations()
    {
        return $this->hasMany(PenjualanItemAllocation::class);
    }
}
