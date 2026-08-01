<?php

namespace App\Http\Controllers;

use App\Http\Requests\OutletRequest;
use App\Models\Kas;
use App\Models\Outlet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OutletController extends Controller
{
    public function getKas($outlet_id)
    {
        $kas = Kas::where('outlet_id', $outlet_id)->get();

        return response()->json($kas);
    }

    public function index()
    {
        return view('outlets.index', [
            'outlets' => Outlet::branches()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('outlets.create');
    }

    public function store(OutletRequest $request)
    {
        $data = $request->validated();
        $data['jenis_outlet'] = 'branch';

        // Handle file upload
        if ($request->hasFile('logo')) {
            // Get the uploaded file
            $file = $request->file('logo');

            // Generate a unique file name
            $fileName = time().'.'.$file->getClientOriginalExtension();

            // Store the file
            $file->storeAs('public/logos', $fileName);

            // Add the file path to the data array
            $data['logo'] = 'storage/logos/'.$fileName;
        }

        Outlet::create($data);

        return redirect(route('outlet.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function show(Outlet $outlet)
    {
        // dd($outlet);
        return view('outlets.show', [
            'outlet' => $outlet,
        ]);
    }

    public function edit(Outlet $outlet)
    {
        return view('outlets.edit', [
            'outlet' => $outlet,
        ]);
    }

    public function update(OutletRequest $request, Outlet $outlet)
    {
        $data = $request->validated();
        $data['jenis_outlet'] = 'branch';
        if ($request->hasFile('logo')) {
            // Delete the old image file
            if ($outlet->logo) {
                Storage::delete(str_replace('storage', 'public', $outlet->logo));
            }
            // Store the new image file
            $file = $request->file('logo');
            $fileName = time().'.'.$file->getClientOriginalExtension();
            $file->storeAs('public/logos', $fileName);
            $data['logo'] = 'storage/logos/'.$fileName;
        }
        $outlet->update($data);

        return redirect(route('outlet.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    public function destroy(Outlet $outlet)
    {
        // Delete the image file
        if ($outlet->logo) {
            Storage::delete(str_replace('storage', 'public', $outlet->logo));
        }

        $outlet->delete();

        return redirect(route('outlet.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }

    public function storeShop(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alamat' => 'required|string|max:255',
            'desc' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Nama customer/toko wajib diisi.',
            'alamat.required' => 'Alamat customer/toko wajib diisi.',
        ]);

        $name = trim((string) $validated['name']);

        $shop = Outlet::withTrashed()
            ->where('jenis_outlet', 'toko')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($shop) {
            if ($shop->trashed()) {
                $shop->restore();
            }

            $shop->fill([
                'name' => $name,
                'jenis_outlet' => 'toko',
                'alamat' => trim((string) $validated['alamat']),
                'desc' => trim((string) ($validated['desc'] ?? '')) ?: null,
            ]);
            $shop->save();
        } else {
            $shop = Outlet::create([
                'name' => $name,
                'jenis_outlet' => 'toko',
                'alamat' => trim((string) $validated['alamat']),
                'desc' => trim((string) ($validated['desc'] ?? '')) ?: null,
                'logo' => null,
                'npwp' => null,
                'slogan' => null,
                'footer' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'alamat' => $shop->alamat,
                'desc' => $shop->desc,
            ],
        ], 201);
    }
}
