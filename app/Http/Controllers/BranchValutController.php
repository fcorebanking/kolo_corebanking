<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchValutRequest;
use App\Http\Requests\UpdateBranchValutRequest;
use App\Models\BranchValut;

class BranchValutController extends Controller
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
     * @param  \App\Http\Requests\StoreBranchValutRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBranchValutRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\BranchValut  $branchValut
     * @return \Illuminate\Http\Response
     */
    public function show(BranchValut $branchValut)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BranchValut  $branchValut
     * @return \Illuminate\Http\Response
     */
    public function edit(BranchValut $branchValut)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBranchValutRequest  $request
     * @param  \App\Models\BranchValut  $branchValut
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBranchValutRequest $request, BranchValut $branchValut)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BranchValut  $branchValut
     * @return \Illuminate\Http\Response
     */
    public function destroy(BranchValut $branchValut)
    {
        //
    }
}
