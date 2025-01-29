<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePendingSavingRequest;
use App\Http\Requests\UpdatePendingSavingRequest;
use App\Models\PendingSaving;

class PendingSavingController extends Controller
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
     * @param  \App\Http\Requests\StorePendingSavingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePendingSavingRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PendingSaving  $pendingSaving
     * @return \Illuminate\Http\Response
     */
    public function show(PendingSaving $pendingSaving)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PendingSaving  $pendingSaving
     * @return \Illuminate\Http\Response
     */
    public function edit(PendingSaving $pendingSaving)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePendingSavingRequest  $request
     * @param  \App\Models\PendingSaving  $pendingSaving
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePendingSavingRequest $request, PendingSaving $pendingSaving)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PendingSaving  $pendingSaving
     * @return \Illuminate\Http\Response
     */
    public function destroy(PendingSaving $pendingSaving)
    {
        //
    }
}
