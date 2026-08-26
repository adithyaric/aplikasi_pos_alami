<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Journal;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Throwable;

class JournalController extends Controller
{
    public function __construct(private readonly AccountingService $accounting)
    {
    }

    public function index(Request $request)
    {
        $journals = Journal::excludeReturns()->with('details.account')
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date_to))
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->search.'%';
                $query->where(fn ($builder) => $builder->where('journal_number', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.journals.index', compact('journals'));
    }

    public function create()
    {
        return view('accounting.journals.form', [
            'journal' => null,
            'accounts' => Account::active()->posting()->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $this->validated($request);
            $this->accounting->createJournal(
                $data['transaction_date'],
                'MANUAL',
                null,
                $data['description'] ?? null,
                $data['details'],
                null,
                true
            );

            return redirect()->route('accounting.journals.index')->with('toast_success', 'Jurnal umum berhasil disimpan.');
        } catch (Throwable $exception) {
            return back()->withInput()->with('toast_error', $exception->getMessage());
        }
    }

    public function show(Journal $journal)
    {
        return view('accounting.journals.show', ['journal' => $journal->load('details.account')]);
    }

    public function edit(Journal $journal)
    {
        abort_unless($journal->is_manual, 403, 'Jurnal otomatis hanya dapat dikoreksi dari transaksi sumbernya.');

        return view('accounting.journals.form', [
            'journal' => $journal->load('details.account'),
            'accounts' => Account::active()->posting()->orderBy('code')->get(),
        ]);
    }

    public function update(Request $request, Journal $journal)
    {
        try {
            $data = $this->validated($request);
            $this->accounting->updateManualJournal($journal, $data);

            return redirect()->route('accounting.journals.show', $journal)->with('toast_success', 'Jurnal umum berhasil diperbarui.');
        } catch (Throwable $exception) {
            return back()->withInput()->with('toast_error', $exception->getMessage());
        }
    }

    public function destroy(Journal $journal)
    {
        try {
            $this->accounting->deleteJournal($journal);

            return redirect()->route('accounting.journals.index')->with('toast_success', 'Jurnal umum berhasil dihapus.');
        } catch (Throwable $exception) {
            return back()->with('toast_error', $exception->getMessage());
        }
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'details' => ['required', 'array', 'min:2'],
            'details.*.account_id' => ['nullable', 'integer'],
            'details.*.debit' => ['nullable', 'numeric', 'min:0'],
            'details.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['details'] = collect($validated['details'])
            ->map(fn (array $detail) => [
                'account_id' => (int) ($detail['account_id'] ?? 0),
                'debit' => (float) ($detail['debit'] ?? 0),
                'credit' => (float) ($detail['credit'] ?? 0),
            ])
            ->values()
            ->all();

        return $validated;
    }
}
