<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function generalJournal(?string $startDate = null, ?string $endDate = null): Collection
    {
        return Journal::with('details.account')
            ->when($startDate, fn ($query) => $query->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('transaction_date', '<=', $endDate))
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function ledger(int $accountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $account = Account::findOrFail($accountId);
        $details = JournalDetail::with('journal')
            ->where('account_id', $accountId)
            ->whereHas('journal', function ($query) use ($startDate, $endDate) {
                $query->when($startDate, fn ($builder) => $builder->whereDate('transaction_date', '>=', $startDate))
                    ->when($endDate, fn ($builder) => $builder->whereDate('transaction_date', '<=', $endDate));
            })
            ->get()
            ->sortBy(fn (JournalDetail $detail) => $detail->journal->transaction_date->format('Y-m-d').'|'.str_pad((string) $detail->journal_id, 12, '0', STR_PAD_LEFT))
            ->values();

        $opening = 0;
        if ($startDate) {
            $openingDetails = JournalDetail::where('account_id', $accountId)
                ->whereHas('journal', fn ($query) => $query->whereDate('transaction_date', '<', $startDate))
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

    public function profitLoss(?string $startDate = null, ?string $endDate = null): array
    {
        $accounts = Account::posting()
            ->whereIn('type_code', AccountingService::PROFIT_LOSS_TYPES)
            ->orderBy('code')
            ->get();
        $aggregates = $this->aggregates($accounts, $startDate, $endDate);

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
        $rows = $accounts->map(function (Account $account) use ($aggregates) {
            $aggregate = $aggregates->get($account->id, ['debit' => 0, 'credit' => 0]);

            return [
                'account' => $account,
                'debit' => round($aggregate['debit'], 2),
                'credit' => round($aggregate['credit'], 2),
                'balance' => round($this->signedBalance($account, $aggregate['debit'], $aggregate['credit']), 2),
            ];
        })->filter(fn (array $row) => abs($row['balance']) > 0.0001)->values();

        $profitLoss = $this->profitLoss(null, $asOfDate);
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
                $query->when($startDate, fn ($builder) => $builder->whereDate('transaction_date', '>=', $startDate))
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
