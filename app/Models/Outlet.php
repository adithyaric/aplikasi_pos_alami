<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Outlet extends Model
{
    use SoftDeletes;

    public const TYPE_OPTIONS = [
        'branch' => 'Cabang / Branch',
        'toko' => 'Toko',
        'beauty' => 'Beauty',
    ];

    protected $fillable = [
        'logo',
        'name',
        'jenis_outlet',
        'alamat',
        'npwp',
        'slogan',
        'desc',
        'footer',
    ];

    public function penjualan()
    {
        return $this->hasMany(Penjualan::class);
    }

    public function pembelian()
    {
        return $this->hasMany(Pembelian::class);
    }

    public function salesmen()
    {
        return $this->hasMany(Salesman::class);
    }

    public function ownerStocks()
    {
        return $this->hasMany(OwnerStock::class, 'owner_id');
    }

    public function ownerStockMovements()
    {
        return $this->hasMany(OwnerStockMovement::class, 'owner_id');
    }

    public function scopeBranches($query)
    {
        return $query->where('jenis_outlet', 'branch');
    }

    public function scopeShops($query)
    {
        return $query->where('jenis_outlet', 'toko');
    }

    public static function typeOptions(?string $currentType = null): array
    {
        $options = self::TYPE_OPTIONS;

        if ($currentType && ! array_key_exists($currentType, $options)) {
            $options = [
                $currentType => ucwords(str_replace(['-', '_'], ' ', $currentType)),
            ] + $options;
        }

        return $options;
    }

    public function getJenisOutletLabelAttribute(): string
    {
        $options = self::typeOptions($this->jenis_outlet);

        return $options[$this->jenis_outlet] ?? ucwords(str_replace(['-', '_'], ' ', (string) $this->jenis_outlet));
    }
}
