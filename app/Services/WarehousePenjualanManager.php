<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanPayment;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\ProductUnitConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WarehousePenjualanManager
{
    public function __construct(
        private readonly ProductUnitConverter $converter
    ) {
    }

    public function resolveBuyer(string $buyerType, int $buyerId): array
    {
        $model = match ($buyerType) {
            'agent' => Agent::findOrFail($buyerId),
            'canvas' => Canvas::findOrFail($buyerId),
            'outlet' => Outlet::findOrFail($buyerId),
        };

        $label = match ($buyerType) {
            'agent' => 'Agen',
            'canvas' => 'Canvas',
            'outlet' => 'Cabang',
        };

        return [
            'id' => (int) $model->id,
            'name' => $model->name,
            'label' => $label.' '.$model->name,
            'termin_days' => (int) ($model->termin_days ?? 0),
        ];
    }

    public function create(array $payload, int $operatorId): Penjualan
    {
        return DB::transaction(function () use ($payload, $operatorId) {
            $buyer = $this->resolveBuyer($payload['buyer_type'], (int) $payload['buyer_id']);
            [$saleDate, $paymentStatus, $dueDate] = $this->resolvePaymentFields($payload);

            $penjualan = Penjualan::create([
                'code' => $payload['code'],
                'sale_channel' => 'warehouse',
                'buyer_type' => $payload['buyer_type'],
                'buyer_id' => $buyer['id'],
                'buyer_name' => $buyer['name'],
                'user_id' => $operatorId,
                'sale_date' => $saleDate->toDateString(),
                'payment_type' => $payload['payment_type'],
                'payment_status' => $paymentStatus,
                'due_date' => $dueDate?->toDateString(),
                'notes' => null,
                'discount' => (int) ($payload['discount'] ?? 0),
                'total' => 0,
            ]);

            $this->syncItems($penjualan, $payload['items'], $buyer, $operatorId);
            $this->syncPaymentTransaction($penjualan);

            return $penjualan;
        });
    }

    public function update(Penjualan $penjualan, array $payload, int $operatorId): Penjualan
    {
        if (! $penjualan->isWarehouseSale()) {
            throw new \InvalidArgumentException('Penjualan non-gudang tidak dapat diubah lewat flow ini.');
        }

        return DB::transaction(function () use ($penjualan, $payload, $operatorId) {
            $this->rollbackSale($penjualan, $operatorId);
            $this->purgeSaleItems($penjualan);

            $buyer = $this->resolveBuyer($payload['buyer_type'], (int) $payload['buyer_id']);
            [$saleDate, $paymentStatus, $dueDate] = $this->resolvePaymentFields($payload);

            $penjualan->update([
                'buyer_type' => $payload['buyer_type'],
                'buyer_id' => $buyer['id'],
                'buyer_name' => $buyer['name'],
                'user_id' => $operatorId,
                'sale_date' => $saleDate->toDateString(),
                'payment_type' => $payload['payment_type'],
                'payment_status' => $paymentStatus,
                'due_date' => $dueDate?->toDateString(),
                'notes' => null,
                'discount' => (int) ($payload['discount'] ?? 0),
                'total' => 0,
            ]);

            $this->syncItems($penjualan, $payload['items'], $buyer, $operatorId);
            $this->syncPaymentTransaction($penjualan);

            return $penjualan->fresh([
                'items.product',
                'items.allocations.stock',
                'agent',
                'canvasBuyer',
                'outletBuyer',
                'operator',
                'paymentTransaction',
            ]);
        });
    }

    private function resolvePaymentFields(array $payload): array
    {
        $saleDate = Carbon::parse($payload['sale_date']);
        $paymentType = $payload['payment_type'];
        $paymentStatus = $paymentType === 'cash'
            ? 'paid'
            : ($payload['payment_status'] ?: 'unpaid');
        $dueDate = null;

        return [$saleDate, $paymentStatus, $dueDate];
    }

    private function syncItems(Penjualan $penjualan, array $items, array $buyer, int $operatorId): void
    {
        $subtotal = 0;

        foreach ($items as $itemData) {
            $product = Product::findOrFail($itemData['product_id']);
            $inputQty = (float) $itemData['qty'];
            $unit = (string) $itemData['unit'];
            $qty = $this->converter->normalize($product, $inputQty, $unit);

            if ($qty < 1) {
                throw new \RuntimeException("Qty {$product->name} tidak valid.");
            }

            $allocations = $this->allocateWarehouseStock($product, $qty);
            $price = (int) $itemData['price'];
            $lineSubtotal = (int) round($qty * $price);
            $subtotal += $lineSubtotal;

            $saleItem = $penjualan->items()->create([
                'product_id' => $product->id,
                'stock_id' => $allocations[0]['stock']->id ?? null,
                'qty' => $qty,
                'qty_input' => $inputQty,
                'unit' => $unit,
                'price' => $price,
                'subtotal' => $lineSubtotal,
            ]);

            foreach ($allocations as $allocation) {
                $stock = $allocation['stock'];
                $allocatedQty = $allocation['qty'];

                $stock->allocate($allocatedQty);

                $saleItem->allocations()->create([
                    'stock_id' => $stock->id,
                    'qty' => $allocatedQty,
                ]);

                if ($penjualan->buyer_type === 'outlet') {
                    $this->increaseOwnerStock((int) $buyer['id'], $stock, $allocatedQty, $penjualan, $operatorId);
                }

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $operatorId,
                    'type' => 'out',
                    'reference_type' => Penjualan::class,
                    'reference_id' => $penjualan->id,
                    'qty_in' => 0,
                    'qty_out' => $allocatedQty,
                    'balance' => (int) Stock::where('product_id', $product->id)->sum('qty'),
                    'notes' => "Penjualan {$buyer['label']} - {$penjualan->code} - Produk: {$product->name}",
                ]);
            }
        }

        $penjualan->update([
            'total' => max(0, $subtotal - (int) $penjualan->discount),
        ]);
    }

    private function syncPaymentTransaction(Penjualan $penjualan): void
    {
        $transaction = $penjualan->paymentTransaction;

        if ($penjualan->payment_type === 'cash') {
            if (! $transaction) {
                $transaction = $penjualan->paymentTransaction()->create([
                    'payment_date' => $penjualan->sale_date ?? now(),
                    'payment_method' => 'cash',
                    'payment_reference' => 'CASH-'.$penjualan->code,
                    'payment_history' => [[
                        'payment_date' => optional($penjualan->sale_date)->format('Y-m-d H:i:s') ?: now()->toDateTimeString(),
                        'amount' => (int) $penjualan->total,
                        'payment_method' => 'cash',
                        'payment_reference' => 'CASH-'.$penjualan->code,
                        'notes' => 'Pembayaran cash saat penjualan dibuat/disimpan.',
                        'created_at' => now()->toDateTimeString(),
                    ]],
                    'status' => 'paid',
                    'amount' => (int) $penjualan->total,
                    'notes' => 'Pembayaran cash otomatis.',
                ]);
            } else {
                $history = $transaction->payment_history ?? [];
                if (empty($history)) {
                    $history[] = [
                        'payment_date' => optional($penjualan->sale_date)->format('Y-m-d H:i:s') ?: now()->toDateTimeString(),
                        'amount' => (int) $penjualan->total,
                        'payment_method' => 'cash',
                        'payment_reference' => $transaction->payment_reference ?: 'CASH-'.$penjualan->code,
                        'notes' => 'Pembayaran cash otomatis.',
                        'created_at' => now()->toDateTimeString(),
                    ];
                } elseif (count($history) === 1 && ($transaction->payment_reference ?: 'CASH-'.$penjualan->code) === 'CASH-'.$penjualan->code) {
                    $history[0]['amount'] = (int) $penjualan->total;
                    $history[0]['payment_reference'] = 'CASH-'.$penjualan->code;
                }

                $transaction->update([
                    'payment_date' => $transaction->payment_date ?: ($penjualan->sale_date ?? now()),
                    'payment_method' => $transaction->payment_method ?: 'cash',
                    'payment_reference' => $transaction->payment_reference ?: 'CASH-'.$penjualan->code,
                    'payment_history' => $history,
                    'status' => 'paid',
                    'amount' => (int) $penjualan->total,
                ]);
            }

            if ($penjualan->payment_status !== 'paid') {
                $penjualan->update(['payment_status' => 'paid']);
            }

            return;
        }

        if (! $transaction) {
            return;
        }

        $normalizedAmount = min((float) $transaction->amount, (float) $penjualan->total);
        $status = $normalizedAmount <= 0
            ? 'unpaid'
            : ($normalizedAmount >= (float) $penjualan->total ? 'paid' : 'partial');

        $transaction->update([
            'amount' => $normalizedAmount,
            'status' => $status,
        ]);

        if ($penjualan->payment_status !== $status) {
            $penjualan->update(['payment_status' => $status]);
        }
    }

    private function rollbackSale(Penjualan $penjualan, ?int $operatorId = null): void
    {
        $penjualan->loadMissing('items.allocations', 'items.product');

        foreach ($penjualan->items as $item) {
            if ($item->allocations->isEmpty()) {
                $this->rollbackLegacyItem($penjualan, $item);
                continue;
            }

            foreach ($item->allocations as $allocation) {
                $stock = Stock::whereKey($allocation->stock_id)->lockForUpdate()->first();
                if (! $stock) {
                    continue;
                }

                $stock->qty += (int) $allocation->qty;
                $stock->save();

                if ($penjualan->buyer_type === 'outlet') {
                    $this->decreaseOwnerStock((int) $penjualan->buyer_id, (int) $item->product_id, (int) $allocation->qty, $stock->id, $penjualan, $operatorId);
                }
            }
        }

        StockMovement::where('reference_type', Penjualan::class)
            ->where('reference_id', $penjualan->id)
            ->delete();
    }

    private function rollbackLegacyItem(Penjualan $penjualan, PenjualanItem $item): void
    {
        $qty = (int) $item->qty;

        if ($penjualan->buyer_type === 'outlet') {
            $this->decreaseOwnerStock((int) $penjualan->buyer_id, (int) $item->product_id, $qty, null, $penjualan);
        }

        $stock = null;

        if ($item->stock_id) {
            $stock = Stock::whereKey($item->stock_id)->lockForUpdate()->first();
        }

        if (! $stock) {
            $stock = Stock::where('product_id', $item->product_id)
                ->where('status', 'available')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
        }

        if ($stock) {
            $stock->qty += $qty;
            $stock->save();

            return;
        }

        $product = $item->product ?: Product::findOrFail($item->product_id);

        Stock::create([
            'product_id' => $product->id,
            'sku' => $product->code.'-RESTORE-'.$penjualan->code,
            'subtotal' => $qty * (int) ($product->harga_beli ?? 0),
            'harga_beli' => (int) ($product->harga_beli ?? 0),
            'qty' => $qty,
            'qty_reserved' => 0,
            'expired_at' => null,
            'location' => $product->lokasi,
            'condition' => 'new',
            'status' => 'available',
        ]);
    }

    private function purgeSaleItems(Penjualan $penjualan): void
    {
        $penjualan->loadMissing('items.allocations');

        foreach ($penjualan->items as $item) {
            $item->allocations()->delete();
            $item->forceDelete();
        }
    }

    private function allocateWarehouseStock(Product $product, int $requestedQty): array
    {
        $stocks = Stock::where('product_id', $product->id)
            ->where('status', 'available')
            ->where('qty_available', '>', 0)
            ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $requestedQty;
        $allocations = [];

        foreach ($stocks as $stock) {
            if ($remaining <= 0) {
                break;
            }

            $availableQty = $this->resolveStockAvailableQty($stock);
            $take = min($remaining, $availableQty);

            if ($take <= 0) {
                continue;
            }

            $allocations[] = [
                'stock' => $stock,
                'qty' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stok {$product->name} tidak mencukupi.");
        }

        return $allocations;
    }

    private function increaseOwnerStock(int $outletId, Stock $stock, int $qty, ?Penjualan $penjualan = null, ?int $operatorId = null): void
    {
        $ownerStock = OwnerStock::where('owner_id', $outletId)
            ->where('product_id', $stock->product_id)
            ->where('stock_id', $stock->id)
            ->lockForUpdate()
            ->first();

        if ($ownerStock) {
            $ownerStock->qty += $qty;
            $ownerStock->sku = $stock->sku;
            $ownerStock->expired_at = $stock->expired_at;
            $ownerStock->harga_beli = $stock->harga_beli;
            $ownerStock->save();
        } else {
            $ownerStock = OwnerStock::create([
                'owner_id' => $outletId,
                'product_id' => $stock->product_id,
                'stock_id' => $stock->id,
                'qty' => $qty,
                'sku' => $stock->sku,
                'expired_at' => $stock->expired_at,
                'harga_beli' => $stock->harga_beli,
            ]);
        }

        $this->recordOwnerStockMovement(
            $ownerStock,
            'in',
            $qty,
            0,
            $operatorId,
            $penjualan,
            $penjualan
                ? "Penjualan gudang ke cabang - {$penjualan->code} - SKU: {$stock->sku}"
                : "Stock cabang masuk dari gudang - SKU: {$stock->sku}"
        );
    }

    private function decreaseOwnerStock(int $outletId, int $productId, int $qty, ?int $stockId = null, ?Penjualan $penjualan = null, ?int $operatorId = null): void
    {
        $remaining = $qty;

        $queries = collect([
            OwnerStock::where('owner_id', $outletId)
                ->where('product_id', $productId)
                ->when($stockId, fn ($query) => $query->where('stock_id', $stockId))
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get(),
            $stockId
                ? OwnerStock::where('owner_id', $outletId)
                    ->where('product_id', $productId)
                    ->where(function ($query) use ($stockId) {
                        $query->whereNull('stock_id')
                            ->orWhere('stock_id', '!=', $stockId);
                    })
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get()
                : collect(),
        ])->flatten(1);

        foreach ($queries as $ownerStock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $ownerStock->qty);
            if ($take <= 0) {
                continue;
            }

            $ownerStock->qty -= $take;
            $ownerStock->qty = max(0, (int) $ownerStock->qty);
            $ownerStock->save();

            $remaining -= $take;

            $this->recordOwnerStockMovement(
                $ownerStock,
                'return_out',
                0,
                $take,
                $operatorId,
                $penjualan,
                $penjualan
                    ? "Rollback penjualan gudang ke cabang - {$penjualan->code}"
                    : 'Pengurangan stock cabang'
            );
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Stok cabang hasil penjualan sebelumnya tidak cukup untuk dibatalkan.');
        }
    }

    private function resolveStockAvailableQty(Stock $stock): int
    {
        if ($stock->qty_available !== null) {
            return max(0, (int) $stock->qty_available);
        }

        return max(0, (int) $stock->qty - (int) ($stock->qty_reserved ?? 0));
    }

    private function recordOwnerStockMovement(OwnerStock $ownerStock, string $type, int|float $qtyIn, int|float $qtyOut, ?int $operatorId, ?Penjualan $penjualan, string $notes): void
    {
        OwnerStockMovement::create([
            'owner_id' => $ownerStock->owner_id,
            'product_id' => $ownerStock->product_id,
            'owner_stock_id' => $ownerStock->id,
            'stock_id' => $ownerStock->stock_id,
            'user_id' => $operatorId ?: auth()->id(),
            'type' => $type,
            'reference_type' => $penjualan ? Penjualan::class : null,
            'reference_id' => $penjualan?->id,
            'qty_in' => $qtyIn,
            'qty_out' => $qtyOut,
            'balance' => OwnerStock::where('owner_id', $ownerStock->owner_id)
                ->where('product_id', $ownerStock->product_id)
                ->sum('qty'),
            'notes' => $notes,
        ]);
    }
}
