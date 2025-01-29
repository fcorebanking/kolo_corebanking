<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEsusulogRequest;
use App\Http\Requests\UpdateEsusulogRequest;
use App\Models\Esusulog;

class EsusulogController extends Controller
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
     * @param  \App\Http\Requests\StoreEsusulogRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreEsusulogRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Esusulog  $esusulog
     * @return \Illuminate\Http\Response
     */
    public function show(Esusulog $esusulog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Esusulog  $esusulog
     * @return \Illuminate\Http\Response
     */
    public function edit(Esusulog $esusulog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateEsusulogRequest  $request
     * @param  \App\Models\Esusulog  $esusulog
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateEsusulogRequest $request, Esusulog $esusulog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Esusulog  $esusulog
     * @return \Illuminate\Http\Response
     */
    public function destroy(Esusulog $esusulog)
    {
        //
    }
}
