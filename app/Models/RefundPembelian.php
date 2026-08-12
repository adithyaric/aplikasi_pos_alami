<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class RefundPembelian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'tanggal',
        'type',             // gudang_ke_supplier | outlet_ke_gudang
        'return_mode',      // replacement | cash_refund
        'status',           // retur | complete
        'kas_id',
        'supplier_id',
        'delivery_order_id', // [NEW] for outlet_ke_gudang, replaces pembelian_id
        'outlet_id',
        'user_id',
        'total',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class)->withTrashed();
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    public function refundPembelianItems()
    {
        return $this->hasMany(RefundPembelianItem::class);
    }

    public function isReplacement(): bool
    {
        return $this->return_mode === 'replacement';
    }

    public function groupedRefundPembelianItems(): Collection
    {
        return self::groupItems($this->refundPembelianItems->loadMissing(['product', 'stock']));
    }

    public static function groupItems(Collection $items): Collection
    {
        return $items
            ->groupBy(function ($item) {
                return implode('|', [
                    (string) $item->product_id,
                    (string) ($item->harga ?? 0),
                    trim((string) ($item->alasan ?? '')),
                    (string) ($item->resolution ?? ''),
                ]);
            })
            ->map(function (Collection $group) {
                $first = $group->first();
                $expiredDates = $group->pluck('stock.expired_at')
                    ->filter()
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values();

                return (object) [
                    'product' => $first->product,
                    'stock' => $first->stock,
                    'product_id' => $first->product_id,
                    'qty' => (int) $group->sum('qty'),
                    'harga' => $first->harga,
                    'subtotal' => (int) $group->sum(fn ($item) => ((int) $item->qty) * ((int) ($item->harga ?? 0))),
                    'alasan' => $first->alasan,
                    'resolution' => $first->resolution,
                    'item_ids' => $group->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                    'expired_at' => $expiredDates->count() === 1 ? $expiredDates->first() : null,
                    'retur' => $first->retur ?? null,
                ];
            })
            ->values();
    }
}
