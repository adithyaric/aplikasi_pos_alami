<?php

namespace App\Http\Controllers;

use App\Exports\BalanceSheetExport;
use App\Exports\GeneralJournalExport;
use App\Exports\GeneralLedgerExport;
use App\Exports\ProfitLossExport;
use App\Models\Account;
use App\Models\Outlet;
use App\Services\AccountingService;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class FinancialReportController extends Controller
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly AccountingService $accounting
    ) {
    }

    public function index()
    {
        return view('accounting.index', [
            'accountCount' => Account::count(),
            'journalCount' => \App\Models\Journal::count(),
        ]);
    }

    public function generalJournal(Request $request)
    {
        $branches = Outlet::branches()->orderBy('name')->get();
        $location = $this->journalLocation($request, $branches->pluck('id')->all());
        $journals = $this->reports->generalJournalPage($request->date_from, $request->date_to, 25, $location);

        return view('accounting.reports.general-journal', compact('journals', 'branches', 'location'));
    }

    public function ledger(Request $request)
    {
        $accounts = Account::active()->posting()->orderBy('code')->get();
        $accountId = $request->filled('account_id') ? $request->integer('account_id') : null;
        $ledger = $accountId ? $this->reports->ledger($accountId, $request->date_from, $request->date_to) : null;
        $ledgerPage = $accountId ? $this->reports->ledgerPage($accountId, $request->date_from, $request->date_to) : null;

        return view('accounting.reports.ledger', compact('accounts', 'ledger', 'ledgerPage'));
    }

    public function profitLoss(Request $request)
    {
        $start = $request->input('date_from', now()->startOfMonth()->toDateString());
        $end = $request->input('date_to', now()->toDateString());
        $report = $this->reports->profitLoss($start, $end);

        return view('accounting.reports.profit-loss', compact('report'));
    }

    public function balanceSheet(Request $request)
    {
        $asOf = $request->input('as_of_date', now()->toDateString());
        $report = $this->reports->balanceSheet($asOf);

        return view('accounting.reports.balance-sheet', compact('report'));
    }

    public function sync()
    {
        $counts = $this->accounting->syncAll();

        return back()->with('toast_success', 'Jurnal transaksi berhasil disinkronkan: '.implode(', ', array_map(fn ($key, $value) => $key.'='.$value, array_keys($counts), $counts)).'.');
    }

    public function exportGeneralJournal(Request $request)
    {
        $location = $this->journalLocation($request, Outlet::branches()->pluck('id')->all());

        return Excel::download(new GeneralJournalExport($request->date_from, $request->date_to, $location), 'jurnal-umum.xlsx');
    }

    private function journalLocation(Request $request, array $branchIds): string
    {
        $request->validate(['location' => ['nullable', Rule::in(array_merge(['pusat', 'all'], array_map('strval', $branchIds)))]]);

        return $request->input('location') ?: 'pusat';
    }

    public function exportLedger(Request $request)
    {
        if (! $request->filled('account_id') || ! $request->integer('account_id')) {
            return redirect()->route('accounting.ledger', $request->query())
                ->with('toast_error', 'Pilih akun buku besar terlebih dahulu sebelum mencetak.');
        }

        return Excel::download(new GeneralLedgerExport($request->integer('account_id'), $request->date_from, $request->date_to), 'buku-besar.xlsx');
    }

    public function exportProfitLoss(Request $request)
    {
        $start = $request->input('date_from', now()->startOfMonth()->toDateString());
        $end = $request->input('date_to', now()->toDateString());

        return Excel::download(new ProfitLossExport($start, $end), 'laporan-laba-rugi.xlsx');
    }

    public function exportBalanceSheet(Request $request)
    {
        return Excel::download(new BalanceSheetExport($request->input('as_of_date', now()->toDateString())), 'laporan-neraca.xlsx');
    }
}
