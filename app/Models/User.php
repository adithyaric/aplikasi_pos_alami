<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public const ROLE_LABELS = [
        'superadmin' => 'Superadmin',
        'po' => 'User PO',
        'finance' => 'User Finance',
        'leader-cabang' => 'User Leader Cabang',
        'sales' => 'User Sales',
        // Legacy slugs remain readable so existing client accounts keep working.
        'admin-gudang' => 'Admin Gudang',
        'admin-cabang' => 'Admin Cabang',
        'owner' => 'Owner',
        'staff-outlet' => 'Staff Outlet',
    ];

    private const ROLE_ALIASES = [
        'admin-gudang' => 'po',
        'owner' => 'finance',
        'admin-cabang' => 'leader-cabang',
        'staff-outlet' => 'leader-cabang',
    ];

    private const PERMISSIONS = [
        'po' => [
            'category.manage', 'product.manage', 'supplier.manage', 'customer-po.manage',
            'pembelian.po', 'pembelian.receive', 'pembelian.payment', 'pembelian.approval',
            'stock.manage', 'branch-stock.manage', 'penjualan.warehouse', 'penjualan.branch',
            'refund.purchase', 'refund.sales', 'reports.all', 'affiliate.manage',
        ],
        'finance' => [
            'pembelian.po', 'stock.view', 'stock.manage', 'branch-stock.view', 'branch-stock.manage', 'penjualan.warehouse',
            'penjualan.branch', 'refund.purchase', 'refund.sales', 'reports.all',
        ],
        'leader-cabang' => [
            'branch-stock.view', 'branch-stock.manage', 'penjualan.branch', 'refund.sales', 'reports.branch',
            'customer-penjualan.manage',
        ],
        'sales' => [
            'branch-stock.view', 'branch-stock.manage', 'penjualan.branch', 'refund.sales', 'reports.branch',
            'customer-penjualan.manage',
        ],
        // Legacy permissions intentionally preserve the current application behavior.
        'legacy-admin-gudang' => ['*'],
        'legacy-owner' => ['*'],
        'legacy-admin-cabang' => [
            'branch-stock.view', 'branch-stock.manage', 'penjualan.branch', 'refund.sales', 'reports.branch',
            'customer-penjualan.manage',
        ],
        'legacy-staff-outlet' => ['*'],
        'superadmin' => ['*'],
    ];

    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'role',
        'status',
        'email',
        'alamat',
        'no_telp',
        'password',
        'limit_discount',
        'outlet_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // public function cart()
    // {
    //     return $this->belongsToMany(Product::class, 'user_cart')->withPivot('qty', 'serial_number', 'stock_id');
    // }

    // public function wishlist()
    // {
    //     return $this->belongsToMany(Product::class, 'user_wishlist')->withPivot('qty', 'name', 'customer_id', 'outlet_id');
    // }

    // public function reviews()
    // {
    //     return $this->hasMany(Review::class);
    // }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function branchId(): ?int
    {
        return $this->outlet_id ? (int) $this->outlet_id : null;
    }

    public function isBranchScoped(): bool
    {
        return in_array($this->role, ['admin-cabang', 'leader-cabang', 'sales', 'staff-outlet'], true)
            && $this->branchId() !== null;
    }

    public function isWarehouseRole(): bool
    {
        return in_array($this->role, ['superadmin', 'po', 'finance', 'admin-gudang', 'owner'], true);
    }

    public function canonicalRole(): string
    {
        return self::ROLE_ALIASES[$this->role] ?? $this->role;
    }

    public function roleLabel(): string
    {
        return self::ROLE_LABELS[$this->role] ?? ucfirst(str_replace(['-', '_'], ' ', (string) $this->role));
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        $permissionSet = self::PERMISSIONS[$this->permissionRole()] ?? [];

        return in_array('*', $permissionSet, true)
            || in_array($permission, $permissionSet, true)
            || collect($permissionSet)->contains(function (string $granted) use ($permission) {
                return str_ends_with($granted, '.*')
                    && str_starts_with($permission, substr($granted, 0, -1));
            });
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return collect($permissions)->contains(fn (string $permission) => $this->hasPermission($permission));
    }

    public function permissionRole(): string
    {
        if (in_array($this->role, ['admin-gudang', 'owner', 'admin-cabang', 'staff-outlet'], true)) {
            return 'legacy-'.$this->role;
        }

        return $this->canonicalRole();
    }
}
