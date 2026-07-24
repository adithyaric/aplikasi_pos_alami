<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerPoRequest;
use App\Models\CustomerPo;
use App\Models\Pembelian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CustomerPoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('q') || $request->filled('term') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return $this->options($request);
        }

        return view('customer-pos.index', [
            'customerPos' => CustomerPo::orderBy('name')->orderBy('company_name')->get(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('q', $request->input('term', '')));

        $customerPos = $this->customerPoOptions($search)
            ->map(fn (array $item) => [
                'id' => $item['name'],
                'text' => $item['name'],
                'name' => $item['name'],
                'company_name' => $item['company_name'],
                'address' => $item['address'],
                'phone' => $item['phone'],
                'email' => $item['email'],
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
            $this->customerPoOptions($search)
                ->values()
        );
    }

    public function create()
    {
        return view('customer-pos.create');
    }

    public function store(CustomerPoRequest $request)
    {
        $customerPo = $this->persistCustomerPo($request->validated());

        if ($request->boolean('_ajax') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return new JsonResponse([
                'data' => $this->serializeCustomerPo($customerPo),
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

    private function persistCustomerPo(array $attributes): CustomerPo
    {
        $normalized = $this->normalizeCustomerPoAttributes($attributes);

        $existing = CustomerPo::withTrashed()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($normalized['name'])])
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->fill($normalized);

            if ($existing->isDirty()) {
                $existing->save();
            }

            return $existing->fresh();
        }

        return CustomerPo::create($normalized);
    }

    private function customerPoOptions(string $search): Collection
    {
        return collect(
            CustomerPo::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($innerQuery) use ($search) {
                        $innerQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('company_name', 'like', '%'.$search.'%')
                            ->orWhere('address', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                })
                ->orderBy('name')
                ->get([
                    'name',
                    'company_name',
                    'address',
                    'phone',
                    'email',
                ])
                ->map(fn (CustomerPo $customerPo) => $this->serializeCustomerPo($customerPo))
                ->all()
        )
            ->merge(
                Pembelian::query()
                    ->whereNotNull('customer_po')
                    ->when($search !== '', fn ($query) => $query->where('customer_po', 'like', '%'.$search.'%'))
                    ->pluck('customer_po')
                    ->map(fn ($name) => [
                        'name' => trim((string) $name),
                        'company_name' => null,
                        'address' => null,
                        'phone' => null,
                        'email' => null,
                    ])
            )
            ->filter(fn (array $item) => $item['name'] !== '')
            ->unique(fn (array $item) => mb_strtolower($item['name']))
            ->sortBy(fn (array $item) => mb_strtolower($item['name']))
            ->take(50)
            ->values();
    }

    private function normalizeCustomerPoAttributes(array $attributes): array
    {
        return [
            'name' => trim((string) ($attributes['name'] ?? '')),
            'company_name' => $this->normalizeNullableString($attributes['company_name'] ?? null),
            'address' => $this->normalizeNullableString($attributes['address'] ?? null),
            'phone' => $this->normalizeNullableString($attributes['phone'] ?? null),
            'email' => $this->normalizeNullableString($attributes['email'] ?? null),
        ];
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function serializeCustomerPo(CustomerPo|array $customerPo): array
    {
        if (is_array($customerPo)) {
            return [
                'name' => trim((string) ($customerPo['name'] ?? '')),
                'company_name' => $this->normalizeNullableString($customerPo['company_name'] ?? null),
                'address' => $this->normalizeNullableString($customerPo['address'] ?? null),
                'phone' => $this->normalizeNullableString($customerPo['phone'] ?? null),
                'email' => $this->normalizeNullableString($customerPo['email'] ?? null),
            ];
        }

        return [
            'id' => $customerPo->id,
            'name' => $customerPo->name,
            'company_name' => $customerPo->company_name,
            'address' => $customerPo->address,
            'phone' => $customerPo->phone,
            'email' => $customerPo->email,
        ];
    }
}
