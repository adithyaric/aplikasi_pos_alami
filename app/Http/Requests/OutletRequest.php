<?php

namespace App\Http\Requests;

use App\Models\Outlet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutletRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'logo' => 'nullable',
            'name' => 'required',
            'jenis_outlet' => [
                'required',
                Rule::in(array_keys(Outlet::typeOptions($this->route('outlet')?->jenis_outlet))),
            ],
            'alamat' => 'required',
            // 'npwp' => 'required',
            // 'slogan' => 'required',
            'desc' => 'nullable',
            // 'footer' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama outlet wajib diisi.',
            'jenis_outlet.required' => 'Jenis outlet wajib dipilih.',
            'jenis_outlet.in' => 'Jenis outlet yang dipilih tidak valid.',
            'alamat.required' => 'Alamat outlet wajib diisi.',
        ];
    }
}
