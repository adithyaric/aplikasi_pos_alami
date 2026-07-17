<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salesman extends Model
{
    use SoftDeletes;

    protected $table = 'salesmans';
    protected $fillable = [
        'code',
        'name',
        'alamat',
        'no_telp',
        'outlet_id',
        'user_id',
    ];

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
