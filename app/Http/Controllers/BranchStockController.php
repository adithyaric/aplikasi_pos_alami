<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockAdjustment;
use App\Models\OwnerStockMovement;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchStockController extends Controller
{
    public function index(Request $request)
    {
        $ownerId = $this->resolvedOwnerId($request);
        $outlets = $this->branchOptions();

        $stocks = OwnerStock::with(['owner:id,name', 'product:id,code,name,satuan,satuan_besar,konversi_qty,satuan_terbesar,konversi_qty_terbesar'])
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->where('qty', '>', 0)
            ->orderBy('owner_id')
            ->orderBy('product_id')
            ->get()
            ->groupBy(fn (OwnerStock $stock) => $stock->owner_id.'-'.$stock->product_id)
            ->map(function ($group) {
                $first = $group->first();
                $qty = (int) $group->sum('qty');

                return [
                    'owner' => $first->owner,
                    'product' => $first->product,
                    'qty' => $qty,
                    'qty_display' => $first->product?->qtyDisplay($qty) ?? $qty,
                    'sku' => $group->sortByDesc('id')->first()?->sku,
                    'expired_at' => $group->filter(fn ($stock) => $stock->expired_at)->sortBy('expired_at')->first()?->expired_at,
                    'harga_beli' => (float) $group->max('harga_beli'),
                ];
            })
            ->values();

        return view('branch-stocks.index', [
            'stocks' => $stocks,
            'outlets' => $outlets,
            'selectedOwnerId' => $ownerId,
            'isBranchScoped' => auth()->user()?->isBranchScoped() ?? false,
        ]);
    }

    public function kartu(Request $request)
    {
        $ownerId = $this->resolvedOwnerId($request);
        $products = $this->productsForOwner($ownerId);

        return view('branch-stocks.kartu', [
            'outlets' => $this->branchOptions(),
            'selectedOwnerId' => $ownerId,
            'products' => $products,
            'isBranchScoped' => auth()->user()?->isBranchScoped() ?? false,
        ]);
    }

    public function getKartuData(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'owner_id' => 'nullable|exists:outlets,id',
        ]);

        $ownerId = $this->resolvedOwnerId($request, true);
        $product = Product::findOrFail($request->integer('product_id'));
        $owner = Outlet::findOrFail($ownerId);
        $runningStock = 0;
        $currentPrice = (float) ($product->harga_beli ?? 0);

        $transactions = OwnerStockMovement::with(['user:id,name', 'ownerStock:id,harga_beli'])
            ->where('owner_id', $ownerId)
            ->where('product_id', $product->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(function (OwnerStockMovement $movement) use (&$runningStock, &$currentPrice) {
                $stokAwal = $runningStock;
                $masuk = (float) $movement->qty_in;
                $keluar = (float) $movement->qty_out;
                $stokAkhir = $stokAwal + $masuk - $keluar;

                if ($movement->ownerStock?->harga_beli) {
                    $currentPrice = (float) $movement->ownerStock->harga_beli;
                }

                $runningStock = $stokAkhir;

                return [
                    'tanggal' => $movement->created_at->format('Y-m-d H:i'),
                    'user' => $movement->user?->name ?? 'System',
                    'type' => $movement->type,
                    'stok_awal' => $stokAwal,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'stok_akhir' => $stokAkhir,
                    'harga' => $currentPrice,
                    'nilai' => $stokAkhir * $currentPrice,
                    'keterangan' => $movement->notes ?: '-',
                ];
            });

        return response()->json([
            'stock' => [
                'owner_id' => $owner->id,
                'owner_name' => $owner->name,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_code' => $product->code,
                'satuan' => $product->satuan ?? 'PCS',
                'satuan_besar' => $product->satuan_besar,
                'konversi_qty' => $product->konversi_qty,
                'satuan_terbesar' => $product->satuan_terbesar,
                'konversi_qty_terbesar' => $product->konversi_qty_terbesar,
            ],
            'transactions' => $transactions,
        ]);
    }

    public function opname(Request $request)
    {
        return view('branch-stocks.opname', [
            'outlets' => $this->branchOptions(),
            'selectedOwnerId' => $this->resolvedOwnerId($request),
            'isBranchScoped' => auth()->user()?->isBranchScoped() ?? false,
        ]);
    }

    public function getOpnameData(Request $request)
    {
        $ownerId = $this->resolvedOwnerId($request, true);

        $stocks = OwnerStock::with('product')
            ->where('owner_id', $ownerId)
            ->groupBy('product_id')
            ->selectRaw('product_id, MAX(id) as id, SUM(qty) as total_qty')
            ->orderBy('product_id')
            ->get()
            ->map(function ($stock) {
                return [
                    'id' => (int) $stock->id,
                    'product_id' => (int) $stock->product_id,
                    'product_code' => $stock->product?->code,
                    'product_name' => $stock->product?->name,
                    'satuan' => $stock->product?->satuan ?? 'PCS',
                    'satuan_besar' => $stock->product?->satuan_besar,
                    'konversi_qty' => $stock->product?->konversi_qty,
                    'satuan_terbesar' => $stock->product?->satuan_terbesar,
                    'konversi_qty_terbesar' => $stock->product?->konversi_qty_terbesar,
                    'qty' => (int) $stock->total_qty,
                ];
            })
            ->values();

        return response()->json(['stocks' => $stocks]);
    }

    public function saveOpname(Request $request)
    {
        $ownerId = $this->resolvedOwnerId($request, true);

        $request->validate([
            'adjustment_date' => 'required|date',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.system_qty' => 'required|numeric|min:0',
            'items.*.physical_qty' => 'required|numeric|min:0',
            'items.*.selisih' => 'required|numeric',
            'items.*.keterangan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $ownerId) {
            foreach ($request->items as $item) {
                $quantity = (float) $item['selisih'];
                if ($quantity == 0.0) {
                    continue;
                }

                $ownerStock = $quantity > 0
                    ? $this->increaseOwnerStockForAdjustment($ownerId, (int) $item['product_id'], $quantity)
                    : $this->decreaseOwnerStockForAdjustment($ownerId, (int) $item['product_id'], abs($quantity));

                $adjustment = OwnerStockAdjustment::create([
                    'owner_id' => $ownerId,
                    'product_id' => (int) $item['product_id'],
                    'owner_stock_id' => $ownerStock?->id,
                    'adjustment_date' => $request->adjustment_date,
                    'system_qty' => (float) $item['system_qty'],
                    'physical_qty' => (float) $item['physical_qty'],
                    'quantity' => $quantity,
                    'reason' => $item['keterangan'] ?? null,
                    'keterangan' => $item['keterangan'] ?? null,
                    'status' => 'Selesai',
                    'user_id' => auth()->id(),
                ]);

                $balance = $this->ownerProductBalance($ownerId, (int) $item['product_id']);

                OwnerStockMovement::create([
                    'owner_id' => $ownerId,
                    'product_id' => (int) $item['product_id'],
                    'owner_stock_id' => $ownerStock?->id,
                    'stock_id' => $ownerStock?->stock_id,
                    'user_id' => auth()->id(),
                    'type' => 'adjustment',
                    'reference_type' => OwnerStockAdjustment::class,
                    'reference_id' => $adjustment->id,
                    'qty_in' => $quantity > 0 ? $quantity : 0,
                    'qty_out' => $quantity < 0 ? abs($quantity) : 0,
                    'balance' => $balance,
                    'notes' => 'Stock opname cabang - '.($item['keterangan'] ?? 'Stock adjustment'),
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Stock opname cabang berhasil disimpan.']);
    }

    private function resolvedOwnerId(Request $request, bool $required = false): ?int
    {
        $user = auth()->user();

        if ($user?->isBranchScoped()) {
            return $user->branchId();
        }

        $ownerId = $request->integer('owner_id') ?: null;
        if ($required && ! $ownerId) {
            abort(422, 'Cabang harus dipilih.');
        }

        return $ownerId;
    }

    private function branchOptions()
    {
        $user = auth()->user();

        if ($user?->isBranchScoped()) {
            return Outlet::whereKey($user->branchId())->get();
        }

        return Outlet::branches()->orderBy('name')->get();
    }

    private function productsForOwner(?int $ownerId)
    {
        return OwnerStock::with('product:id,code,name')
            ->when($ownerId, fn ($query) => $query->where('owner_id', $ownerId))
            ->where('qty', '>', 0)
            ->selectRaw('product_id, SUM(qty) as total_qty')
            ->groupBy('product_id')
            ->orderBy('product_id')
            ->get()
            ->map(fn ($stock) => [
                'product_id' => (int) $stock->product_id,
                'product_name' => $stock->product?->name,
                'product_code' => $stock->product?->code,
                'total_qty' => (int) $stock->total_qty,
            ]);
    }

    private function increaseOwnerStockForAdjustment(int $ownerId, int $productId, float $qty): OwnerStock
    {
        $ownerStock = OwnerStock::where('owner_id', $ownerId)
            ->where('product_id', $productId)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if ($ownerStock) {
            $ownerStock->qty += $qty;
            $ownerStock->save();

            return $ownerStock;
        }

        $product = Product::findOrFail($productId);

        return OwnerStock::create([
            'owner_id' => $ownerId,
            'product_id' => $productId,
            'stock_id' => null,
            'qty' => $qty,
            'sku' => $product->code.'-OPNAME-'.now()->format('YmdHis'),
            'expired_at' => null,
            'harga_beli' => $product->harga_beli ?? 0,
        ]);
    }

    private function decreaseOwnerStockForAdjustment(int $ownerId, int $productId, float $qty): ?OwnerStock
    {
        $remaining = $qty;
        $lastTouched = null;

        $ownerStocks = OwnerStock::where('owner_id', $ownerId)
            ->where('product_id', $productId)
            ->where('qty', '>', 0)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        foreach ($ownerStocks as $ownerStock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (float) $ownerStock->qty);
            $ownerStock->qty -= $take;
            $ownerStock->save();
            $remaining -= $take;
            $lastTouched = $ownerStock;
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Stock cabang tidak cukup untuk penyesuaian opname.');
        }

        return $lastTouched;
    }

    private function ownerProductBalance(int $ownerId, int $productId): float
    {
        return (float) OwnerStock::where('owner_id', $ownerId)
            ->where('product_id', $productId)
            ->sum('qty');
    }
}
