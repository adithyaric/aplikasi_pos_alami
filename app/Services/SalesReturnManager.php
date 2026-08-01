<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use App\Models\OwnerStock;
use App\Models\OwnerStockMovement;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\PenjualanTotalAdjustment;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Support\ProductUnitConverter;
use Illuminate\Support\Facades\DB;

class SalesReturnManager
{
    public const SCOPE_WAREHOUSE_AFFILIATE = 'warehouse_affiliate_return';
    public const SCOPE_WAREHOUSE_BRANCH = 'warehouse_branch_return';
    public const SCOPE_BRANCH_CUSTOMER = 'branch_customer_return';

    public function __construct(
        private readonly ProductUnitConverter $converter
    ) {
    }

    public function create(array $payload, int $userId): Refund
    {
        return DB::transaction(fn () => $this->persist($payload, $userId));
    }

    public function update(Refund $refund, array $payload, int $userId): Refund
    {
        return DB::transaction(function () use ($refund, $payload, $userId) {
            $this->rollback($refund, false);

            return $this->persist($payload, $userId, $refund);
        });
    }

    public function approve(Refund $refund, int $userId, ?string $approvalNote = null): Refund
    {
        return DB::transaction(function () use ($refund, $userId, $approvalNote) {
            /** @var Refund $lockedRefund */
            $lockedRefund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();
            $lockedRefund->loadMissing('refundItems.product', 'refundItems.sourceOwnerStock.product', 'appliedPenjualan');

            if (! $lockedRefund->isPendingApproval() || $lockedRefund->return_scope !== self::SCOPE_WAREHOUSE_BRANCH) {
                throw new \RuntimeException('Retur ini tidak menunggu approval superadmin.');
            }

            $invoice = Penjualan::whereKey($lockedRefund->applied_penjualan_id)->lockForUpdate()->first();
            if (! $invoice) {
                throw new \RuntimeException('Invoice cabang untuk retur ini tidak ditemukan.');
            }

            if ($invoice->payment_status === 'paid') {
                throw new \RuntimeException('Invoice cabang sudah lunas dan tidak bisa dipotong.');
            }

            $returnTotal = (int) $lockedRefund->total;
            $invoiceTotalBefore = (float) $invoice->total;
            $invoiceTotalAfter = $invoiceTotalBefore - $returnTotal;

            if ($returnTotal <= 0 || $invoiceTotalAfter <= 0) {
                throw new \RuntimeException('Retur tidak boleh membuat total invoice menjadi nol atau negatif.');
            }

            $this->applyStoredWarehouseBranchStock($lockedRefund, $userId);

            $invoice->update(['total' => $invoiceTotalAfter]);

            PenjualanTotalAdjustment::create([
                'penjualan_id' => $invoice->id,
                'refund_id' => $lockedRefund->id,
                'type' => 'sales_return',
                'amount' => $returnTotal,
                'total_before' => $invoiceTotalBefore,
                'total_after' => $invoiceTotalAfter,
                'user_id' => $userId,
                'notes' => "Retur penjualan {$lockedRefund->code}",
            ]);

            $this->syncPaymentAfterAdjustment($invoice);

            $lockedRefund->update([
                'status' => Refund::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'approval_note' => $approvalNote,
                'invoice_total_before' => $invoiceTotalBefore,
                'invoice_total_after' => $invoiceTotalAfter,
            ]);

            return $lockedRefund->fresh([
                'refundItems.product',
                'refundItems.sourceOwnerStock.product',
                'appliedPenjualan',
                'approver',
                'totalAdjustment',
            ]);
        });
    }

    public function reject(Refund $refund, int $userId, ?string $approvalNote = null): Refund
    {
        return DB::transaction(function () use ($refund, $userId, $approvalNote) {
            /** @var Refund $lockedRefund */
            $lockedRefund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if (! $lockedRefund->isPendingApproval() || $lockedRefund->return_scope !== self::SCOPE_WAREHOUSE_BRANCH) {
                throw new \RuntimeException('Retur ini tidak menunggu approval superadmin.');
            }

            $lockedRefund->update([
                'status' => Refund::STATUS_REJECTED,
                'approved_by' => $userId,
                'approved_at' => now(),
                'approval_note' => $approvalNote,
            ]);

            return $lockedRefund->fresh(['approver']);
        });
    }

