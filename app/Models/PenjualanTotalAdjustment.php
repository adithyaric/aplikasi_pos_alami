<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanTotalAdjustment extends Model
{
    protected $fillable = [
        'penjualan_id',
        'refund_id',
        'type',
        'amount',
        'total_before',
        'total_after',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'total_before' => 'float',
        'total_after' => 'float',
    ];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class);
    }

    public function refund()
    {
        return $this->belongsTo(Refund::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
