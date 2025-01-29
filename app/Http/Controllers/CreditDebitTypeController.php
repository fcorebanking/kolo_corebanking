<?php

namespace App\Http\Controllers;

use App\Models\CreditDebitType;
use App\Http\Requests\StoreCreditDebitTypeRequest;
use App\Http\Requests\UpdateCreditDebitTypeRequest;

class CreditDebitTypeController extends Controller
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
     * @param  \App\Http\Requests\StoreCreditDebitTypeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCreditDebitTypeRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CreditDebitType  $creditDebitType
     * @return \Illuminate\Http\Response
     */
    public function show(CreditDebitType $creditDebitType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CreditDebitType  $creditDebitType
     * @return \Illuminate\Http\Response
     */
    public function edit(CreditDebitType $creditDebitType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCreditDebitTypeRequest  $request
     * @param  \App\Models\CreditDebitType  $creditDebitType
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCreditDebitTypeRequest $request, CreditDebitType $creditDebitType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CreditDebitType  $creditDebitType
     * @return \Illuminate\Http\Response
     */
    public function destroy(CreditDebitType $creditDebitType)
    {
        //
    }
}
