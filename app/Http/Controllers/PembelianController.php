<?php

namespace App\Http\Controllers;

use App\Http\Requests\PembelianRequest;
use App\Models\CustomerPo;
use App\Models\Kas;
use App\Models\Outlet;
use App\Models\Pembelian;
use App\Models\PembelianProduct;
use App\Models\PembelianTransaction;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockPembelian;
use App\Models\Supplier;
use App\Support\ProductUnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    public function getPembelian($outlet_id)
    {
        $pembelians = Pembelian::where('outlet_id', $outlet_id)->get();

        return response()->json($pembelians);
    }

    public function getItems($pembelian_id)
    {
        $pembelian = Pembelian::find($pembelian_id);
        if ($pembelian) {
            $items = $pembelian->stocks;

            return response()->json($items);
        } else {
            return response()->json([], 404);
        }
    }

    public function getProductsBySupplier(Supplier $supplier)
    {
        $products = $supplier->products()
            ->select('products.id', 'code', 'name', 'is_serialized', 'harga_beli', 'konversi_qty', 'satuan_besar', 'satuan')
            ->get()
            ->map(function ($product) {
                $product->stock_count = $product->stocks()->sum('qty_available');

                return $product;
            });

        return response()->json($products);
    }

    public function getAllProducts()
    {
        $products = Product::select('id', 'code', 'name', 'is_serialized', 'harga_beli', 'min_stock', 'konversi_qty', 'satuan_besar', 'satuan')
            ->withSum('stocks', 'qty_available')
            ->orderBy('name');

        if (request()->filled('supplier_id')) {
            $supplierId = request()->integer('supplier_id');
            $products->whereHas('suppliers', fn ($query) => $query->where('suppliers.id', $supplierId));
        }

        $products = $products->get()
            ->map(function ($product) {
                $currentStock = (int) ($product->stocks_sum_qty_available ?? 0);
                $effectiveMin = $product->effective_min_stock;   // ← compute once
                $product->stock_count      = $currentStock;
                $product->effective_min    = $effectiveMin;      // ← expose as 'effective_min'
                $product->is_under_minimum = $currentStock <= $effectiveMin;

                return $product;
            });

        return response()->json($products);
    }

    public function index(Request $request)
    {
        $filterPeriod = $request->input('period', 'all');
        $dateFrom     = $request->input('date_from');
        $dateTo       = $request->input('date_to');
        $supplierId   = $request->integer('supplier_id') ?: null;

        $query = Pembelian::with([
            'supplier',
            'pembelianProducts.product',
            'pembelianTransaction',
            'ownerApprovedBy',
        ])->latest();

        // Apply filter periode
        if ($filterPeriod === 'hari') {
            $query->whereDate('created_at', today());
        } elseif ($filterPeriod === 'minggu') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filterPeriod === 'bulan') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        } elseif ($filterPeriod === 'daterange' && $dateFrom && $dateTo) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($dateFrom)->startOfDay(),
                \Carbon\Carbon::parse($dateTo)->endOfDay(),
            ]);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $pembelians = $query->get();

        // Hitung summary cards
        $totalPiutang     = $pembelians->filter(fn ($p) => ($p->pembelianTransaction?->status ?? 'unpaid') !== 'paid')
            ->sum(fn ($p) => $p->total - ($p->pembelianTransaction?->amount ?? 0));
        $countPiutang     = $pembelians->filter(fn ($p) => ($p->pembelianTransaction?->status ?? 'unpaid') !== 'paid')->count();

        $totalLunas       = $pembelians->filter(fn ($p) => $p->pembelianTransaction?->status === 'paid')
            ->sum(fn ($p) => $p->pembelianTransaction?->amount ?? 0);
        $countLunas       = $pembelians->filter(fn ($p) => $p->pembelianTransaction?->status === 'paid')->count();

        $totalTransaksi   = $pembelians->count();
        $totalTransaksiNominal = $pembelians->sum('total');

        $allProductsArr = [];

        foreach ($pembelians as $p) {
            foreach ($p->pembelianProducts as $pp) {
                $productId = $pp->product_id;

                if (isset($allProductsArr[$productId])) {
                    $allProductsArr[$productId]['qty'] += $pp->qty;
                } else {
                    $allProductsArr[$productId] = [
                        'product_id'            => $productId,
                        'product'               => $pp->product,
                        'qty'                   => $pp->qty,
                        'konversi_qty'          => $pp->product->konversi_qty,
                        'satuan'                => $pp->product->satuan,
                        'satuan_besar'          => $pp->product->satuan_besar,
                        'konversi_qty_terbesar' => $pp->product->konversi_qty_terbesar,
                        'satuan_terbesar'       => $pp->product->satuan_terbesar,
                    ];
                }
            }
        }

        // Konversi ke Collection setelah selesai
        $allProducts = collect(array_values($allProductsArr));

        $totalQtyItems = $allProducts->count();
        $totalQtyLines = $pembelians->sum(fn ($p) => $p->pembelianProducts->count());

        return view('pembelians.index', [
            'pembelians'          => $pembelians,
            'filterPeriod'        => $filterPeriod,
            'dateFrom'            => $dateFrom,
            'dateTo'              => $dateTo,
            'supplierId'          => $supplierId,
            'suppliers'           => Supplier::orderBy('name')->get(['id', 'name']),
            'totalPiutang'        => $totalPiutang,
            'countPiutang'        => $countPiutang,
            'totalLunas'          => $totalLunas,
            'countLunas'          => $countLunas,
            'totalTransaksi'      => $totalTransaksi,
            'totalTransaksiNominal' => $totalTransaksiNominal,
            'totalQtyItems'       => $totalQtyItems,
            'totalQtyLines'       => $totalQtyLines,
            'allProducts'         => $allProducts,
        ]);
    }

    public function create()
    {
        $supplierId = request('supplier_id');
        $supplier = $supplierId ? Supplier::find($supplierId) : null;
        $code = $supplier?->generateNextPoCode() ?: $this->generateDefaultPoCode();

        return view('pembelians.create', [
            'suppliers' => Supplier::get(),
            'products' => collect(),
            'code' => $code
        ]);
    }

    public function store(PembelianRequest $request)
    {
        if (auth()->user()->role === 'owner') {
            abort(403);
        }

        $offlineClientId = trim((string) $request->input('offline_client_id', '')) ?: null;

        if ($offlineClientId) {
            $existing = Pembelian::withTrashed()
                ->where('offline_client_id', $offlineClientId)
                ->first();

            if ($existing) {
                return $this->offlineStoreResponse($existing, false);
            }
        }

        $request->validated();
        $pembelian = DB::transaction(function () use ($request, $offlineClientId) {
            $supplier = Supplier::whereKey($request->supplier_id)
                ->lockForUpdate()
                ->firstOrFail();
            $code = trim((string) $request->code);
            $customerPo = $this->syncCustomerPoMaster($request->customer_po);

            $pembelian = Pembelian::create([
                'code' => $offlineClientId ? $supplier->generateNextPoCode() : ($code !== '' ? $code : $supplier->generateNextPoCode()),
                'offline_client_id' => $offlineClientId,
                'customer_po' => $customerPo,
                // 'outlet_id' => $request->outlet_id,
                'supplier_id' => $request->supplier_id,
                // 'kas_id' => $request->kas_id,
                'total' => $request->total,
                'is_published' => false,
                'owner_approval_status' => 'approved',
                'owner_approved_by' => null,
                'owner_approved_at' => null,
                'owner_approval_note' => null,
            ]);

            $this->updateStock($request, $pembelian);

            PembelianTransaction::create([
                'pembelian_id' => $pembelian->id,
                'payment_date' => null,
                'payment_method' => 'bank_transfer',
                'payment_reference' => (($supplier->bank_no_rek ?? null) && ($supplier->bank_nama ?? null))
                    ? $supplier->bank_no_rek.'-'.$supplier->bank_nama
                    : 'TRX-'.now()->format('YmdHis'),
                // 'amount' => $grandTotal,
                'amount' => 0,
                'status' => 'unpaid',
                'notes' => null,
            ]);

            return $pembelian;
        });

        if ($offlineClientId) {
            return $this->offlineStoreResponse($pembelian);
        }

        return redirect(route('pembelian.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    private function offlineStoreResponse(Pembelian $pembelian, bool $created = true)
    {
        return response()->json([
            'success' => true,
            'created' => $created,
            'resource' => 'pembelian',
            'id' => $pembelian->id,
            'code' => $pembelian->code,
            'redirect' => route('pembelian.index'),
        ], $created ? 201 : 200);
    }

    public function show(Pembelian $pembelian)
    {
        // dd($pembelian->load(['stocks', 'stocks.product'])->toArray());
        return view('pembelian.show', [
            'pembelian' => $pembelian,
        ]);
    }

    public function edit(Pembelian $pembelian)
    {
        if (! $pembelian->canBeEditedBy(auth()->user())) {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'Anda tidak memiliki akses edit PO.');
        }

        return view('pembelians.edit', [
            'pembelian' => $pembelian,
            'kas' => Kas::get(),
            'outlets' => Outlet::get(),
            'suppliers' => Supplier::get(),
            'products' => collect(),
        ]);
    }

    public function update(PembelianRequest $request, Pembelian $pembelian)
    {
        if (! $pembelian->canBeEditedBy(auth()->user())) {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'Anda tidak memiliki akses edit PO.');
        }

        $data = $request->validated();
        $data['customer_po'] = $this->syncCustomerPoMaster($request->customer_po);
        $pembelian->update($data);
        $this->updateStock($request, $pembelian);

        return redirect(route('pembelian.index'))->with('toast_success', 'Berhasil Memperbarui Data!');
    }

    public function publish(Pembelian $pembelian)
    {
        if ($pembelian->owner_approval_status !== 'approved') {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'PO masih menunggu ACC owner.');
        }

        // Prevent double publishing
        if ($pembelian->is_published) {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'Pembelian already published');
        }

        return redirect()->route('pembelian.penerimaan', $pembelian);
    }

    public function penerimaanIndex()
    {
        $pembelians = Pembelian::with(['supplier', 'pembelianProducts.product', 'stocks'])
            ->latest()
            ->get();

        return view('pembelians.penerimaan-index', compact('pembelians'));
    }

    public function penerimaan(Pembelian $pembelian)
    {
        $pembelian->load(['pembelianProducts.product', 'stocks.product', 'supplier']);

        return view('pembelians.penerimaan', compact('pembelian'));
    }

    public function storePenerimaan(Request $request, Pembelian $pembelian)
    {
        $request->validate([
            'receipt_date'        => 'nullable|date',
            'receipt_pic'         => 'nullable|string',
            'receipt_status'      => 'nullable|in:draft,validated,completed',
            'receipt_photo'       => 'nullable|image|max:2048',
            'items'               => 'required|array',
            'items.*.product_id'  => 'required|exists:products,id',
            'items.*.qty_diterima' => 'required|integer|min:1',
            'items.*.unit'        => 'nullable|string|max:255',
        ], [
            'receipt_date.date'         => 'Tanggal penerimaan harus berupa tanggal yang valid.',
            'receipt_pic.string'        => 'PIC penerimaan harus berupa teks.',
            'receipt_status.in'         => 'Status penerimaan harus dipilih antara draft, validated, atau completed.',
            'receipt_photo.image'       => 'Foto penerimaan harus berupa gambar.',
            'receipt_photo.max'         => 'Ukuran foto penerimaan maksimal 2MB.',
            'items.required'            => 'Item barang harus diisi.',
            'items.array'               => 'Item barang harus berupa array.',
            'items.*.product_id.required' => 'Produk harus dipilih.',
            'items.*.product_id.exists'   => 'Produk yang dipilih tidak ditemukan.',
            'items.*.qty_diterima.required' => 'Jumlah diterima harus diisi.',
            'items.*.qty_diterima.integer'  => 'Jumlah diterima harus berupa angka.',
            'items.*.qty_diterima.min'      => 'Jumlah diterima minimal 1.',
        ]);

        DB::beginTransaction();
        try {
            $converter = app(ProductUnitConverter::class);
            $photoPath = $pembelian->receipt_photo;
            if ($request->hasFile('receipt_photo')) {
                $photoPath = $request->file('receipt_photo')->store('receipt-photos', 'public');
            }

            $pembelian->update([
                'code_gr'        => $request->code_gr,
                'receipt_date'   => $request->receipt_date,
                'receipt_pic'    => $request->receipt_pic,
                'receipt_status' => $request->receipt_status,
                'receipt_photo'  => $photoPath,
            ]);

            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                $unit = $itemData['unit'] ?? $converter->defaultInputUnit($product, 'supplier');
                $qtyDiterima = $converter->normalize($product, $itemData['qty_diterima'], $unit);

                $pembelianProduct = $pembelian->pembelianProducts()
                    ->where('product_id', $itemData['product_id'])
                    ->first();

                if (! $pembelianProduct) { continue; }

                $pembelianProduct->update(['qty_diterima' => $qtyDiterima]);

                // Cek apakah sudah ada stock global untuk product ini dari pembelian ini
                $existingStock = Stock::where('pembelian_id', $pembelian->id)
                    ->where('product_id', $itemData['product_id'])
                    ->first();

                if ($existingStock) {
                    // UPDATE qty stock yang sudah ada
                    $oldQty = $existingStock->qty;

                    $existingStock->update([
                        'qty'        => $qtyDiterima,
                        'subtotal'   => $qtyDiterima * $pembelianProduct->harga_beli,
                        'harga_beli' => $pembelianProduct->harga_beli,
                    ]);

                    if ($oldQty != $qtyDiterima) {
                        $diff = $qtyDiterima - $oldQty;
                        StockMovement::create([
                            'product_id'     => $itemData['product_id'],
                            'user_id'        => auth()->id(),
                            'type'           => $diff > 0 ? 'in' : 'adjustment',
                            'reference_type' => Pembelian::class,
                            'reference_id'   => $pembelian->id,
                            'qty_in'         => $diff > 0 ? $diff : 0,
                            'qty_out'        => $diff < 0 ? abs($diff) : 0,
                            'balance'        => $product->stocks()->sum('qty'),
                            'notes'          => "Stock update - Old Qty: {$oldQty}, New Qty: {$qtyDiterima}",
                        ]);
                    }
                } else {
                    // CREATE stock baru (global, tanpa SKU & expired)
                    Stock::create([
                        'pembelian_id' => $pembelian->id,
                        'product_id'   => $itemData['product_id'],
                        'harga_beli'   => $pembelianProduct->harga_beli,
                        'qty'          => $qtyDiterima,
                        'subtotal'     => $qtyDiterima * $pembelianProduct->harga_beli,
                        'condition'    => 'new',
                        'status'       => 'available',
                    ]);

                    $stockPembelian = StockPembelian::where([
                        'pembelian_id' => $pembelian->id,
                        'product_id'   => $itemData['product_id'],
                    ])->first();

                    if ($stockPembelian) {
                        $stockPembelian->decrement('qty', $qtyDiterima);
                        if ($stockPembelian->qty <= 0) {
                            $stockPembelian->delete();
                        }
                    }

                    StockMovement::create([
                        'product_id'     => $itemData['product_id'],
                        'user_id'        => auth()->id(),
                        'type'           => 'in',
                        'reference_type' => Pembelian::class,
                        'reference_id'   => $pembelian->id,
                        'qty_in'         => $qtyDiterima,
                        'balance'        => $product->stocks()->sum('qty'),
                        'notes'          => "Goods receipt from {$pembelian->supplier->name}",
                    ]);
                }

                // Update product HPP
                $newHPP = $product->calculateHPP($qtyDiterima, $pembelianProduct->harga_beli);
                $product->update(['harga_beli' => (int) $newHPP]);
                $product->updateStockValue();
            }

            if ($request->receipt_status == 'completed' && ! $pembelian->is_published) {
                $pembelian->update(['is_published' => true]);
            }

            DB::commit();

            return redirect()->route('pembelian.penerimaan', $pembelian)
                ->with('toast_success', 'Penerimaan updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('toast_error', $e->getMessage())->withInput();
        }
    }

    public function updatePenerimaan(Request $request, Pembelian $pembelian)
    {
        $request->validate([
            'code_gr' => 'nullable|string',
            'receipt_date' => 'nullable|date',
            'receipt_pic' => 'nullable|string',
            'receipt_status' => 'nullable|in:draft,validated,completed',
            'receipt_photo' => 'nullable|image|max:2048',
        ], [
            'code_gr.string' => 'Kode pembelian harus berupa teks.',
            'receipt_date.date' => 'Tanggal penerimaan harus berupa tanggal yang valid.',
            'receipt_pic.string' => 'PIC penerimaan harus berupa teks.',
            'receipt_status.in' => 'Status penerimaan harus dipilih antara draft, validated, atau completed.',
            'receipt_photo.image' => 'Foto penerimaan harus berupa gambar.',
            'receipt_photo.max' => 'Ukuran foto penerimaan maksimal 2MB.',
        ]);

        DB::beginTransaction();
        try {
            $photoPath = $pembelian->receipt_photo;
            if ($request->hasFile('receipt_photo')) {
                $photoPath = $request->file('receipt_photo')->store('receipt-photos', 'public');
            }

            $pembelian->update([
                'code_gr' => $request->code_gr,
                'receipt_date' => $request->receipt_date,
                'receipt_pic' => $request->receipt_pic,
                'receipt_status' => $request->receipt_status,
                'receipt_photo' => $photoPath,
            ]);

            if ($request->receipt_status == 'completed' && ! $pembelian->is_published) {
                $pembelian->update(['is_published' => true]);
            }

            DB::commit();

            return redirect()->route('pembelian.penerimaan', $pembelian)
                ->with('toast_success', 'Penerimaan details updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('toast_error', $e->getMessage())->withInput();
        }
    }

    private function updateStock($request, $pembelian)
    {
        foreach ($request->input('product', []) as $productData) {
            $product = Product::find($productData['product_id']);

            if (! $product) {
                continue;
            }

            $hargaBeli = $this->normalizeMoney($productData['harga_beli'] ?? 0);
            $qty = (int) ($productData['qty'] ?? 0);
            $subtotal = $this->normalizeMoney($productData['subtotal'] ?? 0);
            $expiredAt = $productData['expired_at'] ?? $productData['expired'] ?? null;
            $serialNumbers = null;

            if (! empty($productData['serial_numbers'])) {
                $serialNumbers = is_array($productData['serial_numbers'])
                    ? array_filter(array_map('trim', $productData['serial_numbers']))
                    : array_filter(array_map('trim', explode("\n", $productData['serial_numbers'])));
            }

            PembelianProduct::updateOrCreate(
                ['pembelian_id' => $pembelian->id, 'product_id' => $productData['product_id']],
                [
                    'harga_beli' => $hargaBeli,
                    'qty' => $qty,
                    'subtotal' => $subtotal,
                    'expired_at' => $expiredAt,
                    'serial_numbers' => $serialNumbers,
                ]
            );

            if ($pembelian->is_published) {
                if ($product->is_serialized && ! empty($serialNumbers)) {
                    foreach ($serialNumbers as $serial) {
                        Stock::updateOrCreate(
                            [
                                'pembelian_id' => $pembelian->id,
                                'product_id' => $productData['product_id'],
                                'serial_number' => $serial,
                            ],
                            [
                                'harga_beli' => $hargaBeli,
                                'qty' => 1,
                                'subtotal' => $hargaBeli,
                                'expired_at' => $expiredAt,
                                'condition' => 'new',
                                'status' => 'available',
                            ]
                        );
                    }
                } else {
                    Stock::updateOrCreate(
                        ['pembelian_id' => $pembelian->id, 'product_id' => $productData['product_id']],
                        [
                            'harga_beli' => $hargaBeli,
                            'qty' => $qty,
                            'subtotal' => $subtotal,
                            'expired_at' => $expiredAt,
                            'condition' => 'new',
                            'status' => 'available',
                        ]
                    );
                }
            } elseif ($product->is_serialized && ! empty($serialNumbers)) {
                foreach ($serialNumbers as $serial) {
                    StockPembelian::updateOrCreate(
                        [
                            'pembelian_id' => $pembelian->id,
                            'product_id' => $productData['product_id'],
                            'serial_number' => $serial,
                        ],
                        [
                            'harga_beli' => $hargaBeli,
                            'qty' => 1,
                            'subtotal' => $hargaBeli,
                            'expired_at' => $expiredAt,
                            'condition' => 'new',
                            'status' => 'available',
                        ]
                    );
                }
            } else {
                StockPembelian::updateOrCreate(
                    ['pembelian_id' => $pembelian->id, 'product_id' => $productData['product_id']],
                    [
                        'harga_beli' => $hargaBeli,
                        'qty' => $qty,
                        'subtotal' => $subtotal,
                        'expired_at' => $expiredAt,
                        'condition' => 'new',
                        'status' => 'available',
                    ]
                );
            }

            $product->update(['harga_beli' => $hargaBeli]);
        }
    }

    private function normalizeMoney($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    public function destroy(Pembelian $pembelian)
    {
        if (auth()->user()->role === 'owner' || ! $pembelian->canBeEditedBy(auth()->user())) {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'PO ini belum bisa dihapus.');
        }

        $pembelian->stocks()->delete();
        $pembelian->delete();

        return redirect(route('pembelian.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function stockDestroy($id)
    {
        $pembelianProduct = PembelianProduct::find($id);
        if (! $pembelianProduct) {
            return redirect()->route('pembelian.index')->with('toast_error', 'Item PO tidak ditemukan.');
        }

        $pembelian = $pembelianProduct->pembelian;
        if (! $pembelian->canBeEditedBy(auth()->user())) {
            return redirect()->route('pembelian.index')
                ->with('toast_error', 'PO ini belum bisa diedit.');
        }

        $pembelianProduct->delete();

        $pembelian->update(
            ['total' => $pembelian->pembelianProducts->sum('subtotal')]
        );

        return redirect()->back()->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function editPembayaran(Pembelian $pembelian)
    {
        $pembelian->load(['supplier', 'pembelianProducts.product', 'pembelianTransaction']);
        $title = 'Edit Pembayaran Pembelian';
        $paymentHistory = $pembelian->pembelianTransaction?->payment_history ?? [];

        return view('pembelians.pembayaran-edit', compact('pembelian', 'title', 'paymentHistory'));
    }

    public function updatePembayaran(Request $request, Pembelian $pembelian)
    {
        if (auth()->user()->role === 'owner') {
            abort(403);
        }

        $currentAmount = $pembelian->pembelianTransaction?->amount ?? 0;
        $maxAmount = $pembelian->total - $currentAmount;

        $request->validate([
            'payment_date'      => 'required|date',
            'payment_method'    => 'required|in:cash,bank_transfer,giro_cek,lainnya',
            'payment_reference' => 'nullable|string',
            'amount'            => 'required|numeric|min:0|max:'.$maxAmount,
            'notes'             => 'nullable|string',
            'status'            => 'required|in:unpaid,paid,partial',
            'bukti_transfer'    => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'payment_date.required' => 'Tanggal pembayaran harus diisi.',
            'payment_date.date' => 'Tanggal pembayaran harus berupa tanggal yang valid.',
            'payment_method.required' => 'Metode pembayaran harus dipilih.',
            'payment_method.in' => 'Metode pembayaran harus dipilih antara cash, bank transfer, giro/cek, atau lainnya.',
            'payment_reference.string' => 'Referensi pembayaran harus berupa teks.',
            'amount.required' => 'Jumlah pembayaran harus diisi.',
            'amount.numeric' => 'Jumlah pembayaran harus berupa angka.',
            'amount.min' => 'Jumlah pembayaran minimal 0.',
            'amount.max' => 'Jumlah pembayaran maksimal :max.',
            'notes.string' => 'Catatan harus berupa teks.',
            'status.required' => 'Status pembayaran harus diisi.',
            'status.in' => 'Status pembayaran harus dipilih antara unpaid, paid, atau partial.',
            'bukti_transfer.file' => 'Bukti transfer harus berupa file.',
            'bukti_transfer.mimes' => 'Bukti transfer harus berupa file dengan format jpg, jpeg, png, atau pdf.',
            'bukti_transfer.max' => 'Ukuran bukti transfer maksimal 2MB.',
        ]);

        DB::beginTransaction();
        try {
            $previousAmount = $pembelian->pembelianTransaction?->amount ?? 0;
            $newTotalAmount = $previousAmount + $request->amount;

            // Handle file upload
            $buktiPath = null;
            if ($request->hasFile('bukti_transfer')) {
                $file = $request->file('bukti_transfer');
                $filename = 'bukti_'.time().'_'.$pembelian->id.'.'.$file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('bukti_transfer', $filename, 'public');
            }

            if ($pembelian->pembelianTransaction) {
                // Update existing transaction
                $paymentHistory = $pembelian->pembelianTransaction->payment_history ?? [];

                if ($request->amount > 0) {
                    $paymentHistory[] = [
                        'payment_date'      => $request->payment_date,
                        'amount'            => $request->amount,
                        'payment_method'    => $request->payment_method,
                        'payment_reference' => $request->payment_reference,
                        'bukti_transfer'    => $buktiPath ?? null,
                        'notes'             => $request->notes,
                        'created_at'        => now()->toDateTimeString(),
                    ];
                }

                $transactionData = [
                    'payment_date'      => $request->payment_date,
                    'payment_method'    => $request->payment_method,
                    'payment_reference' => $request->payment_reference,
                    'amount'            => $newTotalAmount,
                    'payment_history'   => $paymentHistory,
                    'notes'             => $request->notes,
                    'status'            => $request->status,
                ];

                if ($buktiPath) {
                    $transactionData['bukti_transfer'] = $buktiPath;
                }

                $pembelian->pembelianTransaction->update($transactionData);
            } else {
                // Create new transaction
                $paymentHistory = [];
                if ($request->amount > 0) {
                    $paymentHistory[] = [
                        'payment_date'      => $request->payment_date,
                        'amount'            => $request->amount,
                        'payment_method'    => $request->payment_method,
                        'payment_reference' => $request->payment_reference,
                        'bukti_transfer'    => $buktiPath,
                        'notes'             => $request->notes,
                        'created_at'        => now()->toDateTimeString(),
                    ];
                }

                $transactionData = [
                    'payment_date'      => $request->payment_date,
                    'payment_method'    => $request->payment_method,
                    'payment_reference' => $request->payment_reference,
                    'amount'            => $request->amount,
                    'payment_history'   => $paymentHistory,
                    'notes'             => $request->notes,
                    'status'            => $request->status,
                    'bukti_transfer'    => $buktiPath,
                ];

                $pembelian->pembelianTransaction()->create($transactionData);
            }

            DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disimpan'
        ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage()
            ], 500);
        }
    }

    public function nextCode(Supplier $supplier)
    {
        return response()->json([
            'code' => $supplier->generateNextPoCode(),
        ]);
    }

    public function approveOwner(Request $request, Pembelian $pembelian)
    {
        if (! in_array(auth()->user()->role, ['owner', 'superadmin'])) {
            abort(403);
        }

        $request->validate([
            'owner_approval_note' => 'nullable|string',
        ]);

        $pembelian->update([
            'owner_approval_status' => 'approved',
            'owner_approved_by' => auth()->id(),
            'owner_approved_at' => now(),
            'owner_approval_note' => $request->owner_approval_note,
        ]);

        return redirect()->back()->with('toast_success', 'PO berhasil di-ACC owner.');
    }

    public function rejectOwner(Request $request, Pembelian $pembelian)
    {
        if (! in_array(auth()->user()->role, ['owner', 'superadmin'])) {
            abort(403);
        }

        $request->validate([
            'owner_approval_note' => 'nullable|string',
        ]);

        $pembelian->update([
            'owner_approval_status' => 'rejected',
            'owner_approved_by' => auth()->id(),
            'owner_approved_at' => now(),
            'owner_approval_note' => $request->owner_approval_note,
            'is_published' => false,
        ]);

        return redirect()->back()->with('toast_success', 'PO ditolak owner dan dikembalikan ke draft revisi.');
    }

    private function generateDefaultPoCode(): string
    {
        $lastPembelian = Pembelian::latest('id')->first();
        $nextNumber = $lastPembelian ? ($lastPembelian->id + 1) : 1;

        return 'PO'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function syncCustomerPoMaster(?string $customerPo): ?string
    {
        $normalized = trim((string) $customerPo);

        if ('' === $normalized) {
            return null;
        }

        $existing = CustomerPo::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            if ($existing->name !== $normalized) {
                $existing->update(['name' => $normalized]);
            }

            return $normalized;
        }

        CustomerPo::create([
            'name' => $normalized,
        ]);

        return $normalized;
    }

}
