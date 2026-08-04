<aside class="main-sidebar">
    <section class="sidebar">

        <div class="user-panel">
            <div class="pull-left image">
                <img src="{{ $companyLogo }}" class="img-circle" alt="Logo"><br>
            </div>
            <div class="pull-left info">
                <p>{{ Auth::user()?->name }}</p>
                <p>{{ Auth::user()?->role }}</p>
            </div>
        </div>

    @auth
    @php
        $role = auth()->user()->role;
        $isSuperadmin = $role === 'superadmin';
        $isWarehouse = in_array($role, ['superadmin', 'admin-gudang'], true);
        $isStaffOutlet = $role === 'staff-outlet';
        $isAdminCabang = $role === 'admin-cabang';
        $isSales = $role === 'sales';
        $canSeeProcurement = in_array($role, ['superadmin', 'admin-gudang', 'owner'], true);
        $canSeeStock = in_array($role, ['superadmin', 'admin-gudang', 'owner'], true);
        $canSeeBranchStock = in_array($role, ['superadmin', 'admin-gudang', 'owner', 'admin-cabang', 'sales'], true);
        $canSeeWarehouseSales = in_array($role, ['superadmin', 'admin-gudang', 'owner'], true);
        $canSeeBranchSales = in_array($role, ['superadmin', 'admin-gudang', 'owner', 'admin-cabang', 'sales'], true);
        $canSeePurchaseReturn = in_array($role, ['superadmin', 'admin-gudang', 'owner', 'staff-outlet'], true);
        $canSeeSalesReturn = in_array($role, ['superadmin', 'admin-gudang', 'owner', 'admin-cabang', 'sales'], true);
        $currentPenjualan = request()->route('penjualan');
        $currentRefund = request()->route('refund');
        $isCurrentBranchSale = $currentPenjualan instanceof \App\Models\Penjualan && $currentPenjualan->isBranchSale();
        $isCurrentWarehouseSale = $currentPenjualan instanceof \App\Models\Penjualan && $currentPenjualan->isWarehouseSale();
        $requestReturnScope = request()->get('return_scope') ?: request()->get('mode');
        $isCurrentBranchWarehouseReturn = $currentRefund instanceof \App\Models\Refund && $currentRefund->return_scope === 'warehouse_branch_return';
        $branchCustomerReturnActive = request()->routeIs('refund.*')
            && ($requestReturnScope === 'branch_customer_return' || ($requestReturnScope === null && ! $isCurrentBranchWarehouseReturn));
        $branchWarehouseReturnActive = request()->routeIs('refund.*')
            && ($requestReturnScope === 'warehouse_branch_return' || $isCurrentBranchWarehouseReturn);
        $warehouseSalesActive = request()->routeIs('penjualan.index')
            || (request()->routeIs('penjualan.*') && $isCurrentWarehouseSale);
        $branchSalesActive = request()->routeIs('penjualan.branch-index')
            || (request()->routeIs('penjualan.*') && $isCurrentBranchSale);
        $permintaanActive = request()->is('request-orders*')
            || request()->is('picking-lists*')
            || (! $isStaffOutlet && request()->is('delivery-orders*'));
    @endphp
    <ul class="sidebar-menu">

        {{-- Dashboard --}}
        <li class="{{ request()->is('dashboard*') ? 'active' : '' }}">
            <a href="/dashboard"><i class="fa fa-tachometer"></i><span>Dashboard</span></a>
        </li>

        @if ($isWarehouse)
        <li class="{{ request()->is('product*') ? 'active' : '' }}">
            <a href="/product"><i class="fa fa-archive"></i><span>Produk</span></a>
        </li>
        @endif

        @if ($canSeeProcurement)
        <li class="treeview {{ request()->is('pembelian*') || request()->is('penerimaan*') || request()->is('supplier*') || request()->is('customer-po*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-shopping-cart"></i><span>Pembelian</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                @if ($isWarehouse)
                <li class="{{ request()->is('supplier*') ? 'active' : '' }}">
                    <a href="/supplier"><i class="fa fa-archive"></i><span>Supplier</span></a>
                </li>
                <li class="{{ request()->is('customer-po*') ? 'active' : '' }}">
                    <a href="/customer-po"><i class="fa fa-users"></i><span>Customer PO</span></a>
                </li>
                @endif
                <li class="{{ request()->is('pembelian*') && !request()->is('pembelian/*/penerimaan') ? 'active' : '' }}">
                    <a href="/pembelian"><i class="fa fa-file-text-o"></i><span>PO</span></a>
                </li>
                @if (! $isStaffOutlet)
                <li class="{{ request()->is('penerimaan*') || request()->is('pembelian/*/penerimaan') ? 'active' : '' }}">
                    <a href="/penerimaan"><i class="fa fa-download"></i><span>Penerimaan Barang</span></a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if ($canSeeStock)
        <li class="treeview {{ request()->is('stock*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-cubes"></i><span>Stok</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ request()->routeIs('stock.index') ? 'active' : '' }}">
                    <a href="/stock"><i class="fa fa-cubes"></i><span>Stok</span></a>
                </li>
                <li class="{{ request()->routeIs('stock.kartu') ? 'active' : '' }}">
                    <a href="/stock-kartu"><i class="fa fa-cube"></i><span>Kartu Stok</span></a>
                </li>
                <li class="{{ request()->routeIs('stock.opname') ? 'active' : '' }}">
                    <a href="/stock-opname"><i class="fa fa-cube"></i><span>Stock Opname</span></a>
                </li>
            </ul>
        </li>
        @endif

        @if ($canSeeBranchStock)
        <li class="treeview {{ request()->is('branch-stock*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-cubes"></i><span>Stock Cabang</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ request()->routeIs('branch-stock.index') ? 'active' : '' }}">
                    <a href="{{ route('branch-stock.index') }}"><i class="fa fa-cubes"></i><span>Stock Cabang</span></a>
                </li>
                <li class="{{ request()->routeIs('branch-stock.kartu') ? 'active' : '' }}">
                    <a href="{{ route('branch-stock.kartu') }}"><i class="fa fa-list"></i><span>Kartu Stock Cabang</span></a>
                </li>
                <li class="{{ request()->routeIs('branch-stock.opname') ? 'active' : '' }}">
                    <a href="{{ route('branch-stock.opname') }}"><i class="fa fa-check-square-o"></i><span>Opname Cabang</span></a>
                </li>
            </ul>
        </li>
        @endif

        @if ($canSeeWarehouseSales)
        <li class="treeview {{ $warehouseSalesActive || request()->is('customer-penjualan*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-tags"></i><span>Penjualan Gudang</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ $warehouseSalesActive ? 'active' : '' }}"><a href="{{ route('penjualan.index') }}"><i class="fa fa-tags"></i><span>Penjualan</span></a></li>
                <li class="{{ request()->is('customer-penjualan*') ? 'active' : '' }}"><a href="{{ route('customer-penjualan.index') }}"><i class="fa fa-users"></i><span>Customer Penjualan</span></a></li>
            </ul>
        </li>
        @endif

        @if ($canSeeBranchSales)
        <li class="{{ $branchSalesActive ? 'active' : '' }}">
            <a href="{{ route('penjualan.branch-index') }}"><i class="fa fa-line-chart"></i><span>Penjualan Cabang</span></a>
        </li>
        @endif

        @if ($showLegacyDistributionFlow && in_array($role, ['superadmin', 'admin-gudang', 'staff-outlet', 'owner'], true))
        <li class="treeview {{ $permintaanActive ? 'active' : '' }}">
            <a href="#"><i class="fa fa-exchange"></i><span>Permintaan Barang</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ request()->is('request-orders*') ? 'active' : '' }}">
                    <a href="/request-orders"><i class="fa fa-cube"></i><span>Request Cabang</span></a>
                </li>
                @if (! $isStaffOutlet)
                <li class="{{ request()->is('picking-lists*') ? 'active' : '' }}">
                    <a href="/picking-lists"><i class="fa fa-list-ol"></i><span>Picking &amp; Packing</span></a>
                </li>
                <li class="{{ request()->is('delivery-orders*') ? 'active' : '' }}">
                    <a href="/delivery-orders"><i class="fa fa-truck"></i><span>Pengiriman Cabang</span></a>
                </li>
                @endif
            </ul>
        </li>
        @endif

        @if ($showLegacyDistributionFlow && $isStaffOutlet)
        <li class="{{ request()->is('delivery-orders*') ? 'active' : '' }}">
            <a href="/delivery-orders"><i class="fa fa-history"></i><span>Riwayat Permintaan</span></a>
        </li>
        @endif

        @if ($canSeePurchaseReturn || $canSeeSalesReturn)
        @if ($isSales)
        <li class="{{ request()->is('refund') || request()->is('refund/*') ? 'active' : '' }}">
            <a href="{{ route('refund.index', ['return_scope' => 'branch_customer_return']) }}"><i class="fa fa-undo"></i><span>Retur Penjualan</span></a>
        </li>
        @elseif ($isAdminCabang)
        <li class="treeview {{ request()->routeIs('refund.*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-undo"></i><span>Retur Penjualan</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ $branchCustomerReturnActive ? 'active' : '' }}">
                    <a href="{{ route('refund.index', ['return_scope' => 'branch_customer_return']) }}"><i class="fa fa-undo"></i><span>Retur Toko ke Cabang</span></a>
                </li>
                <li class="{{ $branchWarehouseReturnActive ? 'active' : '' }}">
                    <a href="{{ route('refund.index', ['return_scope' => 'warehouse_branch_return']) }}"><i class="fa fa-exchange"></i><span>Retur Cabang ke Gudang</span></a>
                </li>
            </ul>
        </li>
        @elseif ($isStaffOutlet)
        <li class="{{ request()->is('refundPembelian*') ? 'active' : '' }}">
            <a href="/refundPembelian"><i class="fa fa-undo"></i><span>Retur Barang</span></a>
        </li>
        @if ($canSeeSalesReturn)
        <li class="{{ request()->is('refund') || request()->is('refund/*') ? 'active' : '' }}">
            <a href="{{ route('refund.index') }}"><i class="fa fa-undo"></i><span>Retur Penjualan</span></a>
        </li>
        @endif
        @else
        <li class="treeview {{ request()->is('refundPembelian*') || request()->is('refund*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-undo"></i><span>Retur Barang</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                @if (in_array($role, ['superadmin', 'admin-gudang', 'owner'], true))
                <li class="{{ request()->is('refundPembelian*') && (!request()->filled('type') || request()->get('type') === 'gudang_ke_supplier') ? 'active' : '' }}">
                    <a href="/refundPembelian?type=gudang_ke_supplier"><i class="fa fa-undo"></i><span>Retur Pembelian</span></a>
                </li>
                @endif
                @if (in_array($role, ['staff-outlet'], true))
                <li class="{{ request()->is('refundPembelian*') && request()->get('type') === 'outlet_ke_gudang' ? 'active' : '' }}">
                    <a href="/refundPembelian?type=outlet_ke_gudang"><i class="fa fa-exchange"></i><span>Retur Cabang ke Gudang</span></a>
                </li>
                @endif
                @if ($canSeeSalesReturn)
                <li class="{{ request()->is('refund') || request()->is('refund/*') ? 'active' : '' }}">
                    <a href="{{ route('refund.index') }}"><i class="fa fa-undo"></i><span>Retur Penjualan</span></a>
                </li>
                @endif
            </ul>
        </li>
        @endif
        @endif

        @if (in_array($role, ['superadmin', 'admin-gudang', 'staff-outlet', 'owner'], true))
        <li class="{{ in_array(Route::currentRouteName(), ['laporan.index']) ? 'active' : '' }}">
            <a href="/laporan"><i class="fa fa-file-excel-o"></i><span>Laporan</span></a>
        </li>
        @endif

        @if ($isSuperadmin)
        {{-- <li class="treeview {{ request()->is('branchs*') || request()->is('agents*') || request()->is('canvases*') ? 'active' : '' }}"> --}}
            {{-- <a href="#"><i class="fa fa-trello"></i><span>Affiliate</span><i class="fa fa-angle-left pull-right"></i></a> --}}
            {{-- <ul class="treeview-menu"> --}}
                {{-- <li class="{{ request()->is('agents*') ? 'active' : '' }}"> --}}
                    {{-- <a href="/agents"><i class="fa fa-archive"></i><span>Agen</span></a> --}}
                {{-- </li> --}}
                {{-- <li class="{{ request()->is('canvases*') ? 'active' : '' }}"> --}}
                    {{-- <a href="/canvases"><i class="fa fa-archive"></i><span>Canvas</span></a> --}}
                {{-- </li> --}}
            {{-- </ul> --}}
        {{-- </li> --}}
        <li class="treeview {{ request()->is('admin*') || request()->is('salesman*') ? 'active' : '' }}">
            <a href="#"><i class="fa fa-user-secret"></i><span>Admins</span><i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
                <li class="{{ request()->is('admin*') ? 'active' : '' }}">
                    <a href="/admin"><i class="fa fa-user-secret"></i><span>Admins</span></a>
                </li>
                <li class="{{ request()->is('salesman*') ? 'active' : '' }}">
                    <a href="{{ route('salesman.index') }}"><i class="fa fa-users"></i><span>Salesman</span></a>
                </li>
            </ul>
        </li>
        <li class="{{ request()->is('setting*') ? 'active' : '' }}">
            <a href="/setting"><i class="fa fa-gear"></i><span>Setting</span></a>
        </li>
        @endif

    </ul>
    @endauth
    </section>
</aside>
