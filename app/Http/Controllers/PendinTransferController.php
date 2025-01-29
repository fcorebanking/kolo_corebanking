<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorependinTransferRequest;
use App\Http\Requests\UpdatependinTransferRequest;
use App\Models\pendinTransfer;

class PendinTransferController extends Controller
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
     * @param  \App\Http\Requests\StorependinTransferRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorependinTransferRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\pendinTransfer  $pendinTransfer
     * @return \Illuminate\Http\Response
     */
    public function show(pendinTransfer $pendinTransfer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\pendinTransfer  $pendinTransfer
     * @return \Illuminate\Http\Response
     */
    public function edit(pendinTransfer $pendinTransfer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatependinTransferRequest  $request
     * @param  \App\Models\pendinTransfer  $pendinTransfer
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatependinTransferRequest $request, pendinTransfer $pendinTransfer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\pendinTransfer  $pendinTransfer
     * @return \Illuminate\Http\Response
     */
    public function destroy(pendinTransfer $pendinTransfer)
    {
        //
    }
}
