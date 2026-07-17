<?php

namespace App\Http\Controllers;

use App\Http\Requests\CanvasRequest;
use App\Models\Canvas;

class CanvasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('canvas.index', [
            'canvases' => Canvas::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('canvas.create', [
            'canvases' => Canvas::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CanvasRequest $request)
    {
        $data = $request->validated();
        Canvas::create($data);

        return redirect(route('canvases.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Canvas $canvas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Canvas $canvas)
    {
        return view('canvas.edit', [
            'canvases' => $canvas,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(CanvasRequest $request, Canvas $canvas)
    {
        $data = $request->validated();
        $canvas->update($data);

        return redirect(route('canvases.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Canvas $canvas)
    {
        $canvas->delete();

        return redirect(route('canvases.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }
}
