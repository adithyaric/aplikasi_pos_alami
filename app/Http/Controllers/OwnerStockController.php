<?php

namespace App\Http\Controllers;

use App\Models\Outlet;
use Illuminate\Http\Request;

class OwnerStockController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('branch-stock.index', [
            'owner_id' => $request->owner_id,
        ]);
    }

    public function show(Outlet $owner)
    {
        return redirect()->route('branch-stock.index', [
            'owner_id' => $owner->id,
        ]);
    }
}
