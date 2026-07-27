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
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'penjualan.index',
            'penjualan.last-price',
            'penjualan.create',
            'penjualan.store',
            'penjualan.edit',
            'penjualan.update',
            'penjualan.show',
            'penjualan.print',
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
        ];

        $adminCabangRoutes = [
            'customer.index',
            'customer.create',
            'customer.store',
            'customer.edit',
            'customer.update',
            'customer.destroy',
            'refundPembelian.index',
            'refundPembelian.create',
            'refundPembelian.store',
            'refundPembelian.show',
            'retur.outlet.products',
            'salesman.index',
            'salesman.create',
            'salesman.store',
            'salesman.edit',
            'salesman.update',
            'salesman.destroy',
        ];

        $allowedRouteNames = $role === 'admin-cabang'
            ? array_merge($commonRoutes, $adminCabangRoutes)
            : $commonRoutes;

        return in_array((string) $request->route()?->getName(), $allowedRouteNames, true);
    }
}
