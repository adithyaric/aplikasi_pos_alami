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
use App\Models\Stock;
use App\Services\WarehousePenjualanManager;
use App\Support\ProductUnitConverter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function __construct(
        private readonly WarehousePenjualanManager $warehousePenjualanManager
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

    public function index()
    {
        $this->ensureWarehouseSaleAccess();

        return view('penjualan.index', [
            'penjualans' => Penjualan::warehouseSales()
                ->with(['items.product', 'operator', 'agent', 'canvasBuyer', 'outletBuyer', 'paymentTransaction'])
                ->orderByDesc('sale_date')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create()
    {
        $this->ensureWarehouseSaleAccess();

        return view('penjualan.create', $this->warehouseSaleFormData());
    }

    public function edit(Penjualan $penjualan)
    {
        $this->ensureWarehouseSaleAccess();
        abort_unless($penjualan->isWarehouseSale(), 404);

        $penjualan->load('items.product');

        return view('penjualan.edit', $this->warehouseSaleFormData($penjualan));
    }

    public function storeWarehouseSale(WarehousePenjualanRequest $request)
    {
        $this->ensureWarehouseSaleAccess();

        try {
            $penjualan = $this->warehousePenjualanManager->create([
                'code' => $this->generateWarehouseSaleCode(),
                'buyer_type' => $request->buyer_type,
                'buyer_id' => $this->resolveBuyerTargetId($request),
                'sale_date' => $request->sale_date,
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'discount' => (int) ($request->discount ?? 0),
                'items' => collect($request->items)->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'qty' => (float) $item['qty'],
                    'unit' => (string) $item['unit'],
                    'price' => (int) $item['price'],
                ])->all(),
            ], (int) auth()->id());

            return redirect()->route('penjualan.show', $penjualan)
                ->with('toast_success', 'Penjualan berhasil disimpan.');
        } catch (\Exception $exception) {
            return redirect()->back()
                ->withInput()
                ->with('toast_error', 'Gagal: '.$exception->getMessage());
        }
    }

    public function updateWarehouseSale(WarehousePenjualanRequest $request, Penjualan $penjualan)
    {
        $this->ensureWarehouseSaleAccess();
        abort_unless($penjualan->isWarehouseSale(), 404);

        try {
            $penjualan = $this->warehousePenjualanManager->update($penjualan, [
                'buyer_type' => $request->buyer_type,
                'buyer_id' => $this->resolveBuyerTargetId($request),
                'sale_date' => $request->sale_date,
                'payment_type' => $request->payment_type,
                'payment_status' => $request->payment_status,
                'due_date' => $request->due_date,
                'notes' => $request->notes,
                'discount' => (int) ($request->discount ?? 0),
                'items' => collect($request->items)->map(fn ($item) => [
                    'product_id' => (int) $item['product_id'],
                    'qty' => (float) $item['qty'],
                    'unit' => (string) $item['unit'],
                    'price' => (int) $item['price'],
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
        $this->ensureWarehouseSaleAccess();

        $request->validate([
            'buyer_type' => 'required|in:agent,canvas,outlet',
            'buyer_id' => 'required|integer',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $price = PenjualanItem::query()
            ->select('penjualan_items.price')
            ->join('penjualans', 'penjualans.id', '=', 'penjualan_items.penjualan_id')
            ->where('penjualans.sale_channel', 'warehouse')
            ->where('penjualans.buyer_type', $request->buyer_type)
            ->where('penjualans.buyer_id', (int) $request->buyer_id)
            ->where('penjualan_items.product_id', (int) $request->product_id)
            ->orderByDesc('penjualans.sale_date')
            ->orderByDesc('penjualan_items.id')
            ->value('price');

        return response()->json([
            'price' => $price !== null ? (int) $price : null,
        ]);
    }

    public function editPembayaran(Penjualan $penjualan)
    {
        $this->ensureWarehouseSaleAccess();
        abort_unless($penjualan->isWarehouseSale(), 404);

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
        $this->ensureWarehouseSaleAccess();
        abort_unless($penjualan->isWarehouseSale(), 404);

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
        $penjualan->load([
            'items.product',
            'operator',
            'customer',
            'kasir',
            'outlet',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'transaction.payment',
            'paymentTransaction',
        ]);

        return view('penjualan.show', [
            'penjualan' => $penjualan,
        ]);
    }

    public function print(Penjualan $penjualan)
    {
        $penjualan->load([
            'items.product',
            'operator',
            'customer',
            'kasir',
            'outlet',
            'agent',
            'canvasBuyer',
            'outletBuyer',
            'transaction.payment',
            'paymentTransaction',
        ]);

        return view('penjualan.print', [
            'penjualan' => $penjualan,
        ]);
    }

    public function suratJalan(Penjualan $penjualan)
    {
        $penjualan->load([
            'items.product',
            'operator',
            'agent',
            'canvasBuyer',
            'outletBuyer',
        ]);

        return view('penjualan.surat-jalan', [
            'penjualan' => $penjualan,
        ]);
    }

    public function destroy(Penjualan $penjualan)
    {
        if ($penjualan->isWarehouseSale()) {
            return redirect()->route('penjualan.index')
                ->with('toast_error', 'Penjualan gudang tidak dapat dihapus. Gunakan koreksi stok bila diperlukan.');
        }

        $penjualan->delete();

        return redirect(route('penjualan.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    private function ensureWarehouseSaleAccess(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['superadmin', 'admin-gudang', 'owner'], true), 403);
    }

    private function warehouseSaleFormData(?Penjualan $penjualan = null): array
    {
        $converter = app(ProductUnitConverter::class);
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
            ->map(function (Product $product) use ($converter) {
                $availableQty = (int) $product->stocks->sum(function ($stock) {
                    return $this->resolveStockAvailableQty($stock);
                });

                return [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'harga_jual' => (int) ($product->harga_jual ?? 0),
                    'available_stock_qty' => $availableQty,
                    'stock_summary' => $converter->stockSummaryDisplay($product, $availableQty),
                    'default_unit' => $converter->defaultInputUnit($product, 'distribution'),
                    'units' => $this->productUnits($product),
                ];
            })
            ->filter(fn (array $product) => $product['available_stock_qty'] > 0)
            ->values();

        return [
            'penjualan' => $penjualan,
            'code' => $penjualan?->code ?? $this->generateWarehouseSaleCode(),
            'saleDate' => ($penjualan?->sale_date ?? now())->format('Y-m-d'),
            'agents' => Agent::where('is_active', true)->orderBy('name')->get(),
            'canvases' => Canvas::where('is_active', true)->orderBy('name')->get(),
            'outlets' => Outlet::branches()->orderBy('name')->get(),
            'products' => $products,
            'initialItems' => old('items')
                ?: ($penjualan
                    ? $penjualan->items->map(fn ($item) => [
                        'product_id' => (int) $item->product_id,
                        'qty' => (float) ($item->qty_input ?? $item->qty),
                        'unit' => $item->unit ?: $item->product?->satuan,
                        'price' => (int) $item->price,
                    ])->values()->all()
                    : []),
        ];
    }

    private function productUnits(Product $product): array
    {
        return collect([
            $product->satuan,
            $product->satuan_besar,
            $product->satuan_terbesar,
        ])
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($unit) => [
                'value' => $unit,
                'label' => $unit,
            ])
            ->all();
    }

    private function generateWarehouseSaleCode(): string
    {
        $lastSale = Penjualan::warehouseSales()->latest('id')->first();
        $nextNumber = $lastSale ? ((int) substr((string) $lastSale->code, 3) + 1) : 1;

        return 'PNJ'.str_pad((string) $nextNumber, 5, '0', STR_PAD_LEFT);
    }

    private function resolveBuyerTargetId(WarehousePenjualanRequest $request): int
    {
        return (int) match ($request->buyer_type) {
            'agent' => $request->agent_id,
            'canvas' => $request->canvas_id,
            'outlet' => $request->outlet_target_id,
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
