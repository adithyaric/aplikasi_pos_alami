<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesReturnRequest;
use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Kas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Salesman;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\SalesReturnManager;
use App\Support\ProductUnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function __construct(
        private readonly SalesReturnManager $salesReturnManager
    ) {
    }

    public function index(Request $request)
    {
        $query = Refund::with([
            'user',
            'approver',
            'customer',
            'penjualan',
            'appliedPenjualan',
            'sourceOutlet',
            'salesman',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'tokoBuyer',
        ])->orderByDesc('tanggal')->orderByDesc('id');

        $this->scopeRefundQueryForCurrentUser($query);

        if ($request->filled('buyer_type')) {
            $query->where('buyer_type', $request->input('buyer_type'));
        }

        if ($request->filled('return_scope')) {
            $query->where('return_scope', $request->input('return_scope'));
        }

        if ($request->input('period') === 'daterange' && $request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('tanggal', [
                \Carbon\Carbon::parse($request->input('date_from'))->startOfDay(),
                \Carbon\Carbon::parse($request->input('date_to'))->endOfDay(),
            ]);
        }

        $refunds = $query->get();
        $summary = [
            'totalNominal' => $refunds->sum('total'),
            'totalCount' => $refunds->count(),
            'affiliateNominal' => $refunds->where('return_scope', SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE)->sum('total'),
            'affiliateCount' => $refunds->where('return_scope', SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE)->count(),
            'branchWarehouseNominal' => $refunds->where('return_scope', SalesReturnManager::SCOPE_WAREHOUSE_BRANCH)->sum('total'),
            'branchWarehouseCount' => $refunds->where('return_scope', SalesReturnManager::SCOPE_WAREHOUSE_BRANCH)->count(),
            'branchCustomerNominal' => $refunds->where('return_scope', SalesReturnManager::SCOPE_BRANCH_CUSTOMER)->sum('total'),
            'branchCustomerCount' => $refunds->where('return_scope', SalesReturnManager::SCOPE_BRANCH_CUSTOMER)->count(),
        ];

        return view('refunds.index', [
            'refunds' => $refunds,
            'filterPeriod' => $request->input('period', 'all'),
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
            'selectedBuyerType' => $request->input('buyer_type'),
            'selectedReturnScope' => $request->input('return_scope'),
            'buyerTypeOptions' => $this->buyerTypeOptions(),
            'returnScopeOptions' => $this->returnScopeOptions(),
            'summary' => $summary,
            'canApproveBranchWarehouseReturn' => auth()->user()?->role === 'superadmin',
        ]);
    }

    public function create(Request $request)
    {
        return view('refunds.create', $this->refundFormData(null, $request->integer('penjualan_id') ?: null));
    }

    public function edit(Refund $refund)
    {
        $this->ensureRefundAccessible($refund, true);
        $refund->loadMissing('refundItems.product');

        return view('refunds.edit', $this->refundFormData($refund));
    }

    public function show(Refund $refund)
    {
        $this->ensureRefundAccessible($refund);
        $refund->load([
            'approver',
            'customer',
            'outlet',
            'penjualan',
            'appliedPenjualan',
            'sourceOutlet',
            'salesman',
            'refundItems.product',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'tokoBuyer',
            'totalAdjustment',
        ]);

        return view('refunds.show', [
            'refund' => $refund,
        ]);
    }

    public function store(SalesReturnRequest $request)
    {
        try {
            $data = $this->returnPayload($request->validated());
            $refund = $this->salesReturnManager->create($data, (int) auth()->id());

            return redirect(route('refund.show', $refund))->with(
                'toast_success',
                $refund->isPendingApproval()
                    ? 'Retur cabang dikirim dan menunggu konfirmasi superadmin.'
                    : 'Berhasil Menyimpan Data!'
            );
        } catch (\Throwable $exception) {
            return redirect()->back()
                ->withInput()
                ->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function update(SalesReturnRequest $request, Refund $refund)
    {
        $this->ensureRefundAccessible($refund, true);

        try {
            $data = $this->returnPayload($request->validated());
            $refund = $this->salesReturnManager->update($refund, $data, (int) auth()->id());

            return redirect(route('refund.show', $refund))->with(
                'toast_success',
                $refund->isPendingApproval()
                    ? 'Permintaan retur cabang berhasil diperbarui.'
                    : 'Berhasil Menyimpan Data!'
            );
        } catch (\Throwable $exception) {
            return redirect()->back()
                ->withInput()
                ->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function destroy(Refund $refund)
    {
        $this->ensureRefundAccessible($refund, true);
        DB::transaction(fn () => $this->salesReturnManager->rollback($refund));

        return redirect(route('refund.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function approve(Request $request, Refund $refund)
    {
        abort_unless(auth()->user()?->role === 'superadmin', 403);

        try {
            $refund = $this->salesReturnManager->approve(
                $refund,
                (int) auth()->id(),
                $request->input('approval_note')
            );

            return redirect(route('refund.show', $refund))->with('toast_success', 'Retur cabang berhasil dikonfirmasi.');
        } catch (\Throwable $exception) {
            return redirect()->back()->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function reject(Request $request, Refund $refund)
    {
        abort_unless(auth()->user()?->role === 'superadmin', 403);

        try {
            $refund = $this->salesReturnManager->reject(
                $refund,
                (int) auth()->id(),
                $request->input('approval_note')
            );

            return redirect(route('refund.show', $refund))->with('toast_success', 'Retur cabang ditolak.');
        } catch (\Throwable $exception) {
            return redirect()->back()->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function latestInvoicePreview(Request $request)
    {
        $payload = $this->previewPayload($request);
        $invoice = $this->salesReturnManager->latestInvoice($payload);

        if (! $invoice) {
            return response()->json([
                'invoice' => null,
                'message' => 'Tidak ada invoice terbaru dengan status belum lunas untuk pembeli ini.',
            ]);
        }

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'code' => $invoice->code,
                'sale_date' => optional($invoice->sale_date)->format('Y-m-d'),
                'total' => (float) $invoice->total,
                'payment_status' => $invoice->payment_status,
                'max_return_total' => max(0, (float) $invoice->total - 1),
            ],
        ]);
    }

    public function lastReturnPrice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $payload = $this->previewPayload($request);
        $price = $this->salesReturnManager->lastPrice($payload, $request->integer('product_id'));

        return response()->json(['price' => $price]);
    }

    private function normalizeMoney($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    private function scopeRefundQueryForCurrentUser($query): void
    {
        $user = auth()->user();
        if (! $user?->isBranchScoped() || ! in_array($user->role, ['admin-cabang', 'sales'], true)) {
            return;
        }

        if ($user->role === 'sales') {
            $salesmanId = Salesman::where('user_id', auth()->id())->value('id');
            $query->where('return_scope', SalesReturnManager::SCOPE_BRANCH_CUSTOMER)
                ->where('source_outlet_id', $user->branchId())
                ->where(function ($builder) use ($salesmanId) {
                    $builder->where('user_id', auth()->id())
                        ->when($salesmanId, fn ($salesQuery) => $salesQuery->orWhere('salesman_id', $salesmanId));
                });

            return;
        }

        $branchId = $user->branchId();
        $query->where(function ($builder) use ($branchId) {
            $builder->where(function ($branchCustomerQuery) use ($branchId) {
                $branchCustomerQuery->where('return_scope', SalesReturnManager::SCOPE_BRANCH_CUSTOMER)
                    ->where('source_outlet_id', $branchId);
            })->orWhere(function ($branchWarehouseQuery) use ($branchId) {
                $branchWarehouseQuery->where('return_scope', SalesReturnManager::SCOPE_WAREHOUSE_BRANCH)
                    ->where('buyer_type', 'outlet')
                    ->where('buyer_id', $branchId);
            });
        });
    }

    private function ensureRefundAccessible(Refund $refund, bool $manage = false): void
    {
        $user = auth()->user();
        if (! $user?->isBranchScoped() || ! in_array($user->role, ['admin-cabang', 'sales'], true)) {
            return;
        }

        if ($user->role === 'sales') {
            abort_unless(
                $refund->return_scope === SalesReturnManager::SCOPE_BRANCH_CUSTOMER
                && (int) $refund->source_outlet_id === (int) $user->branchId(),
                403
            );

            $salesmanId = Salesman::where('user_id', auth()->id())->value('id');
            abort_unless(
                (int) $refund->user_id === (int) auth()->id()
                || ($salesmanId && (int) $refund->salesman_id === (int) $salesmanId),
                403
            );

            return;
        }

        $belongsToBranch = ($refund->return_scope === SalesReturnManager::SCOPE_BRANCH_CUSTOMER
                && (int) $refund->source_outlet_id === (int) $user->branchId())
            || ($refund->return_scope === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                && (int) $refund->buyer_id === (int) $user->branchId());

        abort_unless($belongsToBranch, 403);

        if ($manage
            && $refund->return_scope === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
            && ! $refund->isPendingApproval()) {
            abort(403);
        }
    }

    private function refundFormData(?Refund $refund = null, ?int $selectedPenjualanId = null): array
    {
        $prefillSale = $selectedPenjualanId ? Penjualan::find($selectedPenjualanId) : null;
        $converter = app(ProductUnitConverter::class);
        $user = auth()->user();
        $isAdminCabang = $user?->role === 'admin-cabang' && $user->isBranchScoped();
        $isSales = $user?->role === 'sales' && $user->isBranchScoped();
        $isBranchScoped = $isAdminCabang || $isSales;

        $products = Product::orderBy('name')
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
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'harga_jual' => (int) ($product->harga_jual ?? 0),
                'base_unit' => $product->satuan ?: 'PCS',
                'default_unit' => $converter->defaultInputUnit($product, $isBranchScoped ? 'branch' : 'distribution'),
                'unit_factors' => $converter->unitMultipliers($product),
                'units' => $converter->inputUnits($product, $isBranchScoped ? 'branch' : 'distribution'),
            ])
            ->values();

        $selectedReturnScope = old('return_scope', $refund?->return_scope ?: request()->input('return_scope') ?: request()->input('mode'));
        $selectedBuyerType = old('buyer_type', $refund?->buyer_type);
        $selectedBuyerId = old('buyer_id', $refund?->buyer_id);
        $selectedSourceOutletId = old('source_outlet_id', $refund?->source_outlet_id);

        if (! $refund && $prefillSale) {
            $selectedBuyerType = $prefillSale->buyer_type;
            $selectedBuyerId = $prefillSale->buyer_id;
            $selectedSourceOutletId = $prefillSale->outlet_id;
            $selectedReturnScope = $prefillSale->isBranchSale()
                ? SalesReturnManager::SCOPE_BRANCH_CUSTOMER
                : ($prefillSale->buyer_type === 'outlet'
                    ? SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                    : SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE);
        }

        if ($isSales) {
            $selectedReturnScope = SalesReturnManager::SCOPE_BRANCH_CUSTOMER;
            $selectedBuyerType = 'toko';
            $selectedSourceOutletId = $user->branchId();
        }

        if ($isAdminCabang) {
            $selectedReturnScope = $selectedReturnScope === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                ? SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                : SalesReturnManager::SCOPE_BRANCH_CUSTOMER;
            $selectedSourceOutletId = $user->branchId();

            if ($selectedReturnScope === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH) {
                $selectedBuyerType = 'outlet';
                $selectedBuyerId = $user->branchId();
            } else {
                $selectedBuyerType = 'toko';
            }
        }

        return [
            'refund' => $refund,
            'code' => old('code', $refund?->code ?? $this->generateReturnCode()),
            'tanggalValue' => old('tanggal', $refund?->tanggal?->format('Y-m-d') ?? now()->format('Y-m-d')),
            'selectedReturnScope' => $selectedReturnScope,
            'selectedBuyerType' => $selectedBuyerType,
            'selectedBuyerId' => $selectedBuyerId,
            'selectedSourceOutletId' => $selectedSourceOutletId,
            'isBranchScoped' => $isBranchScoped,
            'isAdminCabang' => $isAdminCabang,
            'isSales' => $isSales,
            'branchName' => $user?->outlet?->name,
            'branchReturnScopeOptions' => $this->returnScopeOptions(),
            'agents' => Agent::where('is_active', true)->orderBy('name')->get(),
            'canvases' => Canvas::where('is_active', true)->orderBy('name')->get(),
            'branches' => Outlet::branches()->orderBy('name')->get(),
            'shops' => Outlet::shops()->orderBy('name')->get(),
            'products' => $products,
            'initialItems' => old('product')
                ?: ($refund
                    ? $refund->refundItems->map(fn ($item) => [
                        'product_id' => (int) $item->product_id,
                        'qty' => (float) ($item->qty_input ?? $item->qty),
                        'unit' => $item->unit ?: $item->product?->satuan,
                        'price' => (int) $item->price,
                        'alasan' => $item->alasan,
                    ])->values()->all()
                    : []),
        ];
    }

    private function returnPayload(array $data): array
    {
        if (auth()->user()?->role === 'sales') {
            $data['salesman_id'] = Salesman::where('user_id', auth()->id())->value('id');
        }

        $data['requires_superadmin_approval'] = auth()->user()?->role === 'admin-cabang'
            && ($data['return_scope'] ?? null) === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH;

        return $data;
    }

    private function previewPayload(Request $request): array
    {
        $user = auth()->user();
        $buyerType = (string) $request->input('buyer_type');
        $isSales = $user?->role === 'sales' && $user->isBranchScoped();
        $isAdminCabang = $user?->role === 'admin-cabang' && $user->isBranchScoped();

        if ($isSales) {
            return [
                'return_scope' => SalesReturnManager::SCOPE_BRANCH_CUSTOMER,
                'buyer_type' => 'toko',
                'buyer_id' => (int) $request->input('buyer_id'),
                'source_outlet_id' => $user->branchId(),
            ];
        }

        if ($isAdminCabang) {
            $returnScope = $request->input('return_scope') === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                ? SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                : SalesReturnManager::SCOPE_BRANCH_CUSTOMER;

            if ($returnScope === SalesReturnManager::SCOPE_WAREHOUSE_BRANCH) {
                return [
                    'return_scope' => SalesReturnManager::SCOPE_WAREHOUSE_BRANCH,
                    'buyer_type' => 'outlet',
                    'buyer_id' => (int) $user->branchId(),
                    'source_outlet_id' => $user->branchId(),
                ];
            }

            return [
                'return_scope' => SalesReturnManager::SCOPE_BRANCH_CUSTOMER,
                'buyer_type' => 'toko',
                'buyer_id' => (int) $request->input('buyer_id'),
                'source_outlet_id' => $user->branchId(),
            ];
        }

        return [
            'return_scope' => $request->input('return_scope') ?: ($buyerType === 'outlet'
                ? SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                : SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE),
            'buyer_type' => $buyerType,
            'buyer_id' => (int) $request->input('buyer_id'),
            'source_outlet_id' => $request->input('source_outlet_id'),
        ];
    }

    private function buyerTypeOptions(): array
    {
        $user = auth()->user();

        if ($user?->role === 'sales') {
            return ['toko' => 'Customer/Toko'];
        }

        if ($user?->role === 'admin-cabang') {
            return [
                'toko' => 'Customer/Toko',
                'outlet' => 'Cabang',
            ];
        }

        return [
            'agent' => 'Agen',
            'canvas' => 'Canvas',
            'outlet' => 'Cabang',
            'toko' => 'Customer/Toko',
        ];
    }

    private function returnScopeOptions(): array
    {
        $user = auth()->user();

        if ($user?->role === 'sales') {
            return [
                SalesReturnManager::SCOPE_BRANCH_CUSTOMER => 'Retur Toko ke Cabang',
            ];
        }

        if ($user?->role === 'admin-cabang') {
            return [
                SalesReturnManager::SCOPE_BRANCH_CUSTOMER => 'Retur Toko ke Cabang',
                SalesReturnManager::SCOPE_WAREHOUSE_BRANCH => 'Retur Cabang ke Gudang',
            ];
        }

        return [
            SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE => 'Retur Agen / Canvas',
            SalesReturnManager::SCOPE_WAREHOUSE_BRANCH => 'Retur Cabang ke Gudang',
            SalesReturnManager::SCOPE_BRANCH_CUSTOMER => 'Retur Toko ke Cabang',
        ];
    }

    private function generateReturnCode(): string
    {
        $lastRefund = Refund::latest('id')->first();
        $nextNumber = $lastRefund ? ((int) substr((string) $lastRefund->code, 3) + 1) : 1;

        return 'RTR'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function refundableSale(int $penjualanId): Penjualan
    {
        return Penjualan::with(['items.allocations', 'items.product', 'customer', 'agent', 'canvasBuyer', 'outletBuyer'])
            ->findOrFail($penjualanId);
    }

    private function refundAttributes(Penjualan $penjualan, array $data): array
    {
        return [
            'code' => $data['code'],
            'kas_id' => empty($data['kas_id']) ? null : (int) $data['kas_id'],
            'customer_id' => $penjualan->customer_id,
            'penjualan_id' => $penjualan->id,
            'outlet_id' => $penjualan->buyer_type === 'outlet' ? $penjualan->buyer_id : $penjualan->outlet_id,
            'buyer_type' => $penjualan->buyer_type,
            'buyer_id' => $penjualan->buyer_id,
            'buyer_name' => $penjualan->buyer_display_name,
            'user_id' => auth()->id(),
            'tanggal' => $data['tanggal'],
            'total' => $this->normalizeMoney($data['total']),
        ];
    }

    private function normalizeRefundItems(Penjualan $penjualan, array $items, ?Refund $currentRefund = null): array
    {
        $saleItems = $penjualan->items->keyBy('product_id');
        $alreadyRefunded = RefundItem::query()
            ->selectRaw('refund_items.product_id, SUM(refund_items.qty) as qty')
            ->join('refunds', 'refunds.id', '=', 'refund_items.refund_id')
            ->where('refunds.penjualan_id', $penjualan->id)
            ->when($currentRefund, fn ($query) => $query->where('refunds.id', '!=', $currentRefund->id))
            ->groupBy('refund_items.product_id')
            ->pluck('qty', 'refund_items.product_id');

        $grouped = collect($items)
            ->filter(fn ($item) => ! empty($item['product_id']) && (int) ($item['qty'] ?? 0) > 0)
            ->groupBy(fn ($item) => (int) $item['product_id'])
            ->map(function ($rows, $productId) {
                return [
                    'product_id' => (int) $productId,
                    'qty' => (int) $rows->sum(fn ($row) => (int) $row['qty']),
                    'alasan' => $rows->pluck('alasan')->filter()->implode(' | '),
                ];
            })
            ->values();

        if ($grouped->isEmpty()) {
            throw new \RuntimeException('Minimal harus ada satu produk retur.');
        }

        return $grouped->map(function (array $item) use ($saleItems, $alreadyRefunded) {
            /** @var PenjualanItem|null $saleItem */
            $saleItem = $saleItems->get($item['product_id']);

            if (! $saleItem) {
                throw new \RuntimeException('Produk retur tidak ditemukan pada transaksi penjualan.');
            }

            $refundedQty = (int) ($alreadyRefunded[$item['product_id']] ?? 0);
            $maxQty = max(0, (int) $saleItem->qty - $refundedQty);

            if ($item['qty'] > $maxQty) {
                throw new \RuntimeException("Qty retur {$saleItem->product?->name} melebihi sisa qty yang bisa diretur.");
            }

            return $item;
        })->all();
    }

    private function applyRefundImpact(Refund $refund, Penjualan $penjualan): void
    {
        $refund->loadMissing('refundItems.product');
        $penjualan->loadMissing('items.allocations', 'items.product');

        foreach ($refund->refundItems as $refundItem) {
            /** @var PenjualanItem|null $saleItem */
            $saleItem = $penjualan->items->firstWhere('product_id', $refundItem->product_id);
            if (! $saleItem) {
                throw new \RuntimeException('Item penjualan untuk retur tidak ditemukan.');
            }

            $restored = $this->restoreReturnedStock($saleItem, (int) $refundItem->qty, $refund->code);

            if ($penjualan->buyer_type === 'outlet') {
                $this->decreaseOutletOwnerStock((int) $penjualan->buyer_id, (int) $refundItem->product_id, $restored);
            }

            StockMovement::create([
                'product_id' => $refundItem->product_id,
                'user_id' => auth()->id(),
                'type' => 'in',
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'qty_in' => (int) $refundItem->qty,
                'qty_out' => 0,
                'balance' => (int) Stock::where('product_id', $refundItem->product_id)->sum('qty'),
                'notes' => "Retur penjualan {$refund->code} - {$refund->buyer_display_name} - Produk: {$refundItem->product?->name}",
            ]);
        }
    }

    private function rollbackRefundImpact(Refund $refund): void
    {
        $refund->loadMissing('refundItems.product', 'penjualan.items.allocations', 'penjualan.items.product');
        $penjualan = $refund->penjualan;

        foreach ($refund->refundItems as $refundItem) {
            /** @var PenjualanItem|null $saleItem */
            $saleItem = $penjualan?->items->firstWhere('product_id', $refundItem->product_id);
            if (! $saleItem) {
                continue;
            }

            $removed = $this->removeReturnedStock($saleItem, (int) $refundItem->qty);

            if ($penjualan->buyer_type === 'outlet') {
                $this->increaseOutletOwnerStock((int) $penjualan->buyer_id, (int) $refundItem->product_id, $removed);
            }
        }

        StockMovement::where('reference_type', Refund::class)
            ->where('reference_id', $refund->id)
            ->delete();

        if ($refund->kas_id) {
            $kas = Kas::find($refund->kas_id);
            if ($kas) {
                $kas->nominal = max(0, (int) $kas->nominal - (int) $refund->total);
                $kas->save();
            }
        }
    }

    private function restoreReturnedStock(PenjualanItem $saleItem, int $qty, string $refundCode): array
    {
        $remaining = $qty;
        $restored = [];

        foreach ($saleItem->allocations as $allocation) {
            if ($remaining <= 0) {
                break;
            }

            $stock = Stock::whereKey($allocation->stock_id)->lockForUpdate()->first();
            if (! $stock) {
                continue;
            }

            $restoreQty = min($remaining, (int) $allocation->qty);
            if ($restoreQty <= 0) {
                continue;
            }

            $stock->qty += $restoreQty;
            $stock->save();

            $restored[] = [
                'stock' => $stock,
                'qty' => $restoreQty,
            ];

            $remaining -= $restoreQty;
        }

        if ($remaining > 0 && $saleItem->stock_id) {
            $stock = Stock::whereKey($saleItem->stock_id)->lockForUpdate()->first();
            if ($stock) {
                $stock->qty += $remaining;
                $stock->save();

                $restored[] = [
                    'stock' => $stock,
                    'qty' => $remaining,
                ];

                $remaining = 0;
            }
        }

        if ($remaining > 0) {
            $product = $saleItem->product ?: Product::findOrFail($saleItem->product_id);
            $stock = Stock::create([
                'product_id' => $product->id,
                'sku' => $product->code.'-RETUR-'.$refundCode,
                'subtotal' => $remaining * (int) ($product->harga_beli ?? 0),
                'harga_beli' => (int) ($product->harga_beli ?? 0),
                'qty' => $remaining,
                'qty_reserved' => 0,
                'expired_at' => null,
                'location' => $product->lokasi,
                'condition' => 'used',
                'status' => 'available',
            ]);

            $restored[] = [
                'stock' => $stock,
                'qty' => $remaining,
            ];
        }

        return $restored;
    }

    private function removeReturnedStock(PenjualanItem $saleItem, int $qty): array
    {
        $stocks = Stock::where('product_id', $saleItem->product_id)
            ->where('status', 'available')
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $qty;
        $removed = [];

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $stock->qty);
            if ($take <= 0) {
                continue;
            }

            $stock->qty -= $take;
            $stock->save();

            $removed[] = [
                'stock' => $stock,
                'qty' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Stok hasil retur tidak cukup untuk dibatalkan.');
        }

        return $removed;
    }

    private function decreaseOutletOwnerStock(int $outletId, int $productId, array $restoredStocks): void
    {
        $remaining = collect($restoredStocks)->sum('qty');

        foreach ($restoredStocks as $entry) {
            if ($remaining <= 0) {
                break;
            }

            $taken = $this->decreaseOwnerStock($outletId, $productId, (int) $entry['qty'], $entry['stock']->id);
            $remaining -= $taken;
        }

        if ($remaining > 0) {
            $remaining -= $this->decreaseOwnerStock($outletId, $productId, $remaining);
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Stok cabang hasil penjualan tidak cukup untuk diretur.');
        }
    }

    private function increaseOutletOwnerStock(int $outletId, int $productId, array $removedStocks): void
    {
        foreach ($removedStocks as $entry) {
            $this->increaseOwnerStock($outletId, $entry['stock'], (int) $entry['qty'], $productId);
        }
    }

    private function increaseOwnerStock(int $outletId, Stock $stock, int $qty, int $productId): void
    {
        $ownerStock = OwnerStock::where('owner_id', $outletId)
            ->where('product_id', $productId)
            ->where('stock_id', $stock->id)
            ->lockForUpdate()
            ->first();

        if ($ownerStock) {
            $ownerStock->qty += $qty;
            $ownerStock->sku = $stock->sku;
            $ownerStock->expired_at = $stock->expired_at;
            $ownerStock->harga_beli = $stock->harga_beli;
            $ownerStock->save();

            return;
        }

        OwnerStock::create([
            'owner_id' => $outletId,
            'product_id' => $productId,
            'stock_id' => $stock->id,
            'qty' => $qty,
            'sku' => $stock->sku,
            'expired_at' => $stock->expired_at,
            'harga_beli' => $stock->harga_beli,
        ]);
    }

    private function decreaseOwnerStock(int $outletId, int $productId, int $qty, ?int $stockId = null): int
    {
        $remaining = $qty;
        $stocks = OwnerStock::where('owner_id', $outletId)
            ->where('product_id', $productId)
            ->when($stockId, fn ($query) => $query->where('stock_id', $stockId))
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($stocks as $ownerStock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $ownerStock->qty);
            if ($take <= 0) {
                continue;
            }

            $ownerStock->qty -= $take;
            $ownerStock->save();
            $remaining -= $take;
        }

        return $qty - $remaining;
    }
}
