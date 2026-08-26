<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountingAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = Account::with('parent')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(fn ($builder) => $builder->where('code', 'like', $term)->orWhere('name', 'like', $term));
            })
            ->when($request->filled('type_code'), fn ($query) => $query->where('type_code', $request->input('type_code')))
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        return view('accounting.accounts.index', [
            'accounts' => $accounts,
            'parents' => Account::where('is_header', true)->orderBy('code')->get(),
            'types' => Account::query()->select('type_code')->distinct()->orderBy('type_code')->pluck('type_code'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Account::create($data);

        return redirect()->route('accounting.accounts.index')->with('toast_success', 'Akun berhasil ditambahkan.');
    }

    public function update(Request $request, Account $account)
    {
        $data = $this->validated($request, $account);
        if ((int) ($data['parent_id'] ?? 0) === (int) $account->id) {
            return back()->withInput()->with('toast_error', 'Akun tidak boleh menjadi induk dirinya sendiri.');
        }

        $account->update($data);

        return redirect()->route('accounting.accounts.index')->with('toast_success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Account $account)
    {
        if ($account->children()->exists() || $account->journalDetails()->exists()) {
            $account->update(['is_active' => false]);

            return redirect()->route('accounting.accounts.index')->with('toast_success', 'Akun dinonaktifkan karena sudah dipakai transaksi.');
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')->with('toast_success', 'Akun berhasil dihapus.');
    }

    private function validated(Request $request, ?Account $account = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('accounts', 'code')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:100'],
            'type_code' => ['required', 'string', 'max:10'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_header' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_header' => $request->boolean('is_header'),
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
