<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama Customer PO wajib diisi.',
            'name.string' => 'Nama Customer PO harus berupa teks.',
            'name.max' => 'Nama Customer PO maksimal 255 karakter.',
        ];
    }
}
