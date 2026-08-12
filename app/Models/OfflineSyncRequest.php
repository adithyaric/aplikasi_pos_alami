<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfflineSyncRequest extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'method',
        'path',
        'response_status',
        'response_payload',
        'response_location',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];
}
