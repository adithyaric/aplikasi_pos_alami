<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanPhoto extends Model
{
    protected $fillable = [
        'penjualan_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }
}
