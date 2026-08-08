<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'kode_supplier'            => 'required|string',
            'name'                     => 'required|string',
            'alamat'                   => 'required|string',
            'no_telp'                  => 'required|string',
            'po_number_prefix'         => 'nullable|string|max:255',
            'po_number_padding'        => 'nullable|integer|min:3|max:10',
            'po_template'              => 'nullable|file|mimes:docx,xlsx|max:10240',
            'deadline_days'            => 'nullable|array',
            'deadline_days.*'          => 'integer|between:1,7',
            'deadline_interval_weeks'  => 'nullable|integer|in:1,2,3',
            'deadline_reference_date'  => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'kode_supplier.required' => 'Kode supplier wajib diisi.',
            'kode_supplier.string' => 'Kode supplier harus berupa teks.',

            'name.required' => 'Nama supplier wajib diisi.',
            'name.string' => 'Nama supplier harus berupa teks.',

            'alamat.required' => 'Alamat supplier wajib diisi.',
            'alamat.string' => 'Alamat supplier harus berupa teks.',

            'no_telp.required' => 'Nomor telepon supplier wajib diisi.',
            'no_telp.string' => 'Nomor telepon harus berupa teks.',

            'po_number_prefix.string' => 'Format nomor PO harus berupa teks.',
            'po_number_prefix.max' => 'Format nomor PO maksimal 255 karakter.',
            'po_number_padding.integer' => 'Panjang nomor urut PO harus berupa angka.',
            'po_number_padding.min' => 'Panjang nomor urut PO minimal 3 digit.',
            'po_number_padding.max' => 'Panjang nomor urut PO maksimal 10 digit.',
            'po_template.file' => 'Template PO harus berupa file.',
            'po_template.mimes' => 'Template PO harus menggunakan format DOCX atau XLSX.',
            'po_template.max' => 'Ukuran template PO maksimal 10 MB.',

            'deadline_days.array' => 'Format deadline days harus berupa array.',

            'deadline_days.*.integer' => 'Hari deadline harus berupa angka.',
            'deadline_days.*.between' => 'Hari deadline harus antara 1 sampai 7.',

            'deadline_interval_weeks.integer' => 'Interval minggu deadline harus berupa angka.',
            'deadline_interval_weeks.in' => 'Interval minggu deadline harus bernilai 1, 2, atau 3.',

            'deadline_reference_date.date' => 'Tanggal referensi deadline harus berupa format tanggal yang valid.',
        ];
    }
}