    public function rollback(Refund $refund, bool $deleteRefund = true): void
    {
        $refund->loadMissing('refundItems.sourceOwnerStock.product', 'appliedPenjualan', 'totalAdjustment');

        $invoice = $refund->appliedPenjualan;
        $adjustment = $refund->totalAdjustment;

        if ($refund->hasAppliedEffects() && $invoice && $adjustment) {
            $invoice->update(['total' => $adjustment->total_before]);
            $adjustment->delete();
            $this->syncPaymentAfterAdjustment($invoice);
        }

        if ($refund->hasAppliedEffects() && $refund->return_scope === self::SCOPE_WAREHOUSE_BRANCH) {
            $this->rollbackWarehouseBranchStock($refund);
        }

        if ($refund->hasAppliedEffects() && $refund->return_scope === self::SCOPE_BRANCH_CUSTOMER) {
            $this->rollbackBranchCustomerStock($refund);
        }

        OwnerStockMovement::where('reference_type', Refund::class)
            ->where('reference_id', $refund->id)
            ->delete();

        StockMovement::where('reference_type', Refund::class)
            ->where('reference_id', $refund->id)
            ->delete();

        $refund->refundItems()->delete();

        if ($deleteRefund) {
            $refund->delete();
        }
    }

    public function latestInvoice(array $payload, bool $lock = false): ?Penjualan
    {
        $query = Penjualan::query()
            ->where(function ($builder) {
                $builder->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'paid');
            })
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        $scope = $payload['return_scope'];

