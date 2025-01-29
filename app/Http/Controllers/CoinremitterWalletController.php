<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorecoinremitterWalletRequest;
use App\Http\Requests\UpdatecoinremitterWalletRequest;
use App\Models\coinremitterWallet;

class CoinremitterWalletController extends Controller
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
     * @param  \App\Http\Requests\StorecoinremitterWalletRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorecoinremitterWalletRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\coinremitterWallet  $coinremitterWallet
     * @return \Illuminate\Http\Response
     */
    public function show(coinremitterWallet $coinremitterWallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\coinremitterWallet  $coinremitterWallet
     * @return \Illuminate\Http\Response
     */
    public function edit(coinremitterWallet $coinremitterWallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatecoinremitterWalletRequest  $request
     * @param  \App\Models\coinremitterWallet  $coinremitterWallet
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatecoinremitterWalletRequest $request, coinremitterWallet $coinremitterWallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\coinremitterWallet  $coinremitterWallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(coinremitterWallet $coinremitterWallet)
    {
        //
    }
}
