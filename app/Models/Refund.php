<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'code',
        'kas_id',
        'customer_id',
        'penjualan_id',
        'applied_penjualan_id',
        'outlet_id',
        'source_outlet_id',
        'return_scope',
        'sale_channel',
        'buyer_type',
        'buyer_id',
        'buyer_name',
        'salesman_id',
        'user_id',
        'tanggal',
        'total',
        'invoice_total_before',
        'invoice_total_after',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'approval_note',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id')->withTrashed();
    }

    public function kas()
    {
        return $this->belongsTo(Kas::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class)->withTrashed();
    }

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class)->withTrashed();
    }

    public function appliedPenjualan()
    {
        return $this->belongsTo(Penjualan::class, 'applied_penjualan_id')->withTrashed();
    }

    public function refundItems()
    {
        return $this->hasMany(RefundItem::class);
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'buyer_id');
    }

    public function canvasBuyer()
    {
        return $this->belongsTo(Canvas::class, 'buyer_id');
    }

    public function outletBuyer()
    {
        return $this->belongsTo(Outlet::class, 'buyer_id');
    }

    public function tokoBuyer()
    {
        return $this->belongsTo(Outlet::class, 'buyer_id');
    }

    public function sourceOutlet()
    {
        return $this->belongsTo(Outlet::class, 'source_outlet_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Salesman::class);
    }

    public function totalAdjustment()
    {
        return $this->hasOne(PenjualanTotalAdjustment::class);
    }

    public function buyerEntity()
    {
        return match ($this->buyer_type) {
            'agent' => $this->relationLoaded('agent') ? $this->agent : $this->agent()->first(),
            'canvas' => $this->relationLoaded('canvasBuyer') ? $this->canvasBuyer : $this->canvasBuyer()->first(),
            'outlet' => $this->relationLoaded('outletBuyer') ? $this->outletBuyer : $this->outletBuyer()->first(),
            'toko' => $this->relationLoaded('tokoBuyer') ? $this->tokoBuyer : $this->tokoBuyer()->first(),
            default => null,
        };
    }

    public function getBuyerDisplayNameAttribute(): string
    {
        if ($this->buyer_name) {
            return $this->buyer_name;
        }

        if ($this->buyer_type) {
            return $this->buyerEntity()?->name ?? '-';
        }

        return $this->customer?->name ?? '-';
    }

    public function getBuyerTypeLabelAttribute(): string
    {
        return match ($this->buyer_type) {
            'agent' => 'Agen',
            'canvas' => 'Canvas',
            'outlet' => 'Cabang',
            'toko' => 'Customer/Toko',
            default => $this->customer_id ? 'Customer' : '-',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Superadmin',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Approved',
        };
    }

    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function hasAppliedEffects(): bool
    {
        return ! in_array($this->status, [self::STATUS_PENDING, self::STATUS_REJECTED], true);
    }

    protected $casts = [
        'tanggal' => 'datetime',
        'invoice_total_before' => 'float',
        'invoice_total_after' => 'float',
        'total' => 'float',
        'approved_at' => 'datetime',
    ];
}
