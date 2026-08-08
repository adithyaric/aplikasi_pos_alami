<?php

namespace App\Http\Controllers;

use App\Exports\SuppliersExport;
use App\Http\Requests\SupplierRequest;
use App\Imports\SuppliersImport;
use App\Models\Supplier;
use App\Services\DocumentTemplateManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    public function __construct(
        private readonly DocumentTemplateManager $templateManager,
    ) {
    }

    public function index()
    {
        return view('suppliers.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('suppliers.create', [
            'nextKode' => Supplier::generateNextKode(),
            'poBuilderConfig' => (new Supplier)->poNumberBuilderConfig(),
        ]);
    }

    public function store(SupplierRequest $request)
    {
        $data = $request->validated();
        unset($data['po_template']);

        $supplier = Supplier::create($data);
        $this->syncPoTemplates($supplier, $request);

        return redirect(route('supplier.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function show(Supplier $supplier)
    {
        dd($supplier);
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', [
            'supplier' => $supplier,
            'poBuilderConfig' => $supplier->poNumberBuilderConfig(),
        ]);
    }

    public function nextPoCode(Supplier $supplier)
    {
        return response()->json([
            'code' => $supplier->generateNextPoCode(),
            'prefix' => $supplier->poNumberFormat(),
            'padding' => (int) ($supplier->po_number_padding ?: 5),
        ]);
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();
        unset($data['po_template']);

        $supplier->update($data);
        $this->syncPoTemplates($supplier, $request);

        return redirect(route('supplier.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect(route('supplier.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function downloadPoTemplate(Supplier $supplier)
    {
        $path = $supplier->po_template;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return response()->download(Storage::disk('public')->path($path), basename($path));
    }

    ///-----------------------------------------------------------------------------------------------

    public function export()
    {
        return Excel::download(new SuppliersExport(), 'suppliers.xlsx');
    }

    public function exportTemplate()
    {
        return Excel::download(new SuppliersExport(templateOnly: true), 'template_suppliers.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        Excel::import(new SuppliersImport(), $request->file('file'));

        return redirect()->back()->with('toast_success', 'Berhasil Import Data!');
    }

    private function syncPoTemplates(Supplier $supplier, Request $request): void
    {
        if ($request->boolean('reset_po_template')) {
            $this->templateManager->resetSupplierPurchaseTemplate($supplier);
            $supplier->update(['po_template' => null]);
        }

        if ($request->hasFile('po_template')) {
            $path = $this->templateManager->storeSupplierPurchaseTemplate(
                $supplier,
                $request->file('po_template'),
            );
            $supplier->update(['po_template' => $path]);
        }
    }
}
