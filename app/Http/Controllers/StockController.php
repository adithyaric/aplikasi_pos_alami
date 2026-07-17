<?php

namespace App\Http\Controllers;

use App\Exports\StockOpnameTemplateExport;
use App\Models\Product;
use App\Models\RefundPembelian;
use App\Models\RefundPembelianItem;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class StockController extends Controller
{
    public function index()
    {
        $stocks = Stock::with(['product.category'])
            ->selectRaw('
            product_id,
            SUM(qty) as total_qty,
            MAX(harga_beli) as harga_beli,
            MAX(created_at) as latest_created_at
        ')
            ->groupBy('product_id')
            ->get();

        return view('stocks.index', compact('stocks'));
    }

    public function show(Stock $stock)
    {
        $stock->delete();

        $total = $stock->pembelian->stocks->sum('subtotal');
        $stock->pembelian->update(['total' => $total]);

        return redirect()->back()->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function destroy(Stock $stock)
    {
        dd(
            'destory Stock',
            $stock->toArray(),
            $stock->pembelian->toArray()
        );
        // $stock->delete();

        return redirect()->back()->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function history($productId)
    {
        // Ambil salah satu stock sebagai jangkar/representasi product tersebut
        $stock = Stock::where('product_id', $productId)->firstOrFail();

        $product = $stock->product;

        $activities = Activity::forSubject($stock)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($activity) {
                return [
                    'source'     => 'activity',
                    'date'       => $activity->created_at->format('d M Y H:i'),
                    'user'       => $activity->causer?->name ?? 'System',
                    'event'      => $activity->event,
                    'properties' => $activity->properties,
                ];
            });

        // Query mutasi ini tetap utuh & benar karena berbasis product_id sesuai gambar image_dad12b.png
        $movements = StockMovement::where('product_id', $stock->product_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($movement) {
                return [
                    'source'  => 'movement',
                    'date'    => $movement->created_at->format('d M Y H:i'),
                    'user'    => $movement->user?->name ?? 'System',
                    'type'    => $movement->type,
                    'qty_in'  => $movement->qty_in,
                    'qty_out' => $movement->qty_out,
                    'balance' => $movement->balance,
                    'notes'   => $movement->notes,
                ];
            });

        return response()->json([
            'success'    => true,
            'activities' => $activities,
            'movements'  => $movements,
            'product'    => [
                'satuan'               => $product->satuan ?? 'PCS',
                'satuan_besar'         => $product->satuan_besar,
                'konversi_qty'         => $product->konversi_qty,
                'satuan_terbesar'      => $product->satuan_terbesar,
                'konversi_qty_terbesar' => $product->konversi_qty_terbesar,
            ],
        ]);
    }

    //kartu
    public function kartu(Request $request)
    {
        // Grouping berdasarkan product_id agar tampil global per produk
        $stocks = Stock::with(['product'])
            ->selectRaw('product_id, MAX(id) as id') // ambil 1 sample stock ID untuk cadangan
            ->groupBy('product_id')
            ->get()
            ->map(function ($stock) {
                return [
                    'product_id'   => $stock->product_id,
                    'product_name' => $stock->product->name,
                    'product_code' => $stock->product->code,
                ];
            });

        return view('stocks.kartu', [
            'stocks' => $stocks,
        ]);
    }

    public function getKartuData(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ], [
            'product_id.required' => 'Produk harus dipilih.',
            'product_id.exists'   => 'Produk yang dipilih tidak ditemukan.',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Ambil seluruh pergerakan stok berdasarkan product_id secara berurutan
        // Kita eager load relasi ke pembelian dan supplier jika movement menggunakan Morph/Relation tertentu
        $movements = StockMovement::where('product_id', $product->id)
            ->orderBy('created_at', 'asc')
            ->get();

        $result       = [];
        $runningStock = 0;
        $currentPrice = $product->harga_beli ?? 0;
        $lastSupplier = '-'; // Untuk menampung nama supplier terakhir dimasukkan ke info header

        foreach ($movements as $movement) {
            $stokAwal  = $runningStock;
            $masuk     = $movement->qty_in ?? 0;
            $keluar    = $movement->qty_out ?? 0;
            $stokAkhir = $stokAwal + $masuk - $keluar;

            $supplierName = null;

            // Jika ada mutasi masuk ("in") dari pembelian, kita cari tahu supplier dan harganya
            if ($masuk > 0) {
                $relatedStock = Stock::with('pembelian.supplier')
                    ->where('product_id', $product->id)
                    ->where('created_at', '<=', $movement->created_at)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($relatedStock) {
                    $currentPrice = $relatedStock->harga_beli;
                    if ($relatedStock->pembelian && $relatedStock->pembelian->supplier) {
                        $supplierName = $relatedStock->pembelian->supplier->name;
                        $lastSupplier = $supplierName; // Simpan untuk info header terbaru
                    }
                }
            }

            $nilai = $stokAkhir * $currentPrice;

            // Bangun keterangan default
            $dummyStock = new Stock(['id' => 0, 'product_id' => $product->id]);
            $keterangan = $this->buildKartuKeterangan($movement, $dummyStock);

            // Tempelkan nama supplier di keterangan jika tipe transaksinya masuk/pembelian
            if ($supplierName) {
                $keterangan = "Supplier: " . $supplierName . " | " . $keterangan;
            }

            $result[] = [
                'tanggal'    => $movement->created_at->format('Y-m-d'),
                'stok_awal'  => $stokAwal,
                'masuk'      => $masuk,
                'keluar'     => $keluar,
                'stok_akhir' => $stokAkhir,
                'harga'      => $currentPrice,
                'nilai'      => $nilai,
                'keterangan' => $keterangan,
            ];

            $runningStock = $stokAkhir;
        }

        return response()->json([
            'stock' => [
                'product_id'            => $product->id,
                'product_name'          => $product->name,
                'product_code'          => $product->code,
                'supplier'              => $lastSupplier,
                'konversi_qty'          => $product->konversi_qty,
                'satuan_besar'          => $product->satuan_besar,
                'satuan'                => $product->satuan ?? 'PCS',
                'konversi_qty_terbesar' => $product->konversi_qty_terbesar, // tambah
                'satuan_terbesar'       => $product->satuan_terbesar,       // tambah
            ],
            'transactions' => $result
        ]);
    }

    protected function buildKartuKeterangan(StockMovement $movement, Stock $stock): string
    {
        $parts = [];

        $this->appendKeteranganPart($parts, $movement->notes);

        if ($movement->reference_type === StockAdjustment::class) {
            $adjustment = StockAdjustment::find($movement->reference_id);
            if ($adjustment && $adjustment->stock_id === $stock->id) {
                $this->appendKeteranganPart($parts, $adjustment->keterangan);
                $this->appendKeteranganPart($parts, $adjustment->reason);
            }
        }

        if ($movement->reference_type === RefundPembelian::class) {
            $refundItem = RefundPembelianItem::where('refund_pembelian_id', $movement->reference_id)
                ->where('product_id', $movement->product_id)
                ->where('stock_id', $stock->id)
                ->latest('id')
                ->first();

            if ($refundItem && !empty($refundItem->alasan)) {
                $this->appendKeteranganPart($parts, 'Alasan retur: ' . $refundItem->alasan);
            }
        }

        return !empty($parts) ? implode(' | ', $parts) : '-';
    }

    protected function appendKeteranganPart(array &$parts, ?string $value): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $normalizedValue = mb_strtolower($value);

        foreach ($parts as $part) {
            $normalizedPart = mb_strtolower($part);

            if (
                $normalizedPart === $normalizedValue
                || str_contains($normalizedPart, $normalizedValue)
                || str_contains($normalizedValue, $normalizedPart)
            ) {
                return;
            }
        }

        $parts[] = $value;
    }

    //opname
    public function opname(Request $request)
    {
        $lokasiOptions = Product::whereNotNull('lokasi')
            ->where('lokasi', '!=', '')
            ->distinct()
            ->orderBy('lokasi')
            ->pluck('lokasi');

        // Filter supplier disesuaikan dengan product yang memiliki stock aktif
        $supplierOptions = \App\Models\Supplier::orderBy('name')
            ->whereHas('pembelians.stocks', fn($q) => $q->where('qty', '>', 0))
            ->get(['id', 'name']);

        return view('stocks.opname', [
            'lokasiOptions'   => $lokasiOptions,
            'supplierOptions' => $supplierOptions,
        ]);
    }

    public function getOpnameData(Request $request)
    {
        // Query menggunakan Group By product_id agar akumulasi stoknya global per produk
        $query = Stock::with(['product', 'pembelian.supplier'])
            ->selectRaw('
                product_id,
                SUM(qty) as total_qty,
                SUM(qty_reserved) as total_reserved,
                SUM(qty_available) as total_available,
                MAX(id) as last_stock_id
            ')
            ->where('qty', '>', 0)
            ->groupBy('product_id');

        if ($lokasi = $request->input('lokasi')) {
            $query->whereHas('product', fn($q) => $q->where('lokasi', $lokasi));
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->whereHas('pembelian', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $stocks = $query->get()->map(function ($stock) {
            return [
                'id'                    => $stock->last_stock_id, // pakai last_stock_id bukan $stock->id
                'product_id'            => $stock->product_id,
                'product_name'          => $stock->product->name,
                'product_code'          => $stock->product->code,
                'satuan'                => $stock->product->satuan ?? 'pcs',
                'satuan_besar'          => $stock->product->satuan_besar,
                'konversi_qty'          => $stock->product->konversi_qty,
                'satuan_terbesar'       => $stock->product->satuan_terbesar,
                'konversi_qty_terbesar' => $stock->product->konversi_qty_terbesar,
                'qty'                   => (int) ($stock->total_qty ?? 0),         // pakai total_qty dari SUM
                'qty_reserved'          => (int) ($stock->total_reserved ?? 0),    // pakai total_reserved
                'qty_available'         => (int) ($stock->total_available ?? 0),   // pakai total_available
                'supplier'              => $stock->pembelian?->supplier?->name ?? '-',
                'keterangan'            => $stock->adjustment?->keterangan ?? '',
            ];
        });

        return response()->json(['stocks' => $stocks->values()]);
    }

    public function saveOpname(Request $request)
    {
        $request->validate([
            'adjustment_date'        => 'required|date',
            'items'                  => 'required|array',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.selisih'        => 'required|numeric',
            'items.*.system_qty'     => 'nullable|numeric',
            'items.*.physical_qty'   => 'nullable|numeric',
            'items.*.keterangan'     => 'nullable|string',
        ], [
            'adjustment_date.required' => 'Tanggal penyesuaian harus diisi.',
            'items.required'           => 'Item harus diisi.',
            'items.*.product_id.required' => 'Produk harus ditentukan.',
            'items.*.selisih.required'  => 'Selisih harus diisi.',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                if ($item['selisih'] != 0) {

                    // Cari batch stock terakhir dari produk ini untuk menampung selisih nilai opname
                    $stock = Stock::where('product_id', $item['product_id'])
                        ->orderBy('id', 'desc')
                        ->first();

                    if (!$stock) {
                        // Jika tidak ada stok sama sekali, buat baris stok baru dari master produk
                        $stock = Stock::create([
                            'product_id' => $item['product_id'],
                            'qty' => 0,
                            'harga_beli' => \App\Models\Product::find($item['product_id'])->harga_beli ?? 0
                        ]);
                    }

                    $savedAdj = StockAdjustment::create([
                        'adjustment_date' => $request->adjustment_date,
                        'product_id'      => $stock->product_id,
                        'stock_id'        => $stock->id,
                        'quantity'        => $item['selisih'],
                        'system_qty'      => $item['system_qty'],
                        'physical_qty'    => $item['physical_qty'],
                        'reason'          => $item['keterangan'] ?? null,
                        'status'          => 'Selesai',
                        'keterangan'      => $item['keterangan'] ?? null,
                    ]);

                    // Update qty pada batch stock terpilih
                    $newQty = $stock->qty + $item['selisih'];
                    $stock->update(['qty' => $newQty]);

                    // Ambal total stock global terbaru setelah update untuk disimpan di balance movement
                    $globalBalance = Stock::where('product_id', $stock->product_id)->sum('qty');

                    StockMovement::create([
                        'product_id'     => $stock->product_id,
                        'user_id'        => auth()->id(),
                        'type'           => 'adjustment',
                        'reference_type' => StockAdjustment::class,
                        'reference_id'   => $savedAdj->id,
                        'qty_in'         => $item['selisih'] > 0 ? $item['selisih'] : 0,
                        'qty_out'        => $item['selisih'] < 0 ? abs($item['selisih']) : 0,
                        'balance'        => $globalBalance,
                        'notes'          => 'Stock opname adjustment - ' . ($item['keterangan'] ?? 'Stock adjustment'),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Stok opname berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function exportOpnameTemplate(Request $request)
    {
        $settings = json_decode(Storage::disk('public')->get('settings.json'), true) ?? [];

        $query = Stock::with('product')
            ->where('qty', '>=', 0)
            ->orderBy('product_id');

        if ($lokasi = $request->input('lokasi')) {
            $query->whereHas('product', fn($q) => $q->where('lokasi', $lokasi));
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->whereHas('pembelian', fn($q) => $q->where('supplier_id', $supplierId));
        }

        $stocks = $query->get();
        $date   = date('Y-m-d');

        return Excel::download(
            new StockOpnameTemplateExport($stocks, $date, $settings),
            'Template_Stock_Opname-' . $date . '.xlsx'
        );
    }
}
