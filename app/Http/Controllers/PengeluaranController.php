<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengeluaranRequest;
use App\Models\Category;
use App\Models\Kas;
use App\Models\Pengeluaran;
use App\Services\AccountingService;

class PengeluaranController extends Controller
{
    public function index()
    {
        return view('pengeluarans.index', [
            'pengeluarans' => Pengeluaran::get(),
        ]);
    }

    public function create()
    {
        return view('pengeluarans.create', [
            'kas' => Kas::get(),
            'categories' => Category::where('type', 'pengeluaran')->get(),
        ]);
    }

    public function store(PengeluaranRequest $request)
    {
        $data = $request->validated();
        $pengeluaran = Pengeluaran::create($data);
        $kas = Kas::find($data['kas_id']);
        $kas->nominal -= $data['jumlah'];
        $kas->save();
        app(AccountingService::class)->syncExpense($pengeluaran->fresh('category'));

        return redirect(route('pengeluaran.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function show(Pengeluaran $pengeluaran)
    {
        dd($pengeluaran);
    }

    public function edit(Pengeluaran $pengeluaran)
    {
        return view('pengeluarans.edit', [
            'pengeluaran' => $pengeluaran,
            'kas' => Kas::get(),
            'categories' => Category::where('type', 'pengeluaran')->get(),
        ]);
    }

    public function update(PengeluaranRequest $request, Pengeluaran $pengeluaran)
    {
        $data = $request->validated();

        $pengeluaran->update($data);
        app(AccountingService::class)->syncExpense($pengeluaran->fresh('category'));

        return redirect(route('pengeluaran.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        $pengeluaran->delete();
        app(AccountingService::class)->deleteExpenseJournal($pengeluaran->id);

        return redirect(route('pengeluaran.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }
}
