<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehousePenjualanRequest;
use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Product;
use App\Models\Salesman;
use App\Models\Stock;
use App\Services\BranchPenjualanManager;
use App\Services\PenjualanBalanceService;
use App\Services\WarehousePenjualanManager;
use App\Support\ProductUnitConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function __construct(
        private readonly WarehousePenjualanManager $warehousePenjualanManager,
        private readonly BranchPenjualanManager $branchPenjualanManager,
        private readonly PenjualanBalanceService $balanceService
    ) {
    }

    public function getPenjualan($outlet_id)
    {
        $penjualans = Penjualan::retailSales()
            ->where('outlet_id', $outlet_id)
            ->latest()
            ->get();

        return response()->json($penjualans);
    }

    public function getItems($penjualan_id)
    {
        $penjualan = Penjualan::retailSales()->find($penjualan_id);

        if (! $penjualan) {
            return response()->json([], 404);
        }

        return response()->json($penjualan->items);
    }

    public function marketplace()
    {
        return view('penjualan.marketplace', [
            'penjualan' => Penjualan::retailSales()
                ->has('transaction')
                ->orderBy('created_at', 'desc')
                ->get(),
        ]);
    }

    public function index(Request $request)
    {
        $this->ensurePenjualanAccess();

        if ($this->isBranchMode()) {
            return redirect()->route('penjualan.branch-index', $request->query());
        }

        $filterState = $this->salesFilterState($request);
        $query = Penjualan::with([
            'items.product',
            'operator',
            'salesman',
            'outlet',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'tokoBuyer',
            'paymentTransaction',
            'totalAdjustments',
        ])
            ->warehouseSales()
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        $this->applySalesPeriodFilter($query, $filterState);

        if ($filterState['buyerType']) {
            $query->where('buyer_type', $filterState['buyerType']);
        }

        if ($filterState['buyerId']) {
            $query->where('buyer_id', $filterState['buyerId']);
        }

        $penjualans = $query->get();
        $summary = $this->salesSummary($penjualans);

        return view('penjualan.index', [
            'penjualans' => $penjualans,
            'filterPeriod' => $filterState['period'],
            'dateFrom' => $filterState['dateFrom'],
            'dateTo' => $filterState['dateTo'],
            'selectedBuyerType' => $filterState['buyerType'],
            'selectedBuyerId' => $filterState['buyerId'],
            'buyerTypeOptions' => [
                'agent' => 'Agen',
                'canvas' => 'Canvas',
                'outlet' => 'Cabang',
                'toko' => 'Toko',
            ],
            'buyerOptionsByType' => $this->buyerOptionsByType(),
            'summary' => $summary,
            'canCreatePenjualan' => true,
        ]);
    }

    public function branchIndex(Request $request)
    {
        $this->ensurePenjualanAccess();

        $filterState = $this->salesFilterState($request);
        $query = Penjualan::with([
            'items.product',
            'operator',
            'salesman',
            'outlet',
            'tokoBuyer',
            'paymentTransaction',
            'totalAdjustments',
        ])
            ->branchSales()
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        $this->applySalesPeriodFilter($query, $filterState);

        $user = auth()->user();
        if ($user?->isBranchScoped()) {
            $query->where('outlet_id', $user->branchId());

            if ($user->role === 'sales') {
                $salesmanId = $this->currentSalesmanId();
                $query->where(function ($innerQuery) use ($salesmanId) {
                    $innerQuery->where('user_id', auth()->id())
                        ->when($salesmanId, fn ($salesQuery) => $salesQuery->orWhere('salesman_id', $salesmanId));
                });
            }
        } else {
            if ($request->filled('branch_id')) {
                $query->where('outlet_id', $request->integer('branch_id'));
            }

            if ($request->filled('salesman_id')) {
                $query->where('salesman_id', $request->integer('salesman_id'));
            }
        }

        $penjualans = $query->get();
        $summary = $this->salesSummary($penjualans);
        $selectedBranchId = $user?->isBranchScoped() ? $user->branchId() : $request->integer('branch_id');
        $selectedSalesmanId = $user?->role === 'sales' ? $this->currentSalesmanId() : $request->integer('salesman_id');

        return view('penjualan.branch-index', [
            'penjualans' => $penjualans,
            'filterPeriod' => $filterState['period'],
            'dateFrom' => $filterState['dateFrom'],
            'dateTo' => $filterState['dateTo'],
            'selectedBranchId' => $selectedBranchId,
            'selectedSalesmanId' => $selectedSalesmanId,
            'branches' => $user?->isBranchScoped()
                ? Outlet::whereKey($user->branchId())->orderBy('name')->get()
                : Outlet::branches()->orderBy('name')->get(),
            'salesmen' => Salesman::with('outlet:id,name')
                ->when($user?->isBranchScoped(), fn ($salesmanQuery) => $salesmanQuery->where('outlet_id', $user->branchId()))
                ->orderBy('name')
                ->get(),
            'summary' => $summary,
            'canCreatePenjualan' => $user?->role === 'sales',
        ]);
    }

    public function create()
    {
        $this->ensurePenjualanAccess();

        if ($this->isBranchMode()) {
            $this->ensureBranchSaleCreateAccess();
        }

        return view('penjualan.create', $this->isBranchMode()
            ? $this->branchSaleFormData()
            : $this->warehouseSaleFormData());
    }

    public function edit(Penjualan $penjualan)
    {
        $this->ensurePenjualanAccess();
        $this->ensureSaleCanBeManaged($penjualan);

        $penjualan->load(['items.product', 'paymentTransaction']);

        return view('penjualan.edit', $penjualan->isBranchSale()
            ? $this->branchSaleFormData($penjualan)
            : $this->warehouseSaleFormData($penjualan));
    }

    public function storeWarehouseSale(WarehousePenjualanRequest $request)
    {
        $this->ensurePenjualanAccess();

        $offlineClientId = trim((string) $request->input('offline_client_id', '')) ?: null;

        if ($offlineClientId) {
            $existing = Penjualan::withTrashed()
                ->where('offline_client_id', $offlineClientId)
                ->first();

            if ($existing) {
                return $this->offlineStoreResponse($existing, false);
            }
        }

        try {
            if ($this->isBranchMode()) {
                $this->ensureBranchSaleCreateAccess();

                $penjualan = $this->branchPenjualanManager->create([
                    'code' => $this->generateBranchSaleCode(),
                    'offline_client_id' => $offlineClientId,
                    'buyer_id' => (int) $request->outlet_target_id,
                    'sale_date' => $request->sale_date,
                    'payment_type' => $request->payment_type,
                    'payment_status' => $request->payment_status,
                    'shipping_cost' => (int) ($request->shipping_cost ?? 0),
                    'old_debt_override' => $request->old_debt_override,
                    'discount' => (int) ($request->discount ?? 0),
                    'notes' => $request->notes,
                    'items' => collect($request->items)->map(fn ($item) => [
                        'product_id' => (int) $item['product_id'],
                        'qty' => (float) $item['qty'],
                        'unit' => (string) $item['unit'],
                        'price' => (int) $item['price'],
                        'discount' => (int) ($item['discount'] ?? 0),
                    ])->all(),
                ], (int) auth()->id(), (int) auth()->user()->branchId(), $this->currentSalesmanId());

                if ($offlineClientId) {
                    return $this->offlineStoreResponse($penjualan);
                }

                return redirect()->route('penjualan.show', $penjualan)
                    ->with('toast_success', 'Penjualan cabang berhasil disimpan.');
            }

            $penjualan = $this->warehousePenjualanManager->create([
                'code' => $this->generateWarehouseSaleCode(),
                'offline_client_id' => $offlineClientId,
                'buyer_type' => $request->buyer_type,
                'buyer_id' => $this->resolveBuyerTargetId($request),
                'sale_date' => $request->sale_date,
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'shipping_cost' => (int) ($request->shipping_cost ?? 0),
                'old_debt_override' => $request->old_debt_override,
                'discount' => (int) ($request->discount ?? 0),
                'items' => collect($request->items)->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'qty' => (float) $item['qty'],
                    'unit' => (string) $item['unit'],
                    'price' => (int) $item['price'],
                    'discount' => (int) ($item['discount'] ?? 0),
                ])->all(),
            ], (int) auth()->id());

            if ($offlineClientId) {
                return $this->offlineStoreResponse($penjualan);
            }

            return redirect()->route('penjualan.show', $penjualan)
                ->with('toast_success', 'Penjualan berhasil disimpan.');
        } catch (\Exception $exception) {
            if ($offlineClientId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan penjualan: '.$exception->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    private function offlineStoreResponse(Penjualan $penjualan, bool $created = true)
    {
        return response()->json([
            'success' => true,
            'created' => $created,
            'resource' => 'penjualan',
            'id' => $penjualan->id,
            'code' => $penjualan->code,
            'redirect' => route('penjualan.show', $penjualan),
        ], $created ? 201 : 200);
    }
    public function updateWarehouseSale(WarehousePenjualanRequest $request, Penjualan $penjualan)
    {
        $this->ensurePenjualanAccess();
        $this->ensureSaleCanBeManaged($penjualan);

        try {
            if ($penjualan->isBranchSale()) {
                $penjualan = $this->branchPenjualanManager->update($penjualan, [
                    'buyer_id' => (int) $request->outlet_target_id,
                    'sale_date' => $request->sale_date,
                    'payment_type' => $request->payment_type,
                    'payment_status' => $request->payment_status,
                    'shipping_cost' => (int) ($request->shipping_cost ?? 0),
                    'old_debt_override' => $request->old_debt_override,
                    'discount' => (int) ($request->discount ?? 0),
                    'notes' => $request->notes,
                    'items' => collect($request->items)->map(fn ($item) => [
                        'product_id' => (int) $item['product_id'],
                        'qty' => (float) $item['qty'],
                        'unit' => (string) $item['unit'],
                        'price' => (int) $item['price'],
                        'discount' => (int) ($item['discount'] ?? 0),
                    ])->all(),
                ], (int) auth()->id(), (int) auth()->user()->branchId(), $this->currentSalesmanId());

                return redirect()->route('penjualan.show', $penjualan)
                    ->with('toast_success', 'Penjualan cabang berhasil diperbarui.');
            }

            $penjualan = $this->warehousePenjualanManager->update($penjualan, [
                'buyer_type' => $request->buyer_type,
                'buyer_id' => $this->resolveBuyerTargetId($request),
                'sale_date' => $request->sale_date,
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'shipping_cost' => (int) ($request->shipping_cost ?? 0),
                'old_debt_override' => $request->old_debt_override,
                'discount' => (int) ($request->discount ?? 0),
                'items' => collect($request->items)->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'qty' => (float) $item['qty'],
                    'unit' => (string) $item['unit'],
                    'price' => (int) $item['price'],
                    'discount' => (int) ($item['discount'] ?? 0),
                ])->all(),
            ], (int) auth()->id());

            return redirect()->route('penjualan.show', $penjualan)
                ->with('toast_success', 'Penjualan berhasil diperbarui.');
        } catch (\Exception $exception) {
            return redirect()->back()
                ->withInput()
                ->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function lastPrice(Request $request)
    {
        $this->ensurePenjualanAccess();

        $request->validate([
            'buyer_type' => 'required|in:agent,canvas,outlet,toko',
            'buyer_id' => 'required|integer',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $priceQuery = PenjualanItem::query()
            ->select('penjualan_items.price')
            ->join('penjualans', 'penjualans.id', '=', 'penjualan_items.penjualan_id')
            ->where('penjualans.buyer_type', $request->buyer_type)
            ->where('penjualans.buyer_id', (int) $request->buyer_id)
            ->where('penjualan_items.product_id', (int) $request->product_id);

        if ($this->isBranchMode()) {
            $priceQuery->where('penjualans.sale_channel', 'branch')
                ->where('penjualans.outlet_id', auth()->user()->branchId());
        } else {
            $priceQuery->where('penjualans.sale_channel', 'warehouse');
        }

        $price = $priceQuery->orderByDesc('penjualans.sale_date')
            ->orderByDesc('penjualan_items.id')
            ->value('price');

        return response()->json([
            'price' => $price !== null ? (int) $price : null,
        ]);
    }

    public function oldDebt(Request $request)
    {
        $this->ensurePenjualanAccess();

        $validated = $request->validate([
            'buyer_type' => 'required|in:agent,canvas,outlet,toko',
            'buyer_id' => 'required|integer',
            'sale_date' => 'nullable|date',
            'exclude_id' => 'nullable|integer',
        ]);

        return response()->json([
            'old_debt' => $this->balanceService->calculateOldDebt(
                $validated['buyer_type'],
                (int) $validated['buyer_id'],
                null,
                $validated['sale_date'] ?? now(),
                isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null,
            ),
        ]);
    }

    public function editPembayaran(Penjualan $penjualan)
    {
        $this->ensureSalePaymentAccess($penjualan);

        $penjualan->load([
            'items.product',
            'operator',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'paymentTransaction',
        ]);

        return view('penjualan.pembayaran-edit', [
            'penjualan' => $penjualan,
            'paymentHistory' => $penjualan->paymentTransaction?->payment_history ?? [],
        ]);
    }

    public function updatePembayaran(Request $request, Penjualan $penjualan)
    {
        $this->ensureSalePaymentAccess($penjualan);

        $currentAmount = (float) ($penjualan->paymentTransaction?->amount ?? 0);
        $remainingAmount = max(0, (float) $penjualan->total - $currentAmount);

        $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,giro_cek,lainnya',
            'payment_reference' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01|max:'.$remainingAmount,
            'notes' => 'nullable|string',
        ], [
            'payment_date.required' => 'Tanggal pembayaran harus diisi.',
            'payment_method.required' => 'Metode pembayaran harus dipilih.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'amount.required' => 'Jumlah pembayaran harus diisi.',
            'amount.numeric' => 'Jumlah pembayaran harus berupa angka.',
            'amount.min' => 'Jumlah pembayaran minimal 0.01.',
            'amount.max' => 'Jumlah pembayaran melebihi sisa piutang.',
        ]);

        DB::transaction(function () use ($request, $penjualan, $currentAmount) {
            $payment = $penjualan->paymentTransaction ?: $penjualan->paymentTransaction()->make();
            $history = $payment->payment_history ?? [];
            $paidAmount = $currentAmount + (float) $request->amount;

            $history[] = [
                'payment_date' => $request->payment_date,
                'amount' => (float) $request->amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference ?: 'PAY-'.$penjualan->code.'-'.now()->format('YmdHis'),
                'notes' => $request->notes,
                'created_at' => now()->toDateTimeString(),
            ];

            $status = $paidAmount <= 0
                ? 'unpaid'
                : ($paidAmount >= (float) $penjualan->total ? 'paid' : 'partial');

            $payment->fill([
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference ?: 'PAY-'.$penjualan->code.'-'.now()->format('YmdHis'),
                'payment_history' => $history,
                'status' => $status,
                'amount' => $paidAmount,
                'notes' => $request->notes,
            ]);

            $penjualan->paymentTransaction()->save($payment);
            $penjualan->update([
                'payment_status' => $status,
            ]);
        });

        return redirect()
            ->route('penjualan.pembayaran.edit', $penjualan)
            ->with('toast_success', 'Pembayaran penjualan berhasil disimpan.');
    }

    public function show(Penjualan $penjualan)
    {
        $this->ensureSaleCanBeViewed($penjualan);

        $penjualan->load([
            'items.product',
            'operator',
            'customer',
            'kasir',
            'outlet',
            'salesman',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'tokoBuyer',
            'transaction.payment',
            'paymentTransaction',
            'totalAdjustments.refund',
        ]);

        return view('penjualan.show', [
            'penjualan' => $penjualan,
            'backRoute' => $penjualan->isBranchSale() ? route('penjualan.branch-index') : route('penjualan.index'),
        ]);
    }

    public function destroy(Penjualan $penjualan)
    {
        $this->ensureSaleCanBeManaged($penjualan);

        if ($penjualan->isWarehouseSale() || $penjualan->isBranchSale()) {
            return redirect()->route('penjualan.index')
                ->with('toast_error', 'Penjualan gudang/cabang tidak dapat dihapus. Gunakan koreksi stok bila diperlukan.');
        }

        $penjualan->delete();

        return redirect(route('penjualan.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    private function ensureWarehouseSaleAccess(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['superadmin', 'admin-gudang', 'owner'], true), 403);
    }

    private function ensureSalePaymentAccess(Penjualan $penjualan): void
    {
        if ($penjualan->isWarehouseSale()) {
            $this->ensureWarehouseSaleAccess();

            return;
        }

        abort_unless($penjualan->isBranchSale(), 404);

        if ($this->isBranchMode()) {
            abort_unless((int) $penjualan->outlet_id === (int) auth()->user()->branchId(), 403);

            if (auth()->user()?->role === 'sales') {
                $salesmanId = $this->currentSalesmanId();
                abort_unless(
                    (int) $penjualan->user_id === (int) auth()->id()
                    || ($salesmanId && (int) $penjualan->salesman_id === $salesmanId),
                    403
                );
            }

            return;
        }

        $this->ensureWarehouseSaleAccess();
    }

    private function ensureBranchSaleCreateAccess(): void
    {
        abort_unless(auth()->user()?->role === 'sales', 403);
    }

    private function ensurePenjualanAccess(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['superadmin', 'admin-gudang', 'owner', 'admin-cabang', 'sales'], true), 403);
    }

    private function isBranchMode(): bool
    {
        return auth()->user()?->isBranchScoped() && in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true);
    }

    private function ensureSaleCanBeManaged(Penjualan $penjualan): void
    {
        if ($this->isBranchMode()) {
            abort_unless($penjualan->isBranchSale() && (int) $penjualan->outlet_id === (int) auth()->user()->branchId(), 403);
            abort_unless(auth()->user()?->role === 'sales', 403);

            $salesmanId = $this->currentSalesmanId();
            abort_unless((int) $penjualan->user_id === (int) auth()->id() || ($salesmanId && (int) $penjualan->salesman_id === $salesmanId), 403);

            return;
        }

        $this->ensureWarehouseSaleAccess();
        abort_unless($penjualan->isWarehouseSale(), 404);
    }

    private function ensureSaleCanBeViewed(Penjualan $penjualan): void
    {
        if ($this->isBranchMode()) {
            abort_unless($penjualan->isBranchSale() && (int) $penjualan->outlet_id === (int) auth()->user()->branchId(), 403);

            if (auth()->user()?->role === 'sales') {
                $salesmanId = $this->currentSalesmanId();
                abort_unless((int) $penjualan->user_id === (int) auth()->id() || ($salesmanId && (int) $penjualan->salesman_id === $salesmanId), 403);
            }
        }
    }

    private function currentSalesmanId(): ?int
    {
        if (auth()->user()?->role !== 'sales') {
            return null;
        }

        return Salesman::where('user_id', auth()->id())->value('id');
    }

    private function warehouseSaleFormData(?Penjualan $penjualan = null): array
    {
        $converter = app(ProductUnitConverter::class);
        $unitChannel = $this->warehouseSaleUnitChannel();
        $products = Product::with(['stocks' => function ($query) {
            $query->where('status', 'available')
                ->where('qty', '>', 0)
                ->orderBy('id');
        }])
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'harga_jual',
                'satuan',
                'satuan_besar',
                'konversi_qty',
                'satuan_terbesar',
                'konversi_qty_terbesar',
            ])
            ->map(function (Product $product) use ($converter, $unitChannel) {
                $availableQty = (int) $product->stocks->sum(function ($stock) {
                    return $this->resolveStockAvailableQty($stock);
                });

                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'harga_jual' => (int) ($product->harga_jual ?? 0),
                    'available_stock_qty' => $availableQty,
                    'base_unit' => $product->satuan ?: 'PCS',
                    'stock_summary' => $converter->stockSummaryDisplay($product, $availableQty),
                    'default_unit' => $converter->defaultInputUnit($product, $unitChannel),
                    'unit_factors' => $converter->unitMultipliers($product),
                    'units' => $converter->inputUnits($product, $unitChannel),
                ];
            })
            ->filter(fn (array $product) => $product['available_stock_qty'] > 0)
            ->values();

        return [
            'penjualan' => $penjualan,
            'saleMode' => 'warehouse',
            'branchName' => null,
            'code' => $penjualan?->code ?? $this->generateWarehouseSaleCode(),
            'saleDate' => ($penjualan?->sale_date ?? now())->format('Y-m-d'),
            'agents' => Agent::where('is_active', true)->orderBy('name')->get(),
            'canvases' => Canvas::where('is_active', true)->orderBy('name')->get(),
            'outlets' => Outlet::branches()->orderBy('name')->get(),
            'shops' => Outlet::shops()->orderBy('name')->get(),
            'products' => $products,
            'initialItems' => old('items')
                ?: ($penjualan
                    ? $penjualan->items->map(fn ($item, $index) => [
                        'product_id' => (int) $item->product_id,
                        'qty' => (float) ($item->qty_input ?? $item->qty),
                        'unit' => $item->unit ?: $item->product?->satuan,
                        'price' => (int) $item->price,
                        // Move a legacy invoice-level discount into the first line while editing.
                        'discount' => (int) $item->discount + ($index === 0 ? (int) ($penjualan->discount ?? 0) : 0),
                    ])->values()->all()
                    : []),
            'calculatedOldDebt' => $penjualan ? $this->balanceService->calculatedOldDebt($penjualan) : 0,
        ];
    }

    private function branchSaleFormData(?Penjualan $penjualan = null): array
    {
        $branchId = (int) auth()->user()->branchId();
        abort_unless($branchId > 0, 403);

        $converter = app(ProductUnitConverter::class);
        $existingQtyByProduct = $penjualan
            ? $penjualan->items->pluck('qty', 'product_id')
            : collect();
        $productIds = OwnerStock::where('owner_id', $branchId)
            ->where('qty', '>', 0)
            ->pluck('product_id')
            ->merge($existingQtyByProduct->keys())
            ->unique()
            ->values();

        $products = Product::whereIn('id', $productIds)
            ->with(['ownerStocks' => fn ($query) => $query->where('owner_id', $branchId)])
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'harga_jual',
                'satuan',
                'satuan_besar',
                'konversi_qty',
                'satuan_terbesar',
                'konversi_qty_terbesar',
            ])
            ->map(function (Product $product) use ($converter, $existingQtyByProduct) {
                $availableQty = (int) $product->ownerStocks->sum('qty')
                    + (int) ($existingQtyByProduct[$product->id] ?? 0);

                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'harga_jual' => (int) ($product->harga_jual ?? 0),
                    'available_stock_qty' => $availableQty,
                    'base_unit' => $product->satuan ?: 'PCS',
                    'stock_summary' => $converter->stockSummaryDisplay($product, $availableQty),
                    'default_unit' => $converter->defaultInputUnit($product, 'branch'),
                    'unit_factors' => $converter->unitMultipliers($product),
                    'units' => $converter->inputUnits($product, 'branch'),
                ];
            })
            ->filter(fn (array $product) => $product['available_stock_qty'] > 0)
            ->values();

        return [
            'penjualan' => $penjualan,
            'saleMode' => 'branch',
            'branchName' => auth()->user()->outlet?->name,
            'code' => $penjualan?->code ?? $this->generateBranchSaleCode(),
            'saleDate' => ($penjualan?->sale_date ?? now())->format('Y-m-d'),
            'agents' => collect(),
            'canvases' => collect(),
            'outlets' => Outlet::shops()->orderBy('name')->get(),
            'products' => $products,
            'initialItems' => old('items')
                ?: ($penjualan
                    ? $penjualan->items->map(fn ($item, $index) => [
                        'product_id' => (int) $item->product_id,
                        'qty' => (float) ($item->qty_input ?? $item->qty),
                        'unit' => $item->unit ?: $item->product?->satuan,
                        'price' => (int) $item->price,
                        // Move a legacy invoice-level discount into the first line while editing.
                        'discount' => (int) $item->discount + ($index === 0 ? (int) ($penjualan->discount ?? 0) : 0),
                    ])->values()->all()
                    : []),
            'calculatedOldDebt' => $penjualan ? $this->balanceService->calculatedOldDebt($penjualan) : 0,
        ];
    }

    private function warehouseSaleUnitChannel(): string
    {
        return match (auth()->user()?->role) {
            'sales' => 'sales',
            'staff-outlet' => 'branch',
            default => 'distribution',
        };
    }

    private function generateWarehouseSaleCode(): string
    {
        $lastSale = Penjualan::warehouseSales()->latest('id')->first();
        $nextNumber = $lastSale ? ((int) substr((string) $lastSale->code, 3) + 1) : 1;

        return 'PNJ'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function generateBranchSaleCode(): string
    {
        $lastSale = Penjualan::branchSales()
            ->where('code', 'like', 'INV-CBG-%')
            ->latest('id')
            ->first();

        $nextNumber = 1;
        if ($lastSale && preg_match('/(\d+)$/', (string) $lastSale->code, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'INV-CBG-'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function salesFilterState(Request $request): array
    {
        return [
            'period' => $request->input('period', 'all'),
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
            'buyerType' => $request->input('buyer_type'),
            'buyerId' => $request->integer('buyer_id') ?: null,
        ];
    }

    private function buyerOptionsByType(): array
    {
        return [
            'agent' => Agent::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'code' => $item->code])->values()->all(),
            'canvas' => Canvas::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'code' => $item->code])->values()->all(),
            'outlet' => Outlet::branches()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
            'toko' => Outlet::shops()->orderBy('name')->get(['id', 'name'])->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values()->all(),
        ];
    }

    private function applySalesPeriodFilter($query, array $filterState): void
    {
        if ($filterState['period'] !== 'daterange' || ! $filterState['dateFrom'] || ! $filterState['dateTo']) {
            return;
        }

        $query->whereBetween('sale_date', [
            Carbon::parse($filterState['dateFrom'])->startOfDay(),
            Carbon::parse($filterState['dateTo'])->endOfDay(),
        ]);
    }

    private function salesSummary($penjualans): array
    {
        $notPaidSales = $penjualans->filter(fn (Penjualan $penjualan) => ($penjualan->payment_status ?? 'unpaid') !== 'paid');
        $paidSales = $penjualans->filter(fn (Penjualan $penjualan) => ($penjualan->payment_status ?? 'unpaid') === 'paid');
        $returnAffectedSales = $penjualans->filter(fn (Penjualan $penjualan) => $penjualan->totalAdjustments->sum('amount') > 0);

        return [
            'totalPiutang' => $notPaidSales->sum(fn (Penjualan $penjualan) => max(0, (float) $penjualan->total - (float) ($penjualan->paymentTransaction?->amount ?? 0))),
            'countPiutang' => $notPaidSales->count(),
            'totalLunas' => $paidSales->sum(fn (Penjualan $penjualan) => (float) ($penjualan->paymentTransaction?->amount ?? $penjualan->total)),
            'countLunas' => $paidSales->count(),
            'totalTransaksi' => $penjualans->count(),
            'totalTransaksiNominal' => $penjualans->sum('total'),
            'totalPotonganRetur' => $returnAffectedSales->sum(fn (Penjualan $penjualan) => $penjualan->totalAdjustments->sum('amount')),
            'countPotonganRetur' => $returnAffectedSales->count(),
        ];
    }

    private function resolveBuyerTargetId(WarehousePenjualanRequest $request): int
    {
        return (int) match ($request->buyer_type) {
            'agent' => $request->agent_id,
            'canvas' => $request->canvas_id,
            'outlet' => $request->outlet_target_id,
            'toko' => $request->toko_id,
        };
    }

    private function resolveStockAvailableQty(Stock $stock): int
    {
        if ($stock->qty_available !== null) {
            return max(0, (int) $stock->qty_available);
        }

        return max(0, (int) $stock->qty - (int) ($stock->qty_reserved ?? 0));
    }
}
