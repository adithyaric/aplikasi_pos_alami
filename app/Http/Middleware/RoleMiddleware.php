<?php

namespace App\Http\Middleware;

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

        // 1. Flatten roles from middleware parameter
        $allowedRoles = collect($roles)->flatMap(fn ($role) => explode('|', $role))->toArray();

        if (in_array($user->role, $allowedRoles)) {
            if (! $this->branchScopedRoleCanAccess($request, $user->role)) {
                return redirect()->route('dashboard')->with('toast_error', 'Akses ditolak.');
            }

            return $next($request);
        }

        // 3. Define landing pages per role
        $homeRoutes = [
            'customer'     => 'market.index',
            'admin-gudang' => 'dashboard',
            'admin-cabang' => 'dashboard',
            'staff-outlet' => 'dashboard',
            'owner'        => 'dashboard',
            'sales'        => 'dashboard',
            'superadmin'   => 'dashboard',
        ];

        $target = $homeRoutes[$user->role] ?? 'login';

        // 4. Loop Prevention: If already on target route but still failing middleware
        if ($request->routeIs($target)) {
            abort(403, 'Unauthorized access to role home.');
        }

        return redirect()->route($target)->with('toast_error', 'Akses ditolak.');
    }

    private function branchScopedRoleCanAccess(Request $request, ?string $role): bool
    {
        if (! in_array($role, ['admin-cabang', 'sales'], true)) {
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
            'penjualan.show',
            'penjualan.pembayaran.edit',
            'penjualan.pembayaran.update',
            'laporan.penjualan.invoice',
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

        $salesOnlyRoutes = [
            'penjualan.create',
            'penjualan.store',
            'penjualan.edit',
            'penjualan.update',
        ];

        $allowedRouteNames = $role === 'admin-cabang'
            ? array_merge($commonRoutes, $adminCabangRoutes)
            : array_merge($commonRoutes, $salesOnlyRoutes);

        return in_array((string) $request->route()?->getName(), $allowedRouteNames, true);
    }
}
