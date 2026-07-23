<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalesmanRequest;
use App\Models\Outlet;
use App\Models\Salesman;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SalesmanController extends Controller
{
    public function index(Request $request)
    {
        $salesmans = Salesman::with(['outlet:id,name', 'user:id,email,no_telp'])->get();
        if ($request->wantsJson()) {
            return response($salesmans);
        }

        return view('salesmans.index', [
            'salesmans' => $salesmans,
        ]);
    }

    public function create()
    {
        return view('salesmans.create', [
            'outlets' => Outlet::branches()->orderBy('name')->get(),
        ]);
    }

    public function store(SalesmanRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $salesman = Salesman::create($this->salesmanAttributes($data));

            $this->syncUserForSalesman($salesman, $data);
        });

        return redirect(route('salesman.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function show(Salesman $salesman)
    {
        dd($salesman);
    }

    public function edit(Salesman $salesman)
    {
        $salesman->loadMissing('user');

        return view('salesmans.edit', [
            'salesman' => $salesman,
            'outlets' => Outlet::branches()->orderBy('name')->get(),
        ]);
    }

    public function update(SalesmanRequest $request, Salesman $salesman)
    {
        DB::transaction(function () use ($request, $salesman) {
            $data = $request->validated();
            $salesman->update($this->salesmanAttributes($data));

            $this->syncUserForSalesman($salesman->fresh(), $data);
        });

        return redirect(route('salesman.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(Salesman $salesman)
    {
        DB::transaction(function () use ($salesman) {
            $salesman->loadMissing('user');
            $salesman->user?->delete();
            $salesman->delete();
        });

        return redirect(route('salesman.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    private function salesmanAttributes(array $data): array
    {
        return [
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'alamat' => $data['alamat'],
            'no_telp' => $data['no_telp'],
            'outlet_id' => $data['outlet_id'] ?? null,
        ];
    }

    private function syncUserForSalesman(Salesman $salesman, array $data): void
    {
        $salesman->loadMissing('user');
        $user = $salesman->user;

        if (! $user && empty($data['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Password wajib diisi untuk membuat akun login sales.',
            ]);
        }

        $attributes = [
            'name' => $salesman->name,
            'username' => $user?->username ?: $this->generateUsernameForSalesman($salesman),
            'role' => 'sales',
            'status' => $user?->status ?: 'active',
            'email' => $data['email'] ?? null,
            'alamat' => $salesman->alamat,
            'no_telp' => $salesman->no_telp,
            'outlet_id' => $salesman->outlet_id,
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = Hash::make($data['password']);
        }

        if ($user) {
            $user->update($attributes);
            return;
        }

        $user = User::create($attributes);
        $salesman->update(['user_id' => $user->id]);
    }

    private function generateUsernameForSalesman(Salesman $salesman): string
    {
        $base = Str::slug((string) ($salesman->code ?: $salesman->name ?: 'sales'), '_');
        $base = $base !== '' ? $base : 'sales';
        $username = $base;
        $suffix = 1;

        while (User::withTrashed()->where('username', $username)->exists()) {
            $suffix++;
            $username = $base.'_'.$suffix;
        }

        return $username;
    }
}
