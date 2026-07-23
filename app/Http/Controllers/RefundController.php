<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundRequest;
use App\Models\Kas;
use App\Models\OwnerStock;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\Product;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function index()
    {
        return view('refunds.index', [
            'refunds' => Refund::with([
                'user',
                'customer',
                'penjualan',
                'agent',
                'canvasBuyer',
                'outletBuyer',
            ])->orderByDesc('tanggal')->orderByDesc('id')->get(),
        ]);
    }

    public function create(Request $request)
    {
        return view('refunds.create', $this->refundFormData(null, $request->integer('penjualan_id') ?: null));
    }

    public function edit(Refund $refund)
    {
        $refund->loadMissing('refundItems.product');

        return view('refunds.edit', $this->refundFormData($refund, (int) $refund->penjualan_id));
    }

    public function show(Refund $refund)
    {
        $refund->load([
            'customer',
            'outlet',
            'penjualan',
            'refundItems.product',
            'agent',
            'canvasBuyer',
            'outletBuyer',
        ]);

        return view('refunds.show', [
            'refund' => $refund,
        ]);
    }

    public function store(RefundRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $penjualan = $this->refundableSale((int) $data['penjualan_id']);
            $items = $this->normalizeRefundItems($penjualan, $data['product']);

            $refund = Refund::create($this->refundAttributes($penjualan, $data));

            foreach ($items as $item) {
                $refund->refundItems()->create($item);
            }

            $this->applyRefundImpact($refund->fresh('refundItems.product'), $penjualan);
        });

        return redirect(route('refund.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function update(RefundRequest $request, Refund $refund)
    {
        DB::transaction(function () use ($request, $refund) {
            $refund->loadMissing('refundItems.product', 'penjualan.items.allocations', 'penjualan.items.product');
            $this->rollbackRefundImpact($refund);

            $data = $request->validated();
            $penjualan = $this->refundableSale((int) $data['penjualan_id']);
            $items = $this->normalizeRefundItems($penjualan, $data['product'], $refund);

            $refund->update($this->refundAttributes($penjualan, $data));
            $refund->refundItems()->delete();

            foreach ($items as $item) {
                $refund->refundItems()->create($item);
            }

            $refund->refresh();
            $refund->load('refundItems.product');

            $this->applyRefundImpact($refund, $penjualan);
        });

        return redirect(route('refund.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(Refund $refund)
    {
        DB::transaction(function () use ($refund) {
            $refund->loadMissing('refundItems.product', 'penjualan.items.allocations', 'penjualan.items.product');
            $this->rollbackRefundImpact($refund);
            $refund->refundItems()->delete();
            $refund->delete();
        });

        return redirect(route('refund.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    private function normalizeMoney($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }

    private function refundFormData(?Refund $refund = null, ?int $selectedPenjualanId = null): array
    {
        $refundItems = RefundItem::query()
            ->selectRaw('refunds.penjualan_id, refund_items.product_id, SUM(refund_items.qty) as qty')
            ->join('refunds', 'refunds.id', '=', 'refund_items.refund_id')
            ->when($refund, fn ($query) => $query->where('refunds.id', '!=', $refund->id))
            ->groupBy('refunds.penjualan_id', 'refund_items.product_id')
            ->get()
            ->groupBy('penjualan_id');

        $penjualans = Penjualan::with([
            'items.product',
            'customer:id,name',
            'agent:id,name',
            'canvasBuyer:id,name',
            'outletBuyer:id,name',
        ])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (Penjualan $penjualan) use ($refundItems) {
                $refundLookup = collect($refundItems->get($penjualan->id))
                    ->mapWithKeys(fn ($row) => [(int) $row->product_id => (int) $row->qty]);

                return [
                    'id' => $penjualan->id,
                    'code' => $penjualan->code,
                    'buyer_type' => $penjualan->buyer_type,
                    'buyer_type_label' => $penjualan->buyer_type_label,
                    'buyer_display_name' => $penjualan->buyer_display_name,
                    'sale_channel' => $penjualan->sale_channel ?: 'retail',
                    'sale_channel_label' => $penjualan->isWarehouseSale() ? 'Gudang' : 'Retail',
                    'items' => $penjualan->items->map(function (PenjualanItem $item) use ($refundLookup) {
                        $soldQty = (int) $item->qty;
                        $refundedQty = (int) ($refundLookup[$item->product_id] ?? 0);

                        return [
                            'product_id' => (int) $item->product_id,
                            'product_name' => $item->product?->name ?? 'Produk',
                            'qty_sold' => $soldQty,
                            'qty_remaining' => max(0, $soldQty - $refundedQty),
                            'alasan' => '',
                        ];
                    })->values()->all(),
                ];
            })
            ->values();

        return [
            'refund' => $refund,
            'penjualans' => $penjualans,
            'selectedPenjualanId' => old('penjualan_id', $selectedPenjualanId),
            'initialItems' => old('product')
                ?: ($refund
                    ? $refund->refundItems->map(fn ($item) => [
                        'product_id' => (int) $item->product_id,
                        'qty' => (int) $item->qty,
                        'alasan' => $item->alasan,
                    ])->values()->all()
                    : []),
        ];
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
