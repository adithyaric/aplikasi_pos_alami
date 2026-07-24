<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerPoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->normalizeString($this->input('name')),
            'company_name' => $this->normalizeNullableString($this->input('company_name')),
            'address' => $this->normalizeNullableString($this->input('address')),
            'phone' => $this->normalizeNullableString($this->input('phone')),
            'email' => $this->normalizeNullableString($this->input('email')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama Customer PO wajib diisi.',
            'name.string' => 'Nama Customer PO harus berupa teks.',
            'name.max' => 'Nama Customer PO maksimal 255 karakter.',
            'company_name.string' => 'Nama perusahaan harus berupa teks.',
            'company_name.max' => 'Nama perusahaan maksimal 255 karakter.',
            'address.string' => 'Alamat harus berupa teks.',
            'address.max' => 'Alamat maksimal 1000 karakter.',
            'phone.string' => 'Phone harus berupa teks.',
            'phone.max' => 'Phone maksimal 50 karakter.',
            'email.email' => 'Email harus berupa alamat email yang valid.',
            'email.max' => 'Email maksimal 255 karakter.',
        ];
    }

    private function normalizeString($value): string
    {
        return trim((string) $value);
    }

    private function normalizeNullableString($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
