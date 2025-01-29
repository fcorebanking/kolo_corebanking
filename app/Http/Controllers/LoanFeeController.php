<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreloanFeeRequest;
use App\Http\Requests\UpdateloanFeeRequest;
use App\Models\loanFee;

class LoanFeeController extends Controller
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
     * @param  \App\Http\Requests\StoreloanFeeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreloanFeeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\loanFee  $loanFee
     * @return \Illuminate\Http\Response
     */
    public function show(loanFee $loanFee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\loanFee  $loanFee
     * @return \Illuminate\Http\Response
     */
    public function edit(loanFee $loanFee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateloanFeeRequest  $request
     * @param  \App\Models\loanFee  $loanFee
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateloanFeeRequest $request, loanFee $loanFee)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\loanFee  $loanFee
     * @return \Illuminate\Http\Response
     */
    public function destroy(loanFee $loanFee)
    {
        //
    }
}
