<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanItemAllocation extends Model
{
    protected $fillable = [
        'penjualan_item_id',
        'stock_id',
        'qty',
    ];

    public function penjualanItem()
    {
        return $this->belongsTo(PenjualanItem::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
