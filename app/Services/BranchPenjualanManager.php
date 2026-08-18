<?php

namespace App\Services;

use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanPayment;
use App\Models\Product;
use App\Support\ProductUnitConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BranchPenjualanManager
{
    public function __construct(
        private readonly ProductUnitConverter $converter
    ) {
    }

    public function create(array $payload, int $operatorId, int $branchId, ?int $salesmanId = null): Penjualan
    {
        return DB::transaction(function () use ($payload, $operatorId, $branchId, $salesmanId) {
            $buyer = Outlet::shops()->findOrFail((int) $payload['buyer_id']);
            [$saleDate, $paymentStatus] = $this->resolvePaymentFields($payload);
            $items = $this->normalizeItemDiscounts($payload['items'], (int) ($payload['discount'] ?? 0));

            $penjualan = Penjualan::create([
                'code' => $payload['code'],
                'offline_client_id' => $payload['offline_client_id'] ?? null,
                'sale_channel' => 'branch',
                'buyer_type' => 'toko',
                'buyer_id' => $buyer->id,
                'buyer_name' => $buyer->name,
                'outlet_id' => $branchId,
                'salesman_id' => $salesmanId,
                'user_id' => $operatorId,
                'sale_date' => $saleDate->toDateString(),
                'payment_type' => $payload['payment_type'],
                'payment_status' => $paymentStatus,
                'due_date' => null,
                'notes' => $payload['notes'] ?? null,
                'shipping_cost' => (int) ($payload['shipping_cost'] ?? 0),
                'old_debt_override' => $payload['old_debt_override'] ?? null,
                // New sales keep discounts on each item. This parent field remains only for legacy records.
                'discount' => 0,
                'total' => 0,
            ]);

            $this->syncItems($penjualan, $items, $operatorId);
            $this->syncPaymentTransaction($penjualan);

            return $penjualan->fresh([
                'items.product',
                'items.allocations.ownerStock',
                'operator',
                'salesman',
                'outlet',
                'tokoBuyer',
                'paymentTransaction',
            ]);
        });
    }

    public function update(Penjualan $penjualan, array $payload, int $operatorId, int $branchId, ?int $salesmanId = null): Penjualan
    {
        if (! $penjualan->isBranchSale()) {
            throw new \InvalidArgumentException('Penjualan non-cabang tidak dapat diubah lewat flow cabang.');
        }

        return DB::transaction(function () use ($penjualan, $payload, $operatorId, $branchId, $salesmanId) {
            // Serialize edits to the same sale before rolling its branch stock
            // back and allocating the replacement lines.
            $penjualan = Penjualan::whereKey($penjualan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->rollbackSale($penjualan, $operatorId);
            $this->purgeSaleItems($penjualan);

            $buyer = Outlet::shops()->findOrFail((int) $payload['buyer_id']);
            [$saleDate, $paymentStatus] = $this->resolvePaymentFields($payload);
            $items = $this->normalizeItemDiscounts($payload['items'], (int) ($payload['discount'] ?? 0));

            $penjualan->update([
                'buyer_type' => 'toko',
                'buyer_id' => $buyer->id,
                'buyer_name' => $buyer->name,
                'outlet_id' => $branchId,
                'salesman_id' => $salesmanId ?: $penjualan->salesman_id,
                'user_id' => $operatorId,
                'sale_date' => $saleDate->toDateString(),
                'payment_type' => $payload['payment_type'],
                'payment_status' => $paymentStatus,
                'due_date' => null,
                'notes' => $payload['notes'] ?? null,
                'shipping_cost' => (int) ($payload['shipping_cost'] ?? 0),
                'old_debt_override' => $payload['old_debt_override'] ?? null,
                'discount' => 0,
                'total' => 0,
            ]);

            $this->syncItems($penjualan, $items, $operatorId);
            $this->syncPaymentTransaction($penjualan);

            return $penjualan->fresh([
                'items.product',
                'items.allocations.ownerStock',
                'operator',
                'salesman',
                'outlet',
                'tokoBuyer',
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

        return [$saleDate, $paymentStatus];
    }

    private function syncItems(Penjualan $penjualan, array $items, int $operatorId): void
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

            $allocations = $this->allocateBranchStock((int) $penjualan->outlet_id, $product, $qty);
            $price = (int) $itemData['price'];
            $lineGrossSubtotal = (int) round($qty * $price);
            $lineDiscount = max(0, (int) ($itemData['discount'] ?? 0));

            if ($lineDiscount > $lineGrossSubtotal) {
                throw new \RuntimeException("Diskon produk {$product->name} tidak boleh melebihi subtotal item.");
            }

            $lineSubtotal = $lineGrossSubtotal - $lineDiscount;
            $subtotal += $lineSubtotal;

            $saleItem = $penjualan->items()->create([
                'product_id' => $product->id,
                'stock_id' => $allocations[0]['owner_stock']->stock_id ?? null,
                'qty' => $qty,
                'qty_input' => $inputQty,
                'unit' => $unit,
                'price' => $price,
                'discount' => $lineDiscount,
                'subtotal' => $lineSubtotal,
            ]);

            foreach ($allocations as $allocation) {
                /** @var OwnerStock $ownerStock */
                $ownerStock = $allocation['owner_stock'];
                $allocatedQty = $allocation['qty'];

                $ownerStock->qty -= $allocatedQty;
                $ownerStock->qty = max(0, (float) $ownerStock->qty);
                $ownerStock->save();

                $saleItem->allocations()->create([
                    'stock_id' => $ownerStock->stock_id,
                    'owner_stock_id' => $ownerStock->id,
                    'qty' => $allocatedQty,
                ]);

                OwnerStockMovement::create([
                    'owner_id' => $penjualan->outlet_id,
                    'product_id' => $product->id,
                    'owner_stock_id' => $ownerStock->id,
                    'stock_id' => $ownerStock->stock_id,
                    'user_id' => $operatorId,
                    'type' => 'out',
                    'reference_type' => Penjualan::class,
                    'reference_id' => $penjualan->id,
                    'qty_in' => 0,
                    'qty_out' => $allocatedQty,
                    'balance' => $this->branchProductBalance((int) $penjualan->outlet_id, $product->id),
                    'notes' => "Penjualan cabang ke customer/toko - {$penjualan->code} - Produk: {$product->name}",
                ]);
            }
        }

        $penjualan->update([
            'total' => max(0, $subtotal),
        ]);
    }

    private function normalizeItemDiscounts(array $items, int $legacyDiscount): array
    {
        if ($legacyDiscount <= 0 || collect($items)->contains(fn (array $item) => array_key_exists('discount', $item))) {
            return $items;
        }

        return collect($items)
            ->values()
            ->map(function (array $item, int $index) use ($legacyDiscount) {
                if ($index === 0) {
                    $item['discount'] = $legacyDiscount;
                }

                return $item;
            })
            ->all();
    }

    private function rollbackSale(Penjualan $penjualan, int $operatorId): void
    {
        $penjualan->loadMissing('items.allocations.ownerStock', 'items.product');

        foreach ($penjualan->items as $item) {
            foreach ($item->allocations as $allocation) {
                $ownerStock = OwnerStock::whereKey($allocation->owner_stock_id)->lockForUpdate()->first();

                if (! $ownerStock) {
                    $product = $item->product ?: Product::findOrFail($item->product_id);
                    $ownerStock = OwnerStock::create([
                        'owner_id' => $penjualan->outlet_id,
                        'product_id' => $item->product_id,
                        'stock_id' => $allocation->stock_id,
                        'qty' => 0,
                        'sku' => $product->code.'-RESTORE-'.$penjualan->code,
                        'expired_at' => null,
                        'harga_beli' => $product->harga_beli ?? 0,
                    ]);
                }

                $ownerStock->qty += (float) $allocation->qty;
                $ownerStock->save();

                OwnerStockMovement::create([
                    'owner_id' => $penjualan->outlet_id,
                    'product_id' => $item->product_id,
                    'owner_stock_id' => $ownerStock->id,
                    'stock_id' => $ownerStock->stock_id,
                    'user_id' => $operatorId,
                    'type' => 'rollback_in',
                    'reference_type' => Penjualan::class,
                    'reference_id' => $penjualan->id,
                    'qty_in' => (float) $allocation->qty,
                    'qty_out' => 0,
                    'balance' => $this->branchProductBalance((int) $penjualan->outlet_id, (int) $item->product_id),
                    'notes' => "Rollback penjualan cabang - {$penjualan->code}",
                ]);
            }
        }
    }

    private function purgeSaleItems(Penjualan $penjualan): void
    {
        $penjualan->loadMissing('items.allocations');

        foreach ($penjualan->items as $item) {
            $item->allocations()->delete();
            $item->forceDelete();
        }
    }

    private function allocateBranchStock(int $branchId, Product $product, int $requestedQty): array
    {
        $ownerStocks = OwnerStock::where('owner_id', $branchId)
            ->where('product_id', $product->id)
            ->where('qty', '>', 0)
            ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $requestedQty;
        $allocations = [];

        foreach ($ownerStocks as $ownerStock) {
            if ($remaining <= 0) {
                break;
            }

            $take = min($remaining, (int) $ownerStock->qty);
            if ($take <= 0) {
                continue;
            }

            $allocations[] = [
                'owner_stock' => $ownerStock,
                'qty' => $take,
            ];

            $remaining -= $take;
        }

        if ($remaining > 0) {
            throw new \RuntimeException("Stock cabang {$product->name} tidak mencukupi.");
        }

        return $allocations;
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
                        'notes' => 'Pembayaran cash saat penjualan cabang dibuat/disimpan.',
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

    private function branchProductBalance(int $branchId, int $productId): float
    {
        return (float) OwnerStock::where('owner_id', $branchId)
            ->where('product_id', $productId)
            ->sum('qty');
    }
}
