<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PenjualanPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'penjualan_id',
        'payment_date',
        'payment_method',
        'payment_reference',
        'payment_history',
        'status',
        'amount',
        'bukti_transfer',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'payment_history' => 'array',
        'amount' => 'float',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }
}
