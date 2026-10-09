<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembelian extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'offline_client_id',
        'customer_po',
        'code_gr',
        'outlet_id',
        'supplier_id',
        'kas_id',
        'total',
        'discount_percent',
        'tax_percent',
        'shipping_cost',
        'is_published',
        'owner_approval_status',
        'owner_approved_by',
        'owner_approved_at',
        'owner_approval_note',
        'receipt_date',
        'receipt_pic',
        'receipt_status',
        'receipt_photo',
    ];

    protected $casts = [
        'receipt_date' => 'datetime',
        'owner_approved_at' => 'datetime',
        'discount_percent' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'shipping_cost' => 'integer',
    ];

    public const OWNER_APPROVAL_STATUSES = [
        'pending' => 'Menunggu ACC Owner',
        'approved' => 'Disetujui Owner',
        'rejected' => 'Ditolak Owner',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class)->withTrashed();
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function customerPo()
    {
        return $this->hasOne(CustomerPo::class, 'name', 'customer_po')->withTrashed();
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    // Market stocks (after published)
    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    // Warehouse stocks (before published)
    public function stockPembelians()
    {
        return $this->hasMany(StockPembelian::class);
    }

    public function pembelianProducts()
    {
        return $this->hasMany(PembelianProduct::class);
    }

    public function recalculateTotal(): void
    {
        $subtotal = (int) $this->pembelianProducts()->sum('subtotal');
        $this->update([
            'total' => (int) round(
                $subtotal * (1 - (float) $this->discount_percent / 100 + (float) $this->tax_percent / 100)
                + (int) $this->shipping_cost
            ),
        ]);
    }

    public function pembelianTransaction()
    {
        return $this->hasOne(PembelianTransaction::class);
    }

    public function ownerApprovedBy()
    {
        return $this->belongsTo(User::class, 'owner_approved_by')->withTrashed();
    }

    public function getOwnerApprovalLabelAttribute(): string
    {
        return self::OWNER_APPROVAL_STATUSES[$this->owner_approval_status] ?? ucfirst((string) $this->owner_approval_status);
    }

    public function isOwnerApproved(): bool
    {
        return $this->owner_approval_status === 'approved';
    }

    public function canBeEditedBy(?User $user): bool
    {
        return (bool) $user;
    }
}
