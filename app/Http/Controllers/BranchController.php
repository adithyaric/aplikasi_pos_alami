<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\Branch;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('branch.index', [
            'branchs' => Branch::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('branch.create', [
            'branchs' => Branch::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BranchRequest $request)
    {
        $data = $request->validated();
        Branch::create($data);

        return redirect(route('branchs.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Branch $branch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Branch $branch)
    {
        return view('branch.edit', [
            'branchs' => $branch,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $data = $request->validated();
        $branch->update($data);

        return redirect(route('branchs.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect(route('branchs.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }
}
