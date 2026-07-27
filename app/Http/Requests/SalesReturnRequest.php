<?php

namespace App\Http\Requests;

use App\Services\SalesReturnManager;
use Illuminate\Foundation\Http\FormRequest;

class SalesReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['superadmin', 'admin-gudang', 'owner', 'admin-cabang', 'sales'], true);
    }

    protected function prepareForValidation(): void
    {
        $buyerType = (string) $this->input('buyer_type');
        $isBranchScoped = $this->user()?->isBranchScoped()
            && in_array($this->user()?->role, ['admin-cabang', 'sales'], true);

        $returnScope = $this->input('return_scope');
        if ($isBranchScoped) {
            $returnScope = SalesReturnManager::SCOPE_BRANCH_CUSTOMER;
            $buyerType = 'toko';
        } else {
            $returnScope = $buyerType === 'outlet'
                ? SalesReturnManager::SCOPE_WAREHOUSE_BRANCH
                : SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE;
        }

        $items = collect($this->input('product', []))
            ->map(function ($item) {
                $item['price'] = $this->cleanNumeric($item['price'] ?? 0);
                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'return_scope' => $returnScope,
            'buyer_type' => $buyerType,
            'source_outlet_id' => $isBranchScoped ? $this->user()?->branchId() : $this->input('source_outlet_id'),
            'product' => $items,
        ]);
    }

    public function rules(): array
    {
        $isBranchScoped = $this->user()?->isBranchScoped()
            && in_array($this->user()?->role, ['admin-cabang', 'sales'], true);

        return [
            'code' => 'required|string|max:255',
            'tanggal' => 'required|date',
            'return_scope' => 'required|in:'.implode(',', [
                SalesReturnManager::SCOPE_WAREHOUSE_AFFILIATE,
                SalesReturnManager::SCOPE_WAREHOUSE_BRANCH,
                SalesReturnManager::SCOPE_BRANCH_CUSTOMER,
            ]),
            'buyer_type' => $isBranchScoped ? 'required|in:toko' : 'required|in:agent,canvas,outlet',
            'buyer_id' => 'required|integer',
            'source_outlet_id' => $isBranchScoped ? 'required|exists:outlets,id' : 'nullable|exists:outlets,id',
            'notes' => 'nullable|string',
            'product' => 'required|array|min:1',
            'product.*.product_id' => 'required|exists:products,id',
            'product.*.qty' => 'required|numeric|min:1',
            'product.*.unit' => 'nullable|string|max:255',
            'product.*.price' => 'required|numeric|min:1',
            'product.*.alasan' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode retur wajib diisi.',
            'tanggal.required' => 'Tanggal retur wajib diisi.',
            'buyer_type.required' => 'Jenis pembeli wajib dipilih.',
            'buyer_id.required' => 'Pembeli wajib dipilih.',
            'product.required' => 'Minimal harus ada satu produk retur.',
            'product.*.product_id.required' => 'Produk retur wajib dipilih.',
            'product.*.qty.min' => 'Qty retur minimal 1.',
            'product.*.price.min' => 'Harga retur harus lebih dari 0.',
        ];
    }

    private function cleanNumeric($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
