<?php

namespace App\Services;

use App\Models\Penjualan;
use Carbon\Carbon;

class PenjualanBalanceService
{
    public function oldDebt(Penjualan $penjualan): float
    {
        if ($penjualan->old_debt_override !== null) {
            return max(0, (float) $penjualan->old_debt_override);
        }

        return $this->calculatedOldDebt($penjualan);
    }

    public function calculatedOldDebt(Penjualan $penjualan): float
    {
        return $this->calculateOldDebt(
            $penjualan->buyer_type,
            $penjualan->buyer_id,
            $penjualan->customer_id,
            $penjualan->sale_date ?: $penjualan->created_at,
            $penjualan->id,
        );
    }

    public function calculateOldDebt(
        ?string $buyerType,
        ?int $buyerId,
        ?int $customerId,
        $saleDate = null,
        ?int $excludeId = null,
    ): float {
        if ((! $buyerType || ! $buyerId) && ! $customerId) {
            return 0;
        }

        $query = Penjualan::query()->with('paymentTransaction');

        if ($buyerType && $buyerId) {
            $query->where('buyer_type', $buyerType)
                ->where('buyer_id', $buyerId);
        } else {
            $query->where('customer_id', $customerId);
        }

        if ($excludeId) {
            $query->whereKeyNot($excludeId);
        }

        if ($saleDate) {
            $date = Carbon::parse($saleDate)->toDateString();
            $query->where(function ($builder) use ($date, $excludeId) {
                $builder->whereDate('sale_date', '<', $date)
                    ->orWhere(function ($sameDay) use ($date, $excludeId) {
                        $sameDay->whereDate('sale_date', $date);

                        if ($excludeId) {
                            $sameDay->where('id', '<', $excludeId);
                        }
                    });
            });
        }

        return (float) $query->get()->sum(function (Penjualan $invoice): float {
            if ($invoice->payment_status === 'paid' && ! $invoice->paymentTransaction) {
                return 0;
            }

            $paid = (float) ($invoice->paymentTransaction?->amount ?? 0);

            return max(0, (float) $invoice->total - $paid);
        });
    }

    public function payment(Penjualan $penjualan): float
    {
        return (float) ($penjualan->paymentTransaction?->amount ?? 0);
    }

    public function newDebt(Penjualan $penjualan): float
    {
        return max(
            0,
            $this->oldDebt($penjualan)
                + (float) ($penjualan->shipping_cost ?? 0)
                + (float) ($penjualan->total ?? 0)
                - $this->payment($penjualan),
        );
    }
}
