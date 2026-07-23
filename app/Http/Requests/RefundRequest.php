<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|string|max:255',
            'kas_id' => 'nullable|exists:kas,id',
            'penjualan_id' => 'required|exists:penjualans,id',
            'tanggal' => 'required|date',
            'total' => 'required',
            'product' => 'required|array|min:1',
            'product.*.product_id' => 'required|exists:products,id',
            'product.*.qty' => 'required|integer|min:1',
            'product.*.alasan' => 'nullable|string|max:255',
        ];
    }
}
