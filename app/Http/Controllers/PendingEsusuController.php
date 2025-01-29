<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePendingEsusuRequest;
use App\Http\Requests\UpdatePendingEsusuRequest;
use App\Models\PendingEsusu;

class PendingEsusuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePendingEsusuRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePendingEsusuRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PendingEsusu  $pendingEsusu
     * @return \Illuminate\Http\Response
     */
    public function show(PendingEsusu $pendingEsusu)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PendingEsusu  $pendingEsusu
     * @return \Illuminate\Http\Response
     */
    public function edit(PendingEsusu $pendingEsusu)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePendingEsusuRequest  $request
     * @param  \App\Models\PendingEsusu  $pendingEsusu
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePendingEsusuRequest $request, PendingEsusu $pendingEsusu)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PendingEsusu  $pendingEsusu
     * @return \Illuminate\Http\Response
     */
    public function destroy(PendingEsusu $pendingEsusu)
    {
        //
    }
}
