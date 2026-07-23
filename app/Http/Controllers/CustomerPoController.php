<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPoRequest;
use App\Models\CustomerPo;
use App\Models\Pembelian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerPoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', $request->input('term', '')));

        if ($request->filled('q') || $request->filled('term') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return $this->options($request);
        }

        return view('customer-pos.index', [
            'customerPos' => CustomerPo::orderBy('name')->get(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', $request->input('term', '')));

        $customerPos = $this->customerPoOptionNames($search)
            ->map(fn ($name) => [
                'id' => $name,
                'text' => $name,
            ])
            ->values();

        return new JsonResponse([
            'results' => $customerPos,
        ]);
    }

    public function pembelianOptions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', $request->input('term', '')));

        return new JsonResponse(
            $this->customerPoOptionNames($search)
                ->map(fn ($name) => [
                    'name' => $name,
                ])
                ->values()
        );
    }

    public function create()
    {
        return view('customer-pos.create');
    }

    public function store(CustomerPoRequest $request)
    {
        $customerPo = $this->persistCustomerPoName($request->input('name'));

        if ($request->boolean('_ajax') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return new JsonResponse([
                'data' => [
                    'id' => $customerPo->id,
                    'name' => $customerPo->name,
                ],
            ], 201);
        }

        return redirect()->route('customer-po.index')->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function edit(CustomerPo $customerPo)
    {
        return view('customer-pos.edit', [
            'customerPo' => $customerPo,
        ]);
    }

    public function update(CustomerPoRequest $request, CustomerPo $customerPo)
    {
        $customerPo->update($request->validated());

        return redirect()->route('customer-po.index')->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(CustomerPo $customerPo)
    {
        $customerPo->delete();

        return redirect()->route('customer-po.index')->with('toast_success', 'Berhasil Menghapus Data!');
    }

    private function persistCustomerPoName(?string $name): CustomerPo
    {
        $normalized = trim((string) $name);

        $existing = CustomerPo::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalized)])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            if ($existing->name !== $normalized) {
                $existing->update(['name' => $normalized]);
            }

            return $existing->fresh();
        }

        return CustomerPo::create([
            'name' => $normalized,
        ]);
    }

    private function customerPoOptionNames(string $search)
    {
        return CustomerPo::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->pluck('name')
            ->merge(
                Pembelian::query()
                    ->whereNotNull('customer_po')
                    ->when($search !== '', fn ($query) => $query->where('customer_po', 'like', '%'.$search.'%'))
                    ->pluck('customer_po')
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->sortBy(fn ($name) => mb_strtolower($name))
            ->take(50)
            ->values();
    }
}
