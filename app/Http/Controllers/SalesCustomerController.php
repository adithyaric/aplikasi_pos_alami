<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Canvas;
use App\Models\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SalesCustomerController extends Controller
{
    private const TYPES = [
        'toko' => 'Toko',
        'agent' => 'Agen',
        'canvas' => 'Canvas',
        'cabang' => 'Cabang',
    ];

    public function index(Request $request)
    {
        $types = $this->availableTypes();
        $customers = $this->catalog()
            ->filter(fn (array $item) => array_key_exists($item['type'], $types))
            ->when($request->filled('type'), fn (Collection $items) => $items->where('type', $request->input('type')))
            ->when($request->filled('q'), fn (Collection $items) => $items->filter(
                fn (array $item) => str_contains(mb_strtolower($item['name']), mb_strtolower((string) $request->input('q')))
            ))
            ->values();

        return view('customer-penjualans.index', [
            'customers' => $customers,
            'types' => $types,
            'selectedType' => $request->input('type'),
            'search' => $request->input('q'),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = mb_strtolower(trim((string) $request->input('q', $request->input('term', ''))));
        $types = $this->availableTypes();

        return response()->json([
            'results' => $this->catalog()
                ->filter(fn (array $item) => array_key_exists($item['type'], $types))
                ->filter(fn (array $item) => $search === '' || str_contains(mb_strtolower($item['name']), $search))
                ->values(),
        ]);
    }

    public function create()
    {
        return view('customer-penjualans.create', ['types' => $this->availableTypes()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $customer = $this->persist($data);

        if ($request->boolean('_ajax') || $request->expectsJson() || $request->ajax()) {
            return response()->json(['data' => $this->serialize($customer, $data['type'])], 201);
        }

        return redirect()->route('customer-penjualan.index')->with('toast_success', 'Customer penjualan berhasil disimpan.');
    }

    public function edit(string $type, int $id)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        abort_unless(array_key_exists($type, $this->availableTypes()), 404);

        return view('customer-penjualans.edit', [
            'customer' => $this->find($type, $id),
            'type' => $type,
            'types' => $this->availableTypes(),
        ]);
    }

    public function update(Request $request, string $type, int $id)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        abort_unless(array_key_exists($type, $this->availableTypes()), 404);

        $data = $this->validated($request);
        abort_unless($data['type'] === $type, 422);
        $this->find($type, $id)->update($this->modelAttributes($data));

        return redirect()->route('customer-penjualan.index')->with('toast_success', 'Customer penjualan berhasil diperbarui.');
    }

    public function destroy(string $type, int $id)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        abort_unless(array_key_exists($type, $this->availableTypes()), 404);
        $this->find($type, $id)->delete();

        return redirect()->route('customer-penjualan.index')->with('toast_success', 'Customer penjualan berhasil dihapus.');
    }

    private function catalog(): Collection
    {
        return collect([
            ...Outlet::shops()->orderBy('name')->get()->map(fn (Outlet $item) => $this->serialize($item, 'toko')),
            ...Agent::orderBy('name')->get()->map(fn (Agent $item) => $this->serialize($item, 'agent')),
            ...Canvas::orderBy('name')->get()->map(fn (Canvas $item) => $this->serialize($item, 'canvas')),
            ...Outlet::branches()->orderBy('name')->get()->map(fn (Outlet $item) => $this->serialize($item, 'cabang')),
        ])->sortBy(fn (array $item) => mb_strtolower($item['name']))->values();
    }

    private function availableTypes(): array
    {
        if (in_array(auth()->user()?->role, ['admin-cabang', 'sales'], true)) {
            return ['toko' => self::TYPES['toko']];
        }

        return self::TYPES;
    }

    private function find(string $type, int $id)
    {
        return match ($type) {
            'agent' => Agent::findOrFail($id),
            'canvas' => Canvas::findOrFail($id),
            'toko' => Outlet::shops()->findOrFail($id),
            'cabang' => Outlet::branches()->findOrFail($id),
        };
    }

    private function persist(array $data)
    {
        return match ($data['type']) {
            'agent' => Agent::create($this->modelAttributes($data)),
            'canvas' => Canvas::create($this->modelAttributes($data)),
            'toko' => Outlet::create($this->modelAttributes($data) + ['jenis_outlet' => 'toko']),
            'cabang' => Outlet::create($this->modelAttributes($data) + ['jenis_outlet' => 'branch']),
        };
    }

    private function modelAttributes(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'code' => $data['code'] ?? null,
            'desc' => $data['desc'] ?? null,
            'alamat' => $data['alamat'] ?? null,
            'no_telp' => $data['no_telp'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:'.implode(',', array_keys($this->availableTypes())),
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'alamat' => 'nullable|string|max:1000',
            'no_telp' => 'nullable|string|max:255',
            'desc' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ], [
            'type.required' => 'Jenis customer wajib dipilih.',
            'name.required' => 'Nama customer wajib diisi.',
        ]);
    }

    private function serialize($customer, string $type): array
    {
        return [
            'id' => $customer->id,
            'type' => $type,
            'type_label' => self::TYPES[$type],
            'name' => $customer->name,
            'code' => $customer->code ?? null,
            'alamat' => $customer->alamat ?? null,
            'no_telp' => $customer->no_telp ?? null,
            'desc' => $customer->desc ?? null,
            'is_active' => (bool) ($customer->is_active ?? true),
        ];
    }
}
