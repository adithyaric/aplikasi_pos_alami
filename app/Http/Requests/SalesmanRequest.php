<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesmanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $salesman = $this->route('salesman');
        $userId = $salesman?->user_id;

        return [
            'name' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'no_telp' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'no_telp')->ignore($userId),
            ],
            'code' => 'nullable|string|max:255',
            'outlet_id' => 'nullable|exists:outlets,id',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [$salesman ? 'nullable' : 'required', 'string', 'min:6', 'same:confirm-password'],
        ];
    }
}
