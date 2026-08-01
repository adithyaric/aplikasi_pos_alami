<?php

namespace App\Http\Controllers;

use App\Models\Kas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\RefundPembelian;
use App\Models\RefundPembelianItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RefundPembelianController extends Controller
{
    // -----------------------------------------------------------------------
    // AJAX Helpers
    // -----------------------------------------------------------------------

    /**
     * Warehouse stocks (Stock) for a supplier.
     */
    public function getSupplierProducts(Supplier $supplier)
    {
        $stocks = Stock::whereHas('pembelian', fn ($q) => $q->where('supplier_id', $supplier->id))
            ->where('qty_available', '>', 0)
            ->with(['product', 'pembelian'])
            ->get();

        $grouped = $stocks->groupBy('product_id')
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product->name,
                    'qty_available' => (int) $group->sum('qty_available'),
                    'harga_beli' => (int) $first->harga_beli,
                    'stock_breakdown' => $group->map(fn ($stock) => [
                        'stock_id' => $stock->id,
                        'qty_available' => (int) $stock->qty_available,
                    ])->values()->all(),
                ];
            })
            ->sortBy('product_name')
            ->values();

        return response()->json($grouped);
    }

    /**
     * All outlet stocks for an outlet (for retur, showing DO source).
     */
    public function getOutletProducts(Outlet $outlet)
    {
        $user = auth()->user();
        if ($this->isBranchOutletUser($user) && (int) $outlet->id !== (int) $user->outlet_id) {
            abort(403);
        }

        $ownerStocks = OwnerStock::where('owner_id', $outlet->id)
            ->where('qty', '>', 0)
            ->with(['product', 'stock'])
            ->get();

        $grouped = $ownerStocks->groupBy('product_id')
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'product_id' => $first->product_id,
                    'product_name' => $first->product->name,
                    'qty_available' => (int) $group->sum('qty'),
                    'stock_breakdown' => $group->map(fn ($ownerStock) => [
                        'stock_id' => $ownerStock->stock_id,
                        'qty_available' => (int) $ownerStock->qty,
                    ])->values()->all(),
                ];
            })
            ->sortBy('product_name')
            ->values();

        return response()->json($grouped);
    }

    // -----------------------------------------------------------------------
    // Resource
    // -----------------------------------------------------------------------

    public function index(Request $request)
    {
        $user    = auth()->user();
        $isStaff = $this->isBranchOutletUser($user);
        $selectedType = $isStaff
            ? 'outlet_ke_gudang'
            : ($request->query('type') === 'outlet_ke_gudang' ? 'outlet_ke_gudang' : 'gudang_ke_supplier');
        $filterPeriod = $request->input('period', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = RefundPembelian::with('user', 'supplier', 'outlet')->latest();

        if ($isStaff) {
            $query->where('type', 'outlet_ke_gudang')
                ->where('outlet_id', $user->outlet_id);
        } else {
            $query->where('type', $selectedType);

            if ($selectedType === 'outlet_ke_gudang' && $request->filled('outlet_id')) {
                $query->where('outlet_id', $request->outlet_id);
            }
        }

        if ($filterPeriod === 'daterange' && $dateFrom && $dateTo) {
            $query->whereBetween('tanggal', [
                \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                \Carbon\Carbon::parse($dateTo)->endOfDay(),
            ]);
        }

        $refundPembelians = $query->get();
        $summary = [
            'totalNominal' => $refundPembelians->sum('total'),
            'totalCount' => $refundPembelians->count(),
            'completeNominal' => $refundPembelians->where('status', 'complete')->sum('total'),
            'completeCount' => $refundPembelians->where('status', 'complete')->count(),
            'returNominal' => $refundPembelians->where('status', 'retur')->sum('total'),
            'returCount' => $refundPembelians->where('status', 'retur')->count(),
            'branchReturnCount' => $refundPembelians->where('type', 'outlet_ke_gudang')->count(),
            'supplierReturnCount' => $refundPembelians->where('type', 'gudang_ke_supplier')->count(),
        ];

        return view('refundPembelians.index', [
            'refundPembelians' => $refundPembelians,
            'selectedType'     => $selectedType,
            'selectedOutletId' => $isStaff ? $user->outlet_id : ($selectedType === 'outlet_ke_gudang' ? $request->outlet_id : null),
            'outlets'          => $isStaff
                ? Outlet::whereKey($user->outlet_id)->get()
                : Outlet::branches()->orderBy('name')->get(),
            'isStaffOutlet'    => $isStaff,
            'filterPeriod' => $filterPeriod,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'summary' => $summary,
            'canCreateRetur' => ! ($isStaff && $user?->role === 'staff-outlet'),
            'userRole' => $user?->role,
        ]);
    }

    public function create(Request $request)
    {
        $lastRetur  = RefundPembelian::latest('id')->first();
        $nextNumber = $lastRetur ? ((int) substr($lastRetur->code, 3) + 1) : 1;
        $code       = 'RTR'.str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $user          = auth()->user();
        $isStaffOutlet = $this->isBranchOutletUser($user);
        $selectedType  = $isStaffOutlet
            ? 'outlet_ke_gudang'
            : ($request->query('type') === 'outlet_ke_gudang' ? 'outlet_ke_gudang' : 'gudang_ke_supplier');

        return view('refundPembelians.create', [
            'suppliers'     => Supplier::get(),
            'outlets'       => $isStaffOutlet
                ? Outlet::whereKey($user->outlet_id)->get()
                : Outlet::branches()->get(),
            'code'          => $code,
            'isStaffOutlet' => $isStaffOutlet,
            'staffOutletId' => $isStaffOutlet ? $user->outlet_id : null,
            'selectedType'  => $selectedType,
        ]);
    }

    private function normalizeMoney($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isBranchOutletUser = $this->isBranchOutletUser($user);

        if ($isBranchOutletUser) {
            $request->merge([
                'type' => 'outlet_ke_gudang',
                'outlet_id' => $user->outlet_id,
                'return_mode' => 'replacement',
            ]);
        }

        $type = $request->input('type');
        $returnMode = $request->input('return_mode', 'replacement');
        $selectedRows = collect($request->input('selected_rows', []))
            ->map(fn ($row) => (string) $row)
            ->filter()
            ->values();
        $selectedProducts = collect($request->input('product', []))
            ->filter(function ($product, $key) use ($selectedRows) {
                return $selectedRows->isEmpty() || $selectedRows->contains((string) $key);
            })
            ->values()
            ->all();

        $rules = [
            'code'    => 'required|string|unique:refund_pembelians,code',
            'tanggal' => 'required|date',
            'type'    => 'required|in:gudang_ke_supplier,outlet_ke_gudang',
            'return_mode' => 'nullable|in:replacement,cash_refund',
            'product' => 'required|array|min:1',
            'product.*.product_id' => 'required|exists:products,id',
            'product.*.qty'        => 'required|integer|min:1',
            'product.*.alasan'     => 'required|string',
            'product.*.stock_breakdown' => 'nullable|string',
            'product.*.stock_id'   => 'nullable|exists:stocks,id',
        ];

        if ($type === 'gudang_ke_supplier') {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        } elseif ($type === 'outlet_ke_gudang') {
            $rules['outlet_id'] = 'required|exists:outlets,id';
        }

        $request->validate($rules, [
            'code.required'    => 'Kode refund wajib diisi.',
            'code.unique'      => 'Kode refund sudah terdaftar.',
            'tanggal.required' => 'Tanggal harus dipilih.',
            'type.required'    => 'Tipe refund wajib dipilih.',
            'supplier_id.required' => 'Supplier wajib diisi untuk retur Gudang ke Supplier.',
            'outlet_id.required'   => 'Cabang wajib diisi untuk retur Cabang ke Gudang.',
            'product.required'     => 'Minimal harus ada satu produk.',
            'product.min'          => 'Minimal harus ada satu produk.',
            'product.*.product_id.required' => 'ID Produk tidak valid.',
            'product.*.qty.min'    => 'Jumlah barang minimal 1.',
            'product.*.alasan.required' => 'Alasan retur wajib diisi.',
        ]);

        DB::beginTransaction();
        try {
            $isOutlet = $request->type === 'outlet_ke_gudang';
            $isReplacement = ! $isOutlet && $returnMode === 'replacement';
            $total    = 0;

            if (empty($selectedProducts)) {
                throw new \Exception('Pilih minimal satu baris retur yang dicentang.');
            }

            $refundPembelian = RefundPembelian::create([
                'code'              => $request->code,
                'tanggal'           => $request->tanggal,
                'type'              => $request->type,
                'return_mode'       => $isOutlet ? 'replacement' : $returnMode,
                'status'            => ($isOutlet || $isReplacement) ? 'complete' : 'retur',
                'supplier_id'       => $request->supplier_id,
                'outlet_id'         => $request->outlet_id,
                'delivery_order_id' => $request->delivery_order_id,
                'user_id'           => auth()->id(),
                'total'             => 0,
            ]);

            foreach ($selectedProducts as $product) {
                $requestedQty = (int) $product['qty'];
                $breakdown = $this->parseStockBreakdown(
                    $product['stock_breakdown'] ?? null,
                    $product['stock_id'] ?? null
                );
                $allocations = $type === 'gudang_ke_supplier'
                    ? $this->allocateSupplierStocks($breakdown, $requestedQty, 'Stok gudang tidak mencukupi.')
                    : $this->allocateOutletStocks($breakdown, (int) $request->outlet_id, $requestedQty, 'Stok cabang tidak mencukupi.');

                if (! $isOutlet) {
                    // ── Gudang ke Supplier ──────────────────────────────────────
                    $firstStock = Stock::findOrFail($allocations[0]['stock_id']);
                    $harga  = $this->normalizeMoney($product['harga'] ?? $firstStock->harga_beli);
                    $total += $harga * $requestedQty;

                    if ($isReplacement) {
                        StockMovement::create([
                            'product_id'     => $product['product_id'],
                            'user_id'        => auth()->id(),
                            'type'           => 'adjustment',
                            'reference_type' => RefundPembelian::class,
                            'reference_id'   => $refundPembelian->id,
                            'qty_in'         => 0,
                            'qty_out'        => 0,
                            'balance'        => Stock::where('product_id', $product['product_id'])->sum('qty'),
                            'notes'          => "Retur replacement supplier - {$refundPembelian->code} - Produk: {$firstStock->product->name} - Alasan: {$product['alasan']}",
                        ]);
                    }

                    foreach ($allocations as $allocation) {
                        $stock = Stock::whereKey($allocation['stock_id'])->lockForUpdate()->firstOrFail();

                        if (! $isReplacement) {
                            if ($this->resolveStockAvailableQty($stock) < $allocation['qty']) {
                                throw new \Exception("Stok gudang tidak mencukupi untuk: {$stock->product->name}");
                            }

                            $stock->qty -= $allocation['qty'];
                            $stock->save();

                            StockMovement::create([
                                'product_id'     => $product['product_id'],
                                'user_id'        => auth()->id(),
                                'type'           => 'out',
                                'reference_type' => RefundPembelian::class,
                                'reference_id'   => $refundPembelian->id,
                                'qty_in'         => 0,
                                'qty_out'        => $allocation['qty'],
                                'balance'        => $stock->qty,
                                'notes'          => "Retur cash refund supplier - {$refundPembelian->code} - Produk: {$stock->product->name} - Alasan: {$product['alasan']}",
                            ]);
                        }

                        RefundPembelianItem::create([
                            'refund_pembelian_id' => $refundPembelian->id,
                            'product_id'          => $product['product_id'],
                            'stock_id'            => $stock->id,
                            'sku'                 => $stock->sku,
                            'qty'                 => $allocation['qty'],
                            'harga'               => $harga,
                            'alasan'              => $product['alasan'],
                            'resolution'          => $isReplacement ? 'barang' : null,
                        ]);
                    }
                } else {
                    // ── Outlet ke Gudang ─────────────────────────────────────────
                    foreach ($allocations as $allocation) {
                        $stock = Stock::whereKey($allocation['stock_id'])->lockForUpdate()->firstOrFail();
                        $ownerStock = OwnerStock::where('stock_id', $stock->id)
                            ->where('owner_id', $request->outlet_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $ownerStock || $ownerStock->qty < $allocation['qty']) {
                            throw new \Exception("Stok cabang tidak mencukupi untuk: {$stock->product->name}");
                        }

                        $ownerStock->qty -= $allocation['qty'];
                        $ownerStock->save();

                        OwnerStockMovement::create([
                            'owner_id' => $request->outlet_id,
                            'product_id' => $product['product_id'],
                            'owner_stock_id' => $ownerStock->id,
                            'stock_id' => $stock->id,
                            'user_id' => auth()->id(),
                            'type' => 'return_out',
                            'reference_type' => RefundPembelian::class,
                            'reference_id' => $refundPembelian->id,
                            'qty_in' => 0,
                            'qty_out' => $allocation['qty'],
                            'balance' => OwnerStock::where('owner_id', $request->outlet_id)
                                ->where('product_id', $product['product_id'])
                                ->sum('qty'),
                            'notes' => "Retur cabang ke gudang - {$refundPembelian->code} - Produk: {$stock->product->name}",
                        ]);

                        $stock->qty += $allocation['qty'];
                        $stock->save();

                        StockMovement::create([
                            'product_id'     => $product['product_id'],
                            'user_id'        => auth()->id(),
                            'type'           => 'in',
                            'reference_type' => RefundPembelian::class,
                            'reference_id'   => $refundPembelian->id,
                            'qty_in'         => $allocation['qty'],
                            'qty_out'        => 0,
                            'balance'        => $stock->qty,
                            'notes'          => "Retur cabang ke gudang - {$refundPembelian->code} - Produk: {$stock->product->name} - Alasan: {$product['alasan']}",
                        ]);

                        RefundPembelianItem::create([
                            'refund_pembelian_id' => $refundPembelian->id,
                            'product_id'          => $product['product_id'],
                            'stock_id'            => $stock->id,
                            'sku'                 => $stock->sku,
                            'qty'                 => $allocation['qty'],
                            'harga'               => $stock->harga_beli,
                            'alasan'              => $product['alasan'],
                            'resolution'          => 'barang',
                        ]);
                    }
                }
            }

            $refundPembelian->update(['total' => $total]);

            DB::commit();

            return redirect(route('refundPembelian.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()->with('toast_error', 'Gagal: '.$e->getMessage());
        }
    }

    public function show(RefundPembelian $refundPembelian)
    {
        return view('refundPembelians.show', [
            'refundPembelian' => $refundPembelian->load('supplier', 'outlet', 'deliveryOrder', 'user', 'refundPembelianItems.product', 'refundPembelianItems.stock'),
            'groupedItems' => $refundPembelian->groupedRefundPembelianItems(),
        ]);
    }

    public function edit(RefundPembelian $refundPembelian)
    {
        if ($refundPembelian->status === 'complete') {
            return redirect()->route('refundPembelian.show', $refundPembelian)
                ->with('toast_error', 'Data yang sudah complete tidak dapat diedit.');
        }

        return view('refundPembelians.edit', [
            'refundPembelian' => $refundPembelian->load('refundPembelianItems.product'),
            'suppliers'       => Supplier::get(),
            'outlets'         => Outlet::get(),
        ]);
    }

    public function update(Request $request, RefundPembelian $refundPembelian)
    {
        if ($refundPembelian->status === 'complete') {
            return redirect()->route('refundPembelian.show', $refundPembelian)
                ->with('toast_error', 'Data yang sudah complete tidak dapat diedit.');
        }

        $request->validate([
            'code'    => 'required|string|unique:refund_pembelians,code,'.$refundPembelian->id,
            'tanggal' => 'required|date',
        ], [
            'code.required' => 'Kode harus diisi.',
            'code.unique'   => 'Kode sudah digunakan.',
            'tanggal.date'  => 'Format tanggal tidak valid.',
        ]);

        $refundPembelian->update([
            'code'    => $request->code,
            'tanggal' => $request->tanggal,
        ]);

        return redirect()->route('refundPembelian.show', $refundPembelian)
            ->with('toast_success', 'Berhasil Update Data!');
    }

    public function destroy(RefundPembelian $refundPembelian)
    {
        if ($refundPembelian->status === 'complete') {
            return redirect()->route('refundPembelian.index')
                ->with('toast_error', 'Data yang sudah complete tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            if ($refundPembelian->type === 'gudang_ke_supplier' && ! $refundPembelian->isReplacement()) {
                // Reverse stock reduction (only for retur status)
                foreach ($refundPembelian->refundPembelianItems as $item) {
                    $stock = Stock::find($item->stock_id);
                    if ($stock) {
                        $stock->qty += $item->qty;
                        $stock->save();
                    }
                }
            }

            $refundPembelian->delete();
            DB::commit();

            return redirect()->route('refundPembelian.index')->with('toast_success', 'Berhasil Menghapus Data!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('toast_error', 'Gagal: '.$e->getMessage());
        }
    }

    // -----------------------------------------------------------------------
    // Terima Retur (hanya untuk gudang_ke_supplier)
    // -----------------------------------------------------------------------

    public function terimaForm(RefundPembelian $refundPembelian)
    {
        if ($refundPembelian->type !== 'gudang_ke_supplier' || $refundPembelian->status !== 'retur') {
            return redirect()->route('refundPembelian.show', $refundPembelian)
                ->with('toast_error', 'Data tidak dapat diproses.');
        }

        return view('refundPembelians.terima', [
            'refundPembelian' => $refundPembelian->load('refundPembelianItems.product', 'refundPembelianItems.stock', 'supplier'),
            'groupedItems'    => $refundPembelian->groupedRefundPembelianItems(),
            'kasList'         => Kas::get(),
        ]);
    }

    public function terima(Request $request, RefundPembelian $refundPembelian)
    {
        if ($refundPembelian->type !== 'gudang_ke_supplier' || $refundPembelian->status !== 'retur') {
            return redirect()->route('refundPembelian.show', $refundPembelian)
                ->with('toast_error', 'Data tidak dapat diproses.');
        }

        $request->validate([
            'items'              => 'required|array',
            'items.*.resolution' => 'required|in:barang,uang',
            'kas_id'             => 'nullable|exists:kas,id',
        ], [
            'items.required'           => 'Daftar item tidak boleh kosong.',
            'items.*.resolution.in'    => 'Resolusi harus berupa barang atau uang.',
            'items.*.resolution.required' => 'Resolusi setiap item wajib dipilih.',
            'kas_id.exists'            => 'Kas yang dipilih tidak terdaftar.',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $itemIds => $itemData) {
                $resolution = $itemData['resolution'];
                $ids = collect(explode(',', (string) $itemIds))
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->values();

                foreach ($ids as $itemId) {
                    $item = RefundPembelianItem::findOrFail($itemId);
                    $item->update(['resolution' => $resolution]);

                    if ($resolution === 'barang') {
                        $stock = Stock::find($item->stock_id);
                        if ($stock) {
                            $stock->qty += $item->qty;
                            $stock->save();
                            $newBalance = $stock->qty;
                        } else {
                            $newBalance = $item->qty;
                        }

                        StockMovement::create([
                            'product_id'     => $item->product_id,
                            'user_id'        => auth()->id(),
                            'type'           => 'in',
                            'reference_type' => RefundPembelian::class,
                            'reference_id'   => $refundPembelian->id,
                            'qty_in'         => $item->qty,
                            'qty_out'        => 0,
                            'balance'        => $newBalance,
                            'notes'          => "Terima retur barang - {$refundPembelian->code} - Produk: {$item->product->name} - Alasan: {$item->alasan}",
                        ]);
                    }
                }
            }

            // if ($uangTotal > 0 && $request->kas_id) {
            //     $kas           = Kas::findOrFail($request->kas_id);
            //     $kas->nominal += $uangTotal;
            //     $kas->save();

            //     $refundPembelian->update(['kas_id' => $request->kas_id]);
            // }

            $refundPembelian->update(['status' => 'complete']);

            DB::commit();

            return redirect()->route('refundPembelian.show', $refundPembelian)
                ->with('toast_success', 'Penerimaan retur berhasil diselesaikan!');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('toast_error', 'Gagal: '.$e->getMessage());
        }
    }

    private function parseStockBreakdown($breakdown, $stockId = null): array
    {
        if (blank($breakdown) && $stockId) {
            return [[
                'stock_id' => (int) $stockId,
            ]];
        }

        $decoded = json_decode((string) $breakdown, true);

        if (! is_array($decoded) || empty($decoded)) {
            throw new \Exception('Data stok retur tidak valid.');
        }

        return collect($decoded)
            ->map(function ($row) {
                return [
                    'stock_id' => (int) ($row['stock_id'] ?? 0),
                ];
            })
            ->filter(fn ($row) => $row['stock_id'] > 0)
            ->values()
            ->all();
    }

    private function allocateSupplierStocks(array $breakdown, int $requestedQty, string $message): array
    {
        $stocks = Stock::whereIn('id', collect($breakdown)->pluck('stock_id')->all())
            ->get()
            ->keyBy('id');

        $remaining = $requestedQty;
        $allocations = [];

        foreach ($breakdown as $row) {
            if ($remaining <= 0) {
                break;
            }

            $stock = $stocks->get((int) $row['stock_id']);
            if (! $stock) {
                continue;
            }

            $availableQty = max(0, $this->resolveStockAvailableQty($stock));
            $take = min($remaining, $availableQty);

            if ($take <= 0) {
                continue;
            }

            $allocations[] = [
                'stock_id' => (int) $stock->id,
                'qty' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception($message);
        }

        return $allocations;
    }

    private function allocateOutletStocks(array $breakdown, int $outletId, int $requestedQty, string $message): array
    {
        $ownerStocks = OwnerStock::where('owner_id', $outletId)
            ->whereIn('stock_id', collect($breakdown)->pluck('stock_id')->all())
            ->get()
            ->keyBy('stock_id');

        $remaining = $requestedQty;
        $allocations = [];

        foreach ($breakdown as $row) {
            if ($remaining <= 0) {
                break;
            }

            $ownerStock = $ownerStocks->get((int) $row['stock_id']);
            if (! $ownerStock) {
                continue;
            }

            $take = min($remaining, max(0, (int) $ownerStock->qty));
            if ($take <= 0) {
                continue;
            }

            $allocations[] = [
                'stock_id' => (int) $row['stock_id'],
                'qty' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \Exception($message);
        }

        return $allocations;
    }

    private function resolveStockAvailableQty(Stock $stock): int
    {
        if ($stock->qty_available !== null) {
            return (int) $stock->qty_available;
        }

        return max(0, (int) $stock->qty - (int) ($stock->qty_reserved ?? 0));
    }

    private function isBranchOutletUser($user): bool
    {
        return $user
            && in_array($user->role, ['admin-cabang', 'staff-outlet'], true)
            && $user->outlet_id;
    }
}
