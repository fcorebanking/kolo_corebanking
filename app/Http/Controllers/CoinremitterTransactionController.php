<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorecoinremitterTransactionRequest;
use App\Http\Requests\UpdatecoinremitterTransactionRequest;
use App\Models\coinremitterTransaction;

class CoinremitterTransactionController extends Controller
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
     * @param  \App\Http\Requests\StorecoinremitterTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorecoinremitterTransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\coinremitterTransaction  $coinremitterTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(coinremitterTransaction $coinremitterTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\coinremitterTransaction  $coinremitterTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(coinremitterTransaction $coinremitterTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatecoinremitterTransactionRequest  $request
     * @param  \App\Models\coinremitterTransaction  $coinremitterTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatecoinremitterTransactionRequest $request, coinremitterTransaction $coinremitterTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\coinremitterTransaction  $coinremitterTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(coinremitterTransaction $coinremitterTransaction)
    {
        //
    }
}
