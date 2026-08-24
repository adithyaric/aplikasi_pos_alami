<?php

namespace App\Http\Middleware;

use App\Models\Penjualan;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();
        $allowedRoles = collect($roles)
            ->flatMap(fn ($role) => explode('|', $role))
            ->values()
            ->all();

        if (! in_array($user->role, $allowedRoles, true)) {
            $target = [
                'customer' => 'market.index',
                'admin-gudang' => 'dashboard',
                'admin-cabang' => 'dashboard',
                'staff-outlet' => 'dashboard',
                'owner' => 'dashboard',
                'po' => 'dashboard',
                'finance' => 'dashboard',
                'leader-cabang' => 'dashboard',
                'sales' => 'dashboard',
                'superadmin' => 'dashboard',
            ][$user->role] ?? 'login';

            if ($request->routeIs($target)) {
                abort(403, 'Unauthorized access to role home.');
            }

            return redirect()->route($target)->with('toast_error', 'Akses ditolak.');
        }

        if (! $this->branchScopedRoleCanAccess($request, $user->role)) {
            return redirect()->route('dashboard')->with('toast_error', 'Akses ditolak.');
        }

        $permission = $this->requiredPermission($request, $user);
        if ($permission === '__denied__' || ($permission && ! $user->hasPermission($permission))) {
            abort(403, 'Akses ditolak.');
        }

        return $next($request);
    }

    private function requiredPermission(Request $request, $user): ?string
    {
        $route = (string) $request->route()?->getName();

        if ($route === '' || in_array($route, [
            'dashboard', 'offline.csrf-token', 'profile.edit', 'profile.update', 'profile.destroy',
        ], true)) {
            return null;
        }

        if ($route === 'customer.get') {
            return $user->isBranchScoped() ? 'penjualan.branch' : 'penjualan.warehouse';
        }
        if ($route === 'outlet.kas') {
            return 'pembelian.po';
        }
        if ($route === 'stock.product-history') {
            return 'stock.view';
        }

        if (str_starts_with($route, 'category.')) {
            return 'category.manage';
        }
        if (str_starts_with($route, 'product.')) {
            return 'product.manage';
        }
        if (str_starts_with($route, 'supplier.')) {
            return 'supplier.manage';
        }
        if (str_starts_with($route, 'customer-po.')) {
            return 'customer-po.manage';
        }
        if (str_starts_with($route, 'stock.')) {
            return in_array($route, ['stock.index', 'stock.show'], true) ? 'stock.view' : 'stock.manage';
        }
        if (str_starts_with($route, 'branch-stock.')) {
            return in_array($route, ['branch-stock.index', 'branch-stock.kartu', 'branch-stock.kartu.data', 'branch-stock.opname', 'branch-stock.opname.data'], true)
                ? 'branch-stock.view'
                : 'branch-stock.manage';
        }
        if (str_starts_with($route, 'customer-penjualan.')) {
            return 'customer-penjualan.manage';
        }
        if ($route === 'outlet.store-shop') {
            return 'customer-penjualan.manage';
        }
        if (str_starts_with($route, 'outlet.')) {
            return 'affiliate.manage';
        }
        if (str_starts_with($route, 'salesman.')) {
            return 'affiliate.manage';
        }
        if (str_starts_with($route, 'setting')) {
            return 'settings.manage';
        }

        if (str_starts_with($route, 'pembelian.') || str_starts_with($route, 'penerimaan')) {
            if (str_contains($route, 'pembayaran')) {
                return 'pembelian.payment';
            }
            if (str_contains($route, 'penerimaan') || str_starts_with($route, 'penerimaan')) {
                return 'pembelian.receive';
            }
            if (str_contains($route, 'owner-approve') || str_contains($route, 'owner-reject')) {
                return 'pembelian.approval';
            }

            return 'pembelian.po';
        }
        if (in_array($route, ['get-pembelian', 'pembelian-detail.items'], true)) {
            return 'pembelian.po';
        }

        if (str_starts_with($route, 'penjualan.') || in_array($route, ['get-penjualan', 'penjualan-detail.items'], true)) {
            return $this->penjualanPermission($request, $user);
        }

        if (str_starts_with($route, 'refundPembelian.')) {
            return 'refund.purchase';
        }
        if (str_starts_with($route, 'refund.')) {
            return 'refund.sales';
        }

        if (str_starts_with($route, 'laporan.')) {
            if (str_starts_with($route, 'laporan.templates')) {
                return 'reports.manage';
            }

            if ($user->isBranchScoped() && in_array($user->role, ['leader-cabang', 'sales'], true)
                && ! in_array($route, [
                    'laporan.index',
                    'laporan.penjualan',
                    'laporan.piutang',
                    'laporan.pembayaran',
                    'laporan.penjualan.invoice',
                    'laporan.penjualan.nota',
                    'laporan.penjualan.surat-jalan',
                ], true)) {
                return '__denied__';
            }

            return $user->isBranchScoped() ? 'reports.branch' : 'reports.all';
        }

        if ($user->canonicalRole() !== $user->role || in_array($user->role, ['po', 'finance', 'leader-cabang', 'sales'], true)) {
            return '__denied__';
        }

        // Legacy roles retain access to routes that predate the permission matrix.
        return null;
    }

    private function penjualanPermission(Request $request, $user): string
    {
        $sale = $request->route('penjualan');
        $isBranchSale = $user->isBranchScoped();

        if ($sale instanceof Penjualan) {
            $isBranchSale = $sale->isBranchSale();
        }

        return $isBranchSale ? 'penjualan.branch' : 'penjualan.warehouse';
    }

    private function branchScopedRoleCanAccess(Request $request, ?string $role): bool
    {
        if (! in_array($role, ['admin-cabang', 'leader-cabang', 'sales'], true)) {
            return true;
        }

        $route = (string) $request->route()?->getName();

        if (in_array($role, ['leader-cabang', 'sales'], true) && str_starts_with($route, 'laporan.')) {
            return true;
        }

        $commonRoutes = [
            'dashboard',
            'offline.csrf-token',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'penjualan.index',
            'penjualan.branch-index',
            'penjualan.last-price',
            'penjualan.old-debt',
            'penjualan.show',
            'penjualan.pembayaran.edit',
            'penjualan.pembayaran.update',
            'laporan.penjualan.invoice',
            'laporan.penjualan.nota',
            'laporan.penjualan.surat-jalan',
            'refund.index',
            'refund.create',
            'refund.store',
            'refund.show',
            'refund.edit',
            'refund.update',
            'refund.destroy',
            'refund.latest-invoice',
            'refund.last-price',
            'branch-stock.index',
            'branch-stock.kartu',
            'branch-stock.kartu.data',
            'branch-stock.opname',
            'branch-stock.opname.data',
            'branch-stock.opname.save',
            'outlet.store-shop',
            'customer-penjualan.index',
            'customer-penjualan.create',
            'customer-penjualan.store',
            'customer-penjualan.edit',
            'customer-penjualan.update',
            'customer-penjualan.destroy',
            'customer-penjualan.options',
        ];

        if (in_array($role, ['leader-cabang', 'sales'], true)) {
            return in_array($route, array_merge($commonRoutes, [
                'penjualan.create',
                'penjualan.store',
                'penjualan.edit',
                'penjualan.update',
            ]), true);
        }

        $adminCabangRoutes = [
            'customer.index',
            'customer.create',
            'customer.store',
            'customer.edit',
            'customer.update',
            'customer.destroy',
            'salesman.index',
            'salesman.create',
            'salesman.store',
            'salesman.edit',
            'salesman.update',
            'salesman.destroy',
        ];

        return in_array($route, array_merge($commonRoutes, $adminCabangRoutes), true);
    }
}
