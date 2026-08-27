<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehousePenjualanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission(['penjualan.warehouse', 'penjualan.branch']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->map(function ($item) {
                if (isset($item['price'])) {
                    $item['price'] = $this->cleanNumeric($item['price']);
                }

                $item['discount'] = $this->cleanNumeric($item['discount'] ?? 0);

                return $item;
            })
            ->values()
            ->all();

        $paymentType = (string) ($this->input('payment_type') ?: 'termin');
        $paymentStatus = (string) $this->input('payment_status');

        if ($paymentStatus === '') {
            $paymentStatus = $paymentType === 'cash' ? 'paid' : 'unpaid';
        }

        $oldDebtOverride = $this->input('old_debt_override');
        $oldDebtOverride = $oldDebtOverride === null || trim((string) $oldDebtOverride) === ''
            ? null
            : $this->cleanNumeric($oldDebtOverride);

        $this->merge([
            'payment_type' => $paymentType,
            'payment_status' => $paymentStatus,
            'discount' => $this->cleanNumeric($this->input('discount')),
            'shipping_cost' => $this->cleanNumeric($this->input('shipping_cost')),
            'old_debt_override' => $oldDebtOverride,
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        $isBranchSale = $this->user()?->isBranchScoped()
            && in_array($this->user()?->role, ['admin-cabang', 'leader-cabang', 'sales'], true);

        return [
            'offline_client_id' => 'nullable|string|max:100',
            'sale_date' => 'required|date',
            'buyer_type' => $isBranchSale ? 'required|in:toko' : 'required|in:agent,canvas,outlet,toko',
            'agent_id' => $isBranchSale ? 'nullable' : 'nullable|required_if:buyer_type,agent|exists:agents,id',
            'canvas_id' => $isBranchSale ? 'nullable' : 'nullable|required_if:buyer_type,canvas|exists:canvases,id',
            'outlet_target_id' => $isBranchSale
                ? ['required', Rule::exists('outlets', 'id')->where(fn ($query) => $query->where('jenis_outlet', 'toko'))]
                : 'nullable|required_if:buyer_type,outlet|exists:outlets,id',
            'toko_id' => $isBranchSale ? 'nullable' : 'nullable|required_if:buyer_type,toko|exists:outlets,id',
            'payment_type' => 'required|in:cash,termin',
            'payment_status' => 'nullable|in:paid,unpaid,partial',
            'due_date' => 'nullable|date|after_or_equal:sale_date',
            'discount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'old_debt_override' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'latitude' => $isBranchSale ? 'nullable|numeric|between:-90,90' : 'prohibited',
            'longitude' => $isBranchSale ? 'nullable|numeric|between:-180,180' : 'prohibited',
            'location_accuracy' => $isBranchSale ? 'nullable|numeric|min:0|max:100000' : 'prohibited',
            'location_captured_at' => $isBranchSale ? 'nullable|date' : 'prohibited',
            'sales_photos' => $isBranchSale ? 'nullable|array|max:10' : 'prohibited',
            'sales_photos.*' => $isBranchSale ? 'image|mimes:jpg,jpeg,png,webp|max:5120' : 'prohibited',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id|distinct',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.unit' => 'required|string|max:255',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'sale_date.required' => 'Tanggal penjualan wajib diisi.',
            'buyer_type.required' => 'Jenis pembeli wajib dipilih.',
            'agent_id.required_if' => 'Agen wajib dipilih.',
            'canvas_id.required_if' => 'Canvas wajib dipilih.',
            'outlet_target_id.required' => 'Customer/Toko wajib dipilih.',
            'outlet_target_id.required_if' => 'Cabang wajib dipilih.',
            'outlet_target_id.exists' => 'Customer/Toko tidak valid.',
            'toko_id.required_if' => 'Toko wajib dipilih.',
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
            'items.*.discount.min' => 'Diskon item tidak boleh negatif.',
            'latitude.between' => 'Latitude lokasi tidak valid.',
            'longitude.between' => 'Longitude lokasi tidak valid.',
            'sales_photos.array' => 'Foto penjualan tidak valid.',
            'sales_photos.max' => 'Maksimal 10 foto penjualan per penyimpanan.',
            'sales_photos.*.image' => 'Foto penjualan harus berupa gambar.',
            'sales_photos.*.mimes' => 'Foto penjualan harus JPG, PNG, atau WEBP.',
            'sales_photos.*.max' => 'Ukuran setiap foto penjualan maksimal 5MB.',
        ];
    }

    private function cleanNumeric($value): int
    {
        return (int) preg_replace('/[^\d]/', '', (string) $value);
    }
}
