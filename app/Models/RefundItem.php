<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefundItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'refund_id',
        'product_id',
        'qty',
        'qty_input',
        'unit',
        'price',
        'subtotal',
        'stock_visibility',
        'source_owner_stock_id',
        'alasan',
    ];

    protected $casts = [
        'qty_input' => 'float',
        'price' => 'float',
        'subtotal' => 'float',
    ];

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sourceOwnerStock()
    {
        return $this->belongsTo(OwnerStock::class, 'source_owner_stock_id');
    }
}
