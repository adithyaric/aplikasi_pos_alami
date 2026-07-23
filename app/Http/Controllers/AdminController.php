<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminRequest;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    private const MANAGED_ROLES = [
        'superadmin',
        'admin-gudang',
        'staff-outlet',
        'owner',
    ];

    public function index()
    {
        return view('admins.index', [
            'users' => User::whereNotIn('role', ['customer', 'sales'])->get(),
        ]);
    }

    public function create()
    {
        return view('admins.create', [
            'roles' => self::MANAGED_ROLES,
            'outlets' => Outlet::get(),
        ]);
    }

    public function store(AdminRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect(route('admin.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function show(User $user)
    {
        dd($user);
    }

    public function edit(User $admin)
    {
        abort_if($admin->role === 'sales', 404);

        return view('admins.edit', [
            'admin' => $admin,
            'roles' => self::MANAGED_ROLES,
            'outlets' => Outlet::get(),
        ]);
    }

    public function update(Request $request, User $admin)
    {
        $this->validate($request, [
            'name' => 'required',
            'username' => 'required',
            'outlet_id' => 'required_if:role,staff-outlet|required_if:role,admin-gudang|nullable|exists:outlets,id',
            'role' => 'required|in:'.implode(',', self::MANAGED_ROLES),
            'status' => 'required',
            'email' => 'required|email|unique:users,email,'.$admin->id,
            'password' => 'same:confirm-password',
        ]);

        $data = $request->all();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            $data = Arr::except($data, ['password']);
        }

        $admin->update($data);

        return redirect(route('admin.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(User $admin)
    {
        $admin->delete();

        return redirect(route('admin.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }
}
