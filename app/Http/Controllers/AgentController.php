<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgentRequest;
use App\Models\Agent;

class AgentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('agents.index', [
            'agents' => Agent::get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('agents.create', [
            'agents' => Agent::get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AgentRequest $request)
    {
        $data = $request->validated();
        Agent::create($data);

        return redirect(route('agents.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Agent $agent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Agent $agent)
    {
        return view('agents.edit', [
            'agents' => $agent,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(AgentRequest $request, Agent $agent)
    {
        $data = $request->validated();
        $agent->update($data);

        return redirect(route('agents.index'))->with('toast_success', 'Berhasil Menyimpan Data!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Agent $agent)
    {
        $agent->delete();

        return redirect(route('agents.index'))->with('toast_success', 'Berhasil Menghapus Data!');
    }
}
