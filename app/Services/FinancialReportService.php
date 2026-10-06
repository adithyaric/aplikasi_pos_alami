<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class FinancialReportService
{
    private const RETAINED_EARNINGS_ACCOUNT_CODE = '300002';

    public function generalJournal(?string $startDate = null, ?string $endDate = null, string $location = 'pusat'): Collection
    {
        return Journal::excludeReturns()->forLocation($location)->with(['details.account', 'outlet'])
            ->when($startDate, fn ($query) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('transaction_date', '<=', $endDate))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function generalJournalPage(?string $startDate = null, ?string $endDate = null, int $perPage = 25, string $location = 'pusat'): LengthAwarePaginator
    {
        return Journal::excludeReturns()->forLocation($location)->with(['details.account', 'outlet'])
            ->when($startDate, fn ($query) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('transaction_date', '<=', $endDate))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function ledger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = Account::findOrFail($accountId);
        $details = JournalDetail::with('journal')
            ->where('account_id', $accountId)
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->excludeReturns()
                    ->when($startDate, fn ($builder) => $builder->whereDate('transaction_date', '>=', $startDate))
                    ->when($endDate, fn ($builder) => $builder->whereDate('transaction_date', '<=', $endDate));
            })
            ->get()
            ->sortBy(fn (JournalDetail $detail) => $detail->journal->transaction_date->format('Y-m-d').'|'.str_pad((string) $detail->journal_id, 12, '0', STR_PAD_LEFT))
            ->values();

        $opening = 0;
        if ($startDate) {
            $openingDetails = JournalDetail::where('account_id', $accountId)
                ->whereHas('journal', fn ($query) => $query->excludeReturns()->whereDate('transaction_date', '<', $startDate))
                ->selectRaw('COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
                ->first();
            $opening = $this->signedBalance($account, (float) $openingDetails->debit_total, (float) $openingDetails->credit_total);
        }

        $running = $opening;
        $rows = $details->map(function (JournalDetail $detail) use (&$running, $account) {
            $debit = (float) $detail->debit;
            $credit = (float) $detail->credit;
            $running += $account->isDebitNormal() ? $debit - $credit : $credit - $debit;

            return [
                'date' => $detail->journal->transaction_date,
                'journal_number' => $detail->journal->journal_number,
                'description' => $detail->journal->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => round($running, 2),
            ];
        });

        return compact('account', 'opening', 'rows');
    }

    public function ledgerPage(int $accountId, ?string $startDate = null, ?string $endDate = null, int $perPage = 25): LengthAwarePaginator
    {
        $ledger = $this->ledger($accountId, $startDate, $endDate);
        $page = LengthAwarePaginator::resolveCurrentPage('page');
        $rows = $ledger['rows'];

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    public function profitLoss(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = Account::posting()
            ->whereIn('type_code', AccountingService::PROFIT_LOSS_TYPES)
            ->orderBy('code')
            ->get();
        $aggregates = $this->aggregates($accounts, $startDate, $endDate);
        $unpaidSalesRevenue = $this->applyPaidSalesRevenue($aggregates, $accounts, $startDate, $endDate);
        $this->includeUnsyncedExpenses($aggregates, $accounts, $startDate, $endDate);

        $rows = $accounts->map(function (Account $account) use ($aggregates) {
            $aggregate = $aggregates->get($account->id, ['debit' => 0, 'credit' => 0]);
            $income = in_array($account->type_code, ['REVE', 'OINC'], true);
            $amount = $income
                ? $aggregate['credit'] - $aggregate['debit']
                : $aggregate['debit'] - $aggregate['credit'];

            return [
                'account' => $account,
                'debit' => round($aggregate['debit'], 2),
                'credit' => round($aggregate['credit'], 2),
                'amount' => round($amount, 2),
                'section' => $this->profitLossSection($account->type_code),
            ];
        })->filter(fn (array $row) => abs($row['amount']) > 0.0001)->values();

        $income = round((float) $rows->whereIn('account.type_code', ['REVE', 'OINC'])->sum('amount'), 2);
        $expense = round((float) $rows->whereIn('account.type_code', ['COGS', 'EXPS', 'OEXP'])->sum('amount'), 2);

        return [
            'rows' => $rows,
            'income' => $income,
            'expense' => $expense,
            'net_income' => round($income - $expense, 2),
            'unpaid_sales_revenue' => $unpaidSalesRevenue,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    public function balanceSheet(string $asOfDate): array
    {
        $accounts = Account::posting()
            ->whereIn('type_code', AccountingService::BALANCE_SHEET_TYPES)
            ->orderBy('code')
            ->get();
        $aggregates = $this->aggregates($accounts, null, $asOfDate);
        $profitLoss = $this->profitLoss(null, $asOfDate);
        $unpaidSalesRevenue = $profitLoss['unpaid_sales_revenue'];
        $rows = $accounts->map(function (Account $account) use ($aggregates) {
            $aggregate = $aggregates->get($account->id, ['debit' => 0, 'credit' => 0]);

            return [
                'account' => $account,
                'debit' => round($aggregate['debit'], 2),
                'credit' => round($aggregate['credit'], 2),
                'balance' => round($this->signedBalance($account, $aggregate['debit'], $aggregate['credit']), 2),
            ];
        })->map(function (array $row) use ($unpaidSalesRevenue) {
            // Sales are currently recognized in profit/loss based on collected
            // payments. Keep the unpaid portion in retained earnings so the
            // balance sheet reflects the AR side without creating a journal.
            if ($row['account']->code === self::RETAINED_EARNINGS_ACCOUNT_CODE) {
                $row['credit'] = round($row['credit'] + $unpaidSalesRevenue, 2);
                $row['balance'] = round($row['balance'] + $unpaidSalesRevenue, 2);
            }

            return $row;
        })->filter(fn (array $row) => abs($row['balance']) > 0.0001)->values();

        $assets = $rows->filter(fn (array $row) => in_array($row['account']->type_code, ['BANK', 'AREC', 'INTR', 'OCAS', 'FASS', 'OASS', 'DEPR'], true));
        $liabilities = $rows->filter(fn (array $row) => in_array($row['account']->type_code, ['APAY', 'OCLY', 'LTLY'], true));
        $equity = $rows->where('account.type_code', 'EQTY');

        return [
            'rows' => $rows,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'net_income' => $profitLoss['net_income'],
            'total_assets' => round((float) $assets->sum('balance'), 2),
            'total_liabilities' => round((float) $liabilities->sum('balance'), 2),
            'total_equity' => round((float) $equity->sum('balance') + $profitLoss['net_income'], 2),
            'as_of_date' => $asOfDate,
        ];
    }

    public function accountBalances(?string $asOfDate = null): Collection
    {
        $accounts = Account::posting()->orderBy('code')->get();
        $aggregates = $this->aggregates($accounts, null, $asOfDate);

        return $accounts->map(function (Account $account) use ($aggregates) {
            $aggregate = $aggregates->get($account->id, ['debit' => 0, 'credit' => 0]);
            $account->setAttribute('debit_total', $aggregate['debit']);
            $account->setAttribute('credit_total', $aggregate['credit']);
            $account->setAttribute('balance', $this->signedBalance($account, $aggregate['debit'], $aggregate['credit']));

            return $account;
        });
    }

    private function aggregates(Collection $accounts, ?string $startDate, ?string $endDate): Collection
    {
        if ($accounts->isEmpty()) {
            return collect();
        }

        return JournalDetail::whereIn('account_id', $accounts->pluck('id'))
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->excludeReturns()
                    ->when($startDate, fn ($builder) => $builder->whereDate('transaction_date', '>=', $startDate))
                    ->when($endDate, fn ($builder) => $builder->whereDate('transaction_date', '<=', $endDate));
            })
            ->selectRaw('account_id, COALESCE(SUM(debit), 0) as debit_total, COALESCE(SUM(credit), 0) as credit_total')
            ->groupBy('account_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->account_id => [
                'debit' => (float) $row->debit_total,
                'credit' => (float) $row->credit_total,
            ]]);
    }

    private function applyPaidSalesRevenue(Collection $aggregates, Collection $accounts, ?string $startDate, ?string $endDate): float
    {
        $revenueAccountIds = $accounts
            ->whereIn('type_code', ['REVE', 'OINC'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($revenueAccountIds->isEmpty()) {
            return 0;
        }

        $unpaidSalesRevenue = 0;

        $salesJournals = Journal::excludeReturns()
            ->where('ref_type', 'SALES')
            ->when($startDate, fn ($query) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('transaction_date', '<=', $endDate))
            ->with('details')
            ->get();
        $sales = Penjualan::with('paymentTransaction')
            ->whereIn('id', $salesJournals->pluck('ref_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        foreach ($salesJournals as $journal) {
            $sale = $sales->get($journal->ref_id);
            $total = round((float) ($sale?->total ?? 0), 2);
            if ($total <= 0) {
                continue;
            }

            $paid = $sale?->payment_type === 'cash'
                ? $total
                : min($total, max(0, (float) ($sale?->paymentTransaction?->amount ?? 0)));
            $ratio = $paid / $total;

            foreach ($journal->details as $detail) {
                if (! $revenueAccountIds->contains((int) $detail->account_id)) {
                    continue;
                }

                $unpaidSalesRevenue += (((float) $detail->credit - (float) $detail->debit) * (1 - $ratio));

                $aggregate = $aggregates->get($detail->account_id, ['debit' => 0, 'credit' => 0]);
                $aggregate['debit'] += ((float) $detail->debit * $ratio) - (float) $detail->debit;
                $aggregate['credit'] += ((float) $detail->credit * $ratio) - (float) $detail->credit;
                $aggregates->put($detail->account_id, $aggregate);
            }
        }

        return round(max(0, $unpaidSalesRevenue), 2);
    }

    private function includeUnsyncedExpenses(Collection $aggregates, Collection $accounts, ?string $startDate, ?string $endDate): void
    {
        $expenses = Pengeluaran::with('category')
            ->when($startDate, fn ($query) => $query->whereDate('tanggal', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('tanggal', '<=', $endDate))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('journals')
                ->where('journals.ref_type', 'EXPENSE')
                ->whereColumn('journals.ref_id', 'pengeluarans.id'))
            ->get();

        foreach ($expenses as $expense) {
            $account = $accounts->first(function (Account $candidate) use ($expense) {
                return $expense->category?->name
                    && strcasecmp(trim($candidate->name), trim($expense->category->name)) === 0
                    && in_array($candidate->type_code, ['EXPS', 'OEXP', 'COGS'], true);
            });

            if (! $account) {
                try {
                    $fallback = app(AccountingService::class)->settingAccount('DEFAULT_ACC_EXPENSE');
                    $account = $accounts->firstWhere('id', $fallback->id);
                } catch (\Throwable) {
                    $account = null;
                }
            }

            if (! $account) {
                continue;
            }

            $aggregate = $aggregates->get($account->id, ['debit' => 0, 'credit' => 0]);
            $aggregate['debit'] += (float) $expense->jumlah;
            $aggregates->put($account->id, $aggregate);
        }
    }

    private function signedBalance(Account $account, float $debit, float $credit): float
    {
        return $account->isDebitNormal() ? $debit - $credit : $credit - $debit;
    }

    private function profitLossSection(string $type): string
    {
        return match ($type) {
            'REVE' => 'Pendapatan Operasional',
            'OINC' => 'Pendapatan Non Operasional',
            'COGS' => 'Beban Pokok Penjualan',
            'EXPS' => 'Beban Operasional',
            'OEXP' => 'Beban Non Operasional',
            default => 'Lainnya',
        };
    }
}
