<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required',
            'username' => 'required',
            'outlet_id' => ['required_if:role,admin-cabang', 'nullable', Rule::exists('outlets', 'id')->where(fn ($query) => $query->where('jenis_outlet', 'branch'))],
            'role' => 'required|in:superadmin,admin-gudang,admin-cabang',
            'status' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
        ];
    }

    public function messages()
    {
        return [
            // Name field messages
            'name.required' => 'Nama lengkap wajib diisi.',

            // Username field messages
            'username.required' => 'Username wajib diisi.',

            // Outlet ID field messages
            'outlet_id.required_if' => 'Outlet wajib dipilih untuk role :input.',

            // Role field messages
            'role.required' => 'Role pengguna wajib dipilih.',

            // Status field messages
            'status.required' => 'Status akun wajib dipilih.',

            // Email field messages
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email tidak valid. Contoh: nama@domain.com',

            // Password field messages
            'password.required' => 'Password wajib diisi.',
            'password.same' => 'Password dan konfirmasi password harus sama.',
        ];
    }
}