        if ($scope === self::SCOPE_BRANCH_CUSTOMER) {
            $query->where('sale_channel', 'branch')
                ->where('outlet_id', (int) $payload['source_outlet_id'])
                ->where('buyer_type', 'toko')
                ->where('buyer_id', (int) $payload['buyer_id']);
        } else {
            $query->where('sale_channel', 'warehouse')
                ->where('buyer_type', $payload['buyer_type'])
                ->where('buyer_id', (int) $payload['buyer_id']);
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function lastPrice(array $payload, int $productId): ?int
    {
        $query = PenjualanItem::query()
            ->select('penjualan_items.price')
            ->join('penjualans', 'penjualans.id', '=', 'penjualan_items.penjualan_id')
            ->where('penjualan_items.product_id', $productId)
            ->where('penjualans.buyer_type', $payload['buyer_type'])
            ->where('penjualans.buyer_id', (int) $payload['buyer_id']);

        if ($payload['return_scope'] === self::SCOPE_BRANCH_CUSTOMER) {
            $query->where('penjualans.sale_channel', 'branch')
                ->where('penjualans.outlet_id', (int) $payload['source_outlet_id']);
        } else {
            $query->where('penjualans.sale_channel', 'warehouse');
        }

        $price = $query->orderByDesc('penjualans.sale_date')
            ->orderByDesc('penjualan_items.id')
            ->value('price');

        return $price !== null ? (int) $price : null;
    }

    private function persist(array $payload, int $userId, ?Refund $refund = null): Refund
    {
        $invoice = $this->latestInvoice($payload, true);
        if (! $invoice) {
            throw new \RuntimeException('Tidak ada invoice unpaid terbaru untuk pembeli ini.');
        }

        $items = $this->normalizedItems($payload['product'] ?? []);
        $returnTotal = (int) collect($items)->sum('subtotal');
        $invoiceTotalBefore = (float) $invoice->total;
        $invoiceTotalAfter = $invoiceTotalBefore - $returnTotal;

        if ($returnTotal <= 0) {
            throw new \RuntimeException('Total retur harus lebih dari 0.');
        }

        if ($invoiceTotalAfter <= 0) {
            throw new \RuntimeException('Retur tidak boleh membuat total invoice menjadi nol atau negatif.');
        }

        $buyer = $this->resolveBuyer($payload);
        $requiresApproval = $this->shouldCreatePendingWarehouseBranchReturn($payload);
        $attributes = [
            'code' => $payload['code'],
            'kas_id' => null,
            'customer_id' => null,
            'penjualan_id' => null,
            'applied_penjualan_id' => $invoice->id,
            'outlet_id' => $payload['buyer_type'] === 'outlet' ? (int) $payload['buyer_id'] : ($payload['source_outlet_id'] ?? null),
            'source_outlet_id' => $payload['source_outlet_id'] ?? null,
            'return_scope' => $payload['return_scope'],
            'sale_channel' => $payload['return_scope'] === self::SCOPE_BRANCH_CUSTOMER ? 'branch' : 'warehouse',
            'buyer_type' => $payload['buyer_type'],
            'buyer_id' => (int) $payload['buyer_id'],
            'buyer_name' => $buyer?->name ?? $invoice->buyer_display_name,
            'salesman_id' => $payload['salesman_id'] ?? null,
            'user_id' => $userId,
            'tanggal' => $payload['tanggal'],
            'total' => $returnTotal,
            'invoice_total_before' => $invoiceTotalBefore,
            'invoice_total_after' => $invoiceTotalAfter,
            'notes' => $payload['notes'] ?? null,
            'status' => $requiresApproval ? Refund::STATUS_PENDING : Refund::STATUS_APPROVED,
            'approved_by' => null,
            'approved_at' => null,
            'approval_note' => null,
        ];

        if ($refund) {
            $refund->update($attributes);
        } else {
            $refund = Refund::create($attributes);
        }

        if ($requiresApproval) {
            $this->storePendingWarehouseBranchItems($refund, $items, (int) $payload['buyer_id']);

            return $refund->fresh(['refundItems.product', 'appliedPenjualan', 'approver']);
        }

        $invoice->update(['total' => $invoiceTotalAfter]);

        PenjualanTotalAdjustment::create([
            'penjualan_id' => $invoice->id,
            'refund_id' => $refund->id,
            'type' => 'sales_return',
            'amount' => $returnTotal,
            'total_before' => $invoiceTotalBefore,
            'total_after' => $invoiceTotalAfter,
            'user_id' => $userId,
            'notes' => "Retur penjualan {$refund->code}",
        ]);

        $this->syncPaymentAfterAdjustment($invoice);

        match ($payload['return_scope']) {
            self::SCOPE_WAREHOUSE_AFFILIATE => $this->storeAffiliateItems($refund, $items),
            self::SCOPE_WAREHOUSE_BRANCH => $this->applyWarehouseBranchStock($refund, $items, (int) $payload['buyer_id'], $userId),
            self::SCOPE_BRANCH_CUSTOMER => $this->applyBranchCustomerStock($refund, $items, (int) $payload['source_outlet_id'], $userId),
            default => throw new \RuntimeException('Scope retur tidak valid.'),
        };

        return $refund->fresh(['refundItems.product', 'appliedPenjualan', 'totalAdjustment']);
    }

    private function shouldCreatePendingWarehouseBranchReturn(array $payload): bool
    {
        return ($payload['requires_superadmin_approval'] ?? false)
            && $payload['return_scope'] === self::SCOPE_WAREHOUSE_BRANCH;
    }

    private function normalizedItems(array $items): array
    {
        $normalized = collect($items)
            ->filter(fn ($item) => ! empty($item['product_id']) && (float) ($item['qty'] ?? 0) > 0)
            ->map(function ($item) {
                $product = Product::findOrFail((int) $item['product_id']);
                $qtyInput = (float) $item['qty'];
                $unit = (string) ($item['unit'] ?: ($product->satuan ?: 'PCS'));
                $qty = $this->converter->normalize($product, $qtyInput, $unit);
                $price = (int) ($item['price'] ?? 0);

                if ($qty < 1) {
                    throw new \RuntimeException("Qty retur {$product->name} tidak valid.");
                }

                if ($price <= 0) {
                    throw new \RuntimeException("Harga retur {$product->name} harus lebih dari 0.");
                }

                return [
                    'product' => $product,
                    'product_id' => $product->id,
                    'qty_input' => $qtyInput,
                    'unit' => $unit,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => (int) round($qty * $price),
                    'alasan' => $item['alasan'] ?? null,
                ];
            })
            ->values();

        if ($normalized->isEmpty()) {
            throw new \RuntimeException('Minimal harus ada satu produk retur.');
        }

        return $normalized->all();
    }

    private function storeAffiliateItems(Refund $refund, array $items): void
    {
        foreach ($items as $item) {
            $refund->refundItems()->create([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'qty_input' => $item['qty_input'],
                'unit' => $item['unit'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
                'stock_visibility' => 'hidden',
                'alasan' => $item['alasan'],
            ]);
        }
    }

    private function applyWarehouseBranchStock(Refund $refund, array $items, int $branchId, int $userId): void
    {
        foreach ($items as $item) {
            $remaining = $item['qty'];
            $ownerStocks = $this->branchOwnerStocks($branchId, $item['product_id']);

            foreach ($ownerStocks as $ownerStock) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (int) $ownerStock->qty);
                if ($take <= 0) {
                    continue;
                }

                $ownerStock->qty -= $take;
                $ownerStock->save();

                $warehouseStock = $this->resolveWarehouseStockForBranchReturn($ownerStock, $item['product'], $refund);
                $warehouseStock->qty += $take;
                $warehouseStock->save();

                if (! $ownerStock->stock_id) {
                    $ownerStock->stock_id = $warehouseStock->id;
                    $ownerStock->save();
                }

                $refundItem = $refund->refundItems()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $take,
                    'qty_input' => $take,
                    'unit' => $item['product']->satuan ?: 'PCS',
                    'price' => $item['price'],
                    'subtotal' => (int) round($take * $item['price']),
                    'stock_visibility' => 'visible',
                    'source_owner_stock_id' => $ownerStock->id,
                    'alasan' => $item['alasan'],
                ]);

                OwnerStockMovement::create([
                    'owner_id' => $branchId,
                    'product_id' => $item['product_id'],
                    'owner_stock_id' => $ownerStock->id,
                    'stock_id' => $warehouseStock->id,
                    'user_id' => $userId,
                    'type' => 'return_out',
                    'reference_type' => Refund::class,
                    'reference_id' => $refund->id,
                    'qty_in' => 0,
                    'qty_out' => $take,
                    'balance' => $this->branchProductBalance($branchId, $item['product_id']),
                    'notes' => "Retur cabang ke gudang - {$refund->code} - Produk: {$item['product']->name}",
                ]);

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'user_id' => $userId,
                    'type' => 'in',
                    'reference_type' => Refund::class,
                    'reference_id' => $refund->id,
                    'qty_in' => $take,
                    'qty_out' => 0,
                    'balance' => Stock::where('product_id', $item['product_id'])->sum('qty'),
                    'notes' => "Retur penjualan cabang ke gudang - {$refund->code} - Item retur #{$refundItem->id}",
                ]);

                $remaining -= $take;
            }

            if ($remaining > 0) {
                throw new \RuntimeException("Stock cabang {$item['product']->name} tidak cukup untuk retur.");
            }
        }
    }

    private function storePendingWarehouseBranchItems(Refund $refund, array $items, int $branchId): void
    {
        foreach ($items as $item) {
            $remaining = $item['qty'];
            $ownerStocks = $this->branchOwnerStocks($branchId, $item['product_id']);

            foreach ($ownerStocks as $ownerStock) {
                if ($remaining <= 0) {
                    break;
                }

                $take = min($remaining, (int) $ownerStock->qty);
                if ($take <= 0) {
                    continue;
                }

                $refund->refundItems()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $take,
                    'qty_input' => $take,
                    'unit' => $item['product']->satuan ?: 'PCS',
                    'price' => $item['price'],
                    'subtotal' => (int) round($take * $item['price']),
                    'stock_visibility' => 'visible',
                    'source_owner_stock_id' => $ownerStock->id,
                    'alasan' => $item['alasan'],
                ]);

                $remaining -= $take;
            }

            if ($remaining > 0) {
                throw new \RuntimeException("Stock cabang {$item['product']->name} tidak cukup untuk retur.");
            }
        }
    }

    private function applyBranchCustomerStock(Refund $refund, array $items, int $branchId, int $userId): void
    {
        foreach ($items as $item) {
            $product = $item['product'];
            $ownerStock = OwnerStock::create([
                'owner_id' => $branchId,
                'product_id' => $item['product_id'],
                'stock_id' => null,
                'qty' => $item['qty'],
                'sku' => $product->code.'-RETUR-'.$refund->code,
                'expired_at' => null,
                'harga_beli' => $product->harga_beli ?? 0,
            ]);

            $refund->refundItems()->create([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'qty_input' => $item['qty_input'],
                'unit' => $item['unit'],
                'price' => $item['price'],
                'subtotal' => $item['subtotal'],
                'stock_visibility' => 'visible',
                'source_owner_stock_id' => $ownerStock->id,
                'alasan' => $item['alasan'],
            ]);

            OwnerStockMovement::create([
                'owner_id' => $branchId,
                'product_id' => $item['product_id'],
                'owner_stock_id' => $ownerStock->id,
                'stock_id' => null,
                'user_id' => $userId,
                'type' => 'return_in',
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'qty_in' => $item['qty'],
                'qty_out' => 0,
                'balance' => $this->branchProductBalance($branchId, $item['product_id']),
                'notes' => "Retur customer/toko ke cabang - {$refund->code} - Produk: {$product->name}",
            ]);
        }
    }

    private function rollbackWarehouseBranchStock(Refund $refund): void
    {
        foreach ($refund->refundItems as $item) {
            $ownerStock = $item->sourceOwnerStock;
            if (! $ownerStock) {
                continue;
            }

            $ownerStock->qty += (float) $item->qty;
            $ownerStock->save();

            if ($ownerStock->stock_id) {
                $stock = Stock::whereKey($ownerStock->stock_id)->lockForUpdate()->first();
                if ($stock) {
                    $stock->qty = max(0, (float) $stock->qty - (float) $item->qty);
                    $stock->save();
                }
            }
        }
    }

    private function applyStoredWarehouseBranchStock(Refund $refund, int $userId): void
    {
        foreach ($refund->refundItems as $refundItem) {
            $ownerStock = OwnerStock::whereKey($refundItem->source_owner_stock_id)->lockForUpdate()->first();
            if (! $ownerStock) {
                throw new \RuntimeException("Batch stock cabang untuk {$refundItem->product?->name} tidak ditemukan.");
            }

            if ((float) $ownerStock->qty < (float) $refundItem->qty) {
                throw new \RuntimeException("Stock cabang {$refundItem->product?->name} tidak cukup saat approval.");
            }

            $ownerStock->qty -= (float) $refundItem->qty;
            $ownerStock->save();

            $warehouseStock = $this->resolveWarehouseStockForBranchReturn($ownerStock, $refundItem->product, $refund);
            $warehouseStock->qty += (float) $refundItem->qty;
            $warehouseStock->save();

            if (! $ownerStock->stock_id) {
                $ownerStock->stock_id = $warehouseStock->id;
                $ownerStock->save();
            }

            OwnerStockMovement::create([
                'owner_id' => $ownerStock->owner_id,
                'product_id' => $refundItem->product_id,
                'owner_stock_id' => $ownerStock->id,
                'stock_id' => $warehouseStock->id,
                'user_id' => $userId,
                'type' => 'return_out',
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'qty_in' => 0,
                'qty_out' => $refundItem->qty,
                'balance' => $this->branchProductBalance((int) $ownerStock->owner_id, (int) $refundItem->product_id),
                'notes' => "Retur cabang ke gudang - {$refund->code} - Produk: {$refundItem->product?->name}",
            ]);

            StockMovement::create([
                'product_id' => $refundItem->product_id,
                'user_id' => $userId,
                'type' => 'in',
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'qty_in' => $refundItem->qty,
                'qty_out' => 0,
                'balance' => Stock::where('product_id', $refundItem->product_id)->sum('qty'),
                'notes' => "Retur penjualan cabang ke gudang - {$refund->code} - Item retur #{$refundItem->id}",
            ]);
        }
    }

    private function rollbackBranchCustomerStock(Refund $refund): void
    {
        foreach ($refund->refundItems as $item) {
            $ownerStock = $item->sourceOwnerStock;
            if (! $ownerStock) {
                continue;
            }

            $ownerStock->qty = max(0, (float) $ownerStock->qty - (float) $item->qty);
            $ownerStock->save();
        }
    }

    private function resolveWarehouseStockForBranchReturn(OwnerStock $ownerStock, Product $product, Refund $refund): Stock
    {
        if ($ownerStock->stock_id) {
            $stock = Stock::whereKey($ownerStock->stock_id)->lockForUpdate()->first();
            if ($stock) {
                return $stock;
            }
        }

        return Stock::create([
            'product_id' => $product->id,
            'sku' => $product->code.'-RETUR-CABANG-'.$refund->code,
            'subtotal' => 0,
            'harga_beli' => $ownerStock->harga_beli ?? $product->harga_beli ?? 0,
            'qty' => 0,
            'qty_reserved' => 0,
            'expired_at' => $ownerStock->expired_at,
            'location' => $product->lokasi,
            'condition' => 'used',
            'status' => 'available',
        ]);
    }

    private function resolveBuyer(array $payload): Agent|Canvas|Outlet|null
    {
        return match ($payload['buyer_type']) {
            'agent' => Agent::find((int) $payload['buyer_id']),
            'canvas' => Canvas::find((int) $payload['buyer_id']),
            'outlet', 'toko' => Outlet::find((int) $payload['buyer_id']),
            default => null,
        };
    }

    private function syncPaymentAfterAdjustment(Penjualan $invoice): void
    {
        $payment = $invoice->paymentTransaction;
        if (! $payment) {
            return;
        }

        $amount = min((float) $payment->amount, (float) $invoice->total);
        $status = $amount <= 0
            ? 'unpaid'
            : ($amount >= (float) $invoice->total ? 'paid' : 'partial');

        $payment->update([
            'amount' => $amount,
            'status' => $status,
        ]);

        $invoice->update(['payment_status' => $status]);
    }

    private function branchProductBalance(int $branchId, int $productId): float
    {
        return (float) OwnerStock::where('owner_id', $branchId)
            ->where('product_id', $productId)
            ->sum('qty');
    }

    private function branchOwnerStocks(int $branchId, int $productId)
    {
        return OwnerStock::where('owner_id', $branchId)
            ->where('product_id', $productId)
            ->where('qty', '>', 0)
            ->orderByRaw('CASE WHEN expired_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expired_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
