<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehousePenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['superadmin', 'admin-gudang', 'owner'], true);
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (isset($item['price'])) {
                    $item['price'] = $this->cleanNumeric($item['price']);
                }

                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'discount' => $this->cleanNumeric($this->input('discount')),
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        return [
            'sale_date' => 'required|date',
            'buyer_type' => 'required|in:agent,canvas,outlet',
            'agent_id' => 'nullable|required_if:buyer_type,agent|exists:agents,id',
            'canvas_id' => 'nullable|required_if:buyer_type,canvas|exists:canvases,id',
            'outlet_target_id' => 'nullable|required_if:buyer_type,outlet|exists:outlets,id',
            'payment_type' => 'required|in:cash,termin',
            'payment_status' => 'nullable|in:paid,unpaid,partial',
            'due_date' => 'nullable|date|after_or_equal:sale_date',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_date.required' => 'Tanggal penjualan wajib diisi.',
            'buyer_type.required' => 'Jenis pembeli wajib dipilih.',
            'agent_id.required_if' => 'Agen wajib dipilih.',
            'canvas_id.required_if' => 'Canvas wajib dipilih.',
            'outlet_target_id.required_if' => 'Cabang wajib dipilih.',
            'payment_type.required' => 'Tipe pembayaran wajib dipilih.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal penjualan.',
            'items.required' => 'Minimal harus ada satu produk.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.product_id.distinct' => 'Produk tidak boleh duplikat.',
            'items.*.qty.required' => 'Qty wajib diisi.',
            'items.*.qty.min' => 'Qty minimal 1.',
            'items.*.unit.required' => 'Satuan wajib dipilih.',
            'items.*.price.required' => 'Harga jual wajib diisi.',
            'items.*.price.min' => 'Harga jual tidak boleh negatif.',
        ];
    }

    private function cleanNumeric($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
