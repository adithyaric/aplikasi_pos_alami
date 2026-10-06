<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    public const DEBIT_NORMAL_TYPES = ['BANK', 'AREC', 'INTR', 'OCAS', 'FASS', 'OASS', 'EXPS', 'COGS'];

    public const PROFIT_LOSS_TYPES = ['REVE', 'OINC', 'COGS', 'EXPS', 'OEXP'];

    public const BALANCE_SHEET_TYPES = ['BANK', 'AREC', 'INTR', 'OCAS', 'FASS', 'OASS', 'DEPR', 'APAY', 'OCLY', 'LTLY', 'EQTY'];

    public function createJournal(
        $date,
        ?string $refType,
        ?int $refId,
        ?string $description,
        array $details,
        ?string $sourceKey = null,
        bool $isManual = false,
        ?int $outletId = null
    ): Journal {
        $normalized = collect($details)
            ->map(function (array $detail): array {
                $debit = round(max(0, (float) ($detail['debit'] ?? 0)), 2);
                $credit = round(max(0, (float) ($detail['credit'] ?? 0)), 2);

                if ($debit > 0 && $credit > 0) {
                    throw new InvalidArgumentException('Satu baris jurnal hanya boleh berisi debit atau kredit.');
                }

                return [
                    'account_id' => (int) ($detail['account_id'] ?? 0),
                    'debit' => $debit,
                    'credit' => $credit,
                ];
            })
            ->filter(fn (array $detail) => $detail['debit'] > 0 || $detail['credit'] > 0)
            ->values();

        if ($normalized->isEmpty()) {
            throw new InvalidArgumentException('Jurnal harus memiliki minimal satu baris nominal.');
        }

        $totalDebit = round((float) $normalized->sum('debit'), 2);
        $totalCredit = round((float) $normalized->sum('credit'), 2);
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new InvalidArgumentException('Jurnal tidak balance. Total debit dan kredit harus sama.');
        }

        $accountIds = $normalized->pluck('account_id')->unique()->filter();
        $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');
        foreach ($accountIds as $accountId) {
            $account = $accounts->get($accountId);
            if (! $account || ! $account->is_active || $account->is_header) {
                throw new InvalidArgumentException('Akun jurnal tidak valid atau masih merupakan akun header.');
            }
        }

        return DB::transaction(function () use ($date, $refType, $refId, $description, $normalized, $sourceKey, $isManual, $outletId) {
            $dateValue = $date ?: now()->toDateString();
            $journal = $sourceKey
                ? Journal::where('source_key', $sourceKey)->lockForUpdate()->first()
                : null;

            if ($journal) {
                $prefix = 'JRN/'.date('Y/m', strtotime((string) $dateValue)).'/';
                $journalNumber = str_starts_with($journal->journal_number, $prefix)
                    ? $journal->journal_number
                    : $this->nextJournalNumber($dateValue, $journal->id);

                $journal->update([
                    'journal_number' => $journalNumber,
                    'transaction_date' => $dateValue,
                    'ref_type' => $refType,
                    'ref_id' => $refId,
                    'description' => $description,
                    'is_manual' => $isManual,
                    'outlet_id' => $outletId,
                ]);
                $journal->details()->delete();
            } else {
                $journal = Journal::create([
                    'journal_number' => $this->nextJournalNumber($dateValue),
                    'transaction_date' => $dateValue,
                    'ref_type' => $refType,
                    'ref_id' => $refId,
                    'source_key' => $sourceKey,
                    'description' => $description,
                    'is_manual' => $isManual,
                    'outlet_id' => $outletId,
                ]);
            }

            $journal->details()->createMany($normalized->all());

            return $journal->load('details.account');
        });
    }

    public function updateManualJournal(Journal $journal, array $payload): Journal
    {
        if (! $journal->is_manual) {
            throw new InvalidArgumentException('Jurnal otomatis tidak dapat diubah dari menu manual.');
        }

        return DB::transaction(function () use ($journal, $payload) {
            $journal->update([
                'transaction_date' => $payload['transaction_date'],
                'description' => $payload['description'] ?? null,
                'outlet_id' => $payload['outlet_id'] ?? null,
            ]);
            $journal->details()->delete();

            $replacement = $this->validateDetails($payload['details'] ?? []);
            $journal->details()->createMany($replacement->all());

            return $journal->fresh('details.account');
        });
    }

    public function deleteJournal(Journal $journal): void
    {
        if (! $journal->is_manual) {
            throw new InvalidArgumentException('Jurnal otomatis tidak dapat dihapus dari menu manual.');
        }

        $journal->delete();
    }

    public function syncSale(Penjualan $sale): void
    {
        $sale->loadMissing([
            'items.product',
            'items.stock',
            'items.allocations.stock',
            'paymentTransaction',
        ]);

        if (! $sale->isWarehouseSale() && ! $sale->isBranchSale()) {
            $this->deleteSourceFamily('SALES:'.$sale->id, 'SALES_PAYMENT:'.$sale->id.':%');
            return;
        }

        // Payment lines can disappear when a transaction is changed from paid
        // to unpaid. Remove only those stale child journals; the source journal
        // itself is updated in-place by createJournal().
        $this->deletePaymentJournals('SALES_PAYMENT:'.$sale->id.':%');

        $total = round((float) ($sale->total ?? 0), 2);
        if ($total <= 0) {
            return;
        }

        $salesAccount = $this->settingAccount('DEFAULT_ACC_SALES');
        $receivableAccount = $this->settingAccount('DEFAULT_ACC_AR');
        $cashAccount = $this->paymentAccount($sale->paymentTransaction?->account_id, $sale->kas_id ?? null);
        $isCashSale = $sale->payment_type === 'cash';

        $details = [
            ['account_id' => $isCashSale ? $cashAccount->id : $receivableAccount->id, 'debit' => $total, 'credit' => 0],
            ['account_id' => $salesAccount->id, 'debit' => 0, 'credit' => $total],
        ];

        $hpp = $this->saleCost($sale);
        if ($hpp > 0) {
            $details[] = ['account_id' => $this->settingAccount('DEFAULT_ACC_COGS')->id, 'debit' => $hpp, 'credit' => 0];
            $details[] = ['account_id' => $this->settingAccount('DEFAULT_ACC_INVENTORY')->id, 'debit' => 0, 'credit' => $hpp];
        }

        $this->createJournal(
            $sale->sale_date ?: $sale->created_at,
            'SALES',
            $sale->id,
            'Penjualan '.$sale->code.' - '.$sale->buyer_display_name,
            $details,
            'SALES:'.$sale->id,
            false,
            $sale->isBranchSale() ? $sale->outlet_id : null
        );

        if (! $isCashSale) {
            $this->syncPaymentHistory(
                $sale->paymentTransaction,
                $sale->paymentTransaction?->payment_history ?? [],
                $receivableAccount,
                $cashAccount,
                $sale->id,
                'SALES_PAYMENT',
                fn (array $history) => $history['payment_date'] ?? $sale->sale_date ?? $sale->created_at,
                'Pembayaran penjualan '.$sale->code,
                false,
                $sale->isBranchSale() ? $sale->outlet_id : null
            );
        }
    }

    public function syncPurchase(Pembelian $purchase): void
    {
        $purchase->loadMissing(['pembelianProducts', 'stocks', 'pembelianTransaction']);
        $this->deletePaymentJournals('PURCHASE_PAYMENT:'.$purchase->id.':%');
        $payableAccount = $this->settingAccount('DEFAULT_ACC_AP');
        $cashAccount = $this->paymentAccount($purchase->pembelianTransaction?->account_id, $purchase->kas_id ?? null);
        if (! $purchase->is_published && $purchase->receipt_status !== 'completed') {
            Journal::where('source_key', 'PURCHASE:'.$purchase->id)->delete();
        } else {
            $value = round((float) $purchase->stocks->sum('subtotal'), 2);
            if ($value <= 0) {
                $value = round((float) $purchase->pembelianProducts->sum(function ($item) {
                    return ((float) ($item->qty_diterima ?? $item->qty ?? 0)) * (float) ($item->harga_beli ?? 0);
                }), 2);
            }

            if ($value > 0) {
                $this->createJournal(
                    $purchase->receipt_date ?: $purchase->created_at,
                    'PURCHASE',
                    $purchase->id,
                    'Penerimaan pembelian '.$purchase->code,
                    [
                        ['account_id' => $this->settingAccount('DEFAULT_ACC_INVENTORY')->id, 'debit' => $value, 'credit' => 0],
                        ['account_id' => $payableAccount->id, 'debit' => 0, 'credit' => $value],
                    ],
                    'PURCHASE:'.$purchase->id
                );
            } else {
                Journal::where('source_key', 'PURCHASE:'.$purchase->id)->delete();
            }
        }

        $history = $purchase->pembelianTransaction?->payment_history ?? [];
        if ($purchase->pembelianTransaction && empty($history) && (float) $purchase->pembelianTransaction->amount > 0) {
            $history[] = [
                'payment_date' => $purchase->pembelianTransaction->payment_date ?: $purchase->receipt_date ?: $purchase->created_at,
                'amount' => $purchase->pembelianTransaction->amount,
                'payment_method' => $purchase->pembelianTransaction->payment_method,
                'account_id' => $purchase->pembelianTransaction->account_id,
            ];
        }

        $this->syncPaymentHistory(
            $purchase->pembelianTransaction,
            $history,
            $payableAccount,
            $cashAccount,
            $purchase->id,
            'PURCHASE_PAYMENT',
            fn (array $history) => $history['payment_date'] ?? $purchase->receipt_date ?? $purchase->created_at,
            'Pembayaran pembelian '.$purchase->code,
            true
        );
    }

    public function syncExpense(Pengeluaran $expense): void
    {
        $amount = round((float) ($expense->jumlah ?? 0), 2);
        if ($amount <= 0) {
            $this->deleteSourceFamily('EXPENSE:'.$expense->id);
            return;
        }

        $expenseAccount = $this->expenseAccount($expense->category?->name);
        $cashAccount = $this->paymentAccount(null, $expense->kas_id);
        $this->createJournal(
            $expense->tanggal ?: $expense->created_at,
            'EXPENSE',
            $expense->id,
            $expense->desc ?: 'Pengeluaran operasional',
            [
                ['account_id' => $expenseAccount->id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $cashAccount->id, 'debit' => 0, 'credit' => $amount],
            ],
            'EXPENSE:'.$expense->id
        );
    }

    public function deleteExpenseJournal(int $expenseId): void
    {
        $this->deleteSourceFamily('EXPENSE:'.$expenseId);
    }

    public function syncAll(): array
    {
        $counts = ['sales' => 0, 'purchases' => 0, 'expenses' => 0];
        Penjualan::with(['items.product', 'items.stock', 'items.allocations.stock', 'paymentTransaction'])->chunkById(100, function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                $this->syncSale($row);
                $counts['sales']++;
            }
        });
        Pembelian::with(['pembelianProducts', 'stocks', 'pembelianTransaction'])->chunkById(100, function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                $this->syncPurchase($row);
                $counts['purchases']++;
            }
        });
        Pengeluaran::with('category')->chunkById(100, function ($rows) use (&$counts) {
            foreach ($rows as $row) {
                $this->syncExpense($row);
                $counts['expenses']++;
            }
        });

        return $counts;
    }

    public function settingAccount(string $key): Account
    {
        $account = SystemSetting::where('key', $key)->with('account')->first()?->account;
        if ($account?->is_active && ! $account->is_header) {
            return $account;
        }

        throw new InvalidArgumentException('Akun default '.$key.' belum dikonfigurasi.');
    }

    public function paymentAccount(?int $accountId, ?int $kasId = null): Account
    {
        if ($accountId) {
            $account = Account::active()->posting()->whereKey($accountId)->first();
            if ($account) {
                return $account;
            }
        }

        return $this->settingAccount('DEFAULT_ACC_CASH');
    }

    private function validateDetails(array $details): Collection
    {
        $normalized = collect($details)
            ->map(fn (array $detail) => [
                'account_id' => (int) ($detail['account_id'] ?? 0),
                'debit' => round(max(0, (float) ($detail['debit'] ?? 0)), 2),
                'credit' => round(max(0, (float) ($detail['credit'] ?? 0)), 2),
            ])
            ->filter(fn (array $detail) => $detail['debit'] > 0 || $detail['credit'] > 0)
            ->values();

        if (abs((float) $normalized->sum('debit') - (float) $normalized->sum('credit')) > 0.01) {
            throw new InvalidArgumentException('Jurnal tidak balance.');
        }
        if ($normalized->isEmpty()) {
            throw new InvalidArgumentException('Jurnal harus memiliki minimal satu baris nominal.');
        }

        $accountIds = $normalized->pluck('account_id')->unique()->filter();
        $valid = Account::active()->posting()->whereIn('id', $accountIds)->count();
        if ($valid !== $accountIds->count()) {
            throw new InvalidArgumentException('Akun jurnal manual tidak valid.');
        }

        return $normalized;
    }

    private function nextJournalNumber($date, ?int $exceptId = null): string
    {
        $date = is_string($date) ? $date : $date->toDateString();
        $prefix = 'JRN/'.date('Y/m', strtotime($date)).'/';
        $query = Journal::where('journal_number', 'like', $prefix.'%');
        if ($exceptId) {
            $query->whereKeyNot($exceptId);
        }
        $last = $query->orderByDesc('id')->value('journal_number');
        $sequence = $last && preg_match('/(\d+)$/', $last, $matches) ? ((int) $matches[1]) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function deleteSourceFamily(string $sourceKey, ?string $paymentPattern = null): void
    {
        Journal::where('source_key', $sourceKey)->delete();
        if ($paymentPattern) {
            $this->deletePaymentJournals($paymentPattern);
        }
    }

    private function deletePaymentJournals(string $paymentPattern): void
    {
        Journal::where('source_key', 'like', $paymentPattern)->delete();
    }

    private function syncPaymentHistory(
        $transaction,
        array $history,
        Account $creditAccount,
        Account $defaultCashAccount,
        int $referenceId,
        string $refType,
        callable $dateResolver,
        string $description,
        bool $creditPaymentAccount = false,
        ?int $outletId = null
    ): void {
        foreach (array_values($history) as $index => $payment) {
            $amount = round((float) ($payment['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $account = $this->paymentAccount(isset($payment['account_id']) ? (int) $payment['account_id'] : $transaction?->account_id)
                ?: $defaultCashAccount;
            $details = $creditPaymentAccount
                ? [
                    ['account_id' => $creditAccount->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $account->id, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['account_id' => $account->id, 'debit' => $amount, 'credit' => 0],
                    ['account_id' => $creditAccount->id, 'debit' => 0, 'credit' => $amount],
                ];

            $this->createJournal(
                $dateResolver($payment),
                $refType,
                $referenceId,
                $description.' #'.($index + 1),
                $details,
                $refType.':'.$referenceId.':'.$index,
                false,
                $outletId
            );
        }
    }

    private function saleCost(Penjualan $sale): float
    {
        return round((float) $sale->items->sum(function ($item) {
            if ($item->allocations->isNotEmpty()) {
                return $item->allocations->sum(fn ($allocation) => (float) ($allocation->qty ?? 0) * (float) ($allocation->stock?->harga_beli ?? 0));
            }

            return (float) ($item->qty ?? 0) * (float) ($item->stock?->harga_beli ?? $item->product?->harga_beli ?? 0);
        }), 2);
    }

    private function expenseAccount(?string $categoryName): Account
    {
        if ($categoryName) {
            $account = Account::active()->posting()->get()->first(fn (Account $account) => strcasecmp(trim($account->name), trim($categoryName)) === 0);
            if ($account) {
                return $account;
            }
        }

        return $this->settingAccount('DEFAULT_ACC_EXPENSE');
    }
}
