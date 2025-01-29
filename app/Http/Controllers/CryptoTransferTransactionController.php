<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCryptoTransferTransactionRequest;
use App\Http\Requests\UpdateCryptoTransferTransactionRequest;
use App\Models\CryptoTransferTransaction;

class CryptoTransferTransactionController extends Controller
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
     * @param  \App\Http\Requests\StoreCryptoTransferTransactionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCryptoTransferTransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CryptoTransferTransaction  $cryptoTransferTransaction
     * @return \Illuminate\Http\Response
     */
    public function show(CryptoTransferTransaction $cryptoTransferTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CryptoTransferTransaction  $cryptoTransferTransaction
     * @return \Illuminate\Http\Response
     */
    public function edit(CryptoTransferTransaction $cryptoTransferTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCryptoTransferTransactionRequest  $request
     * @param  \App\Models\CryptoTransferTransaction  $cryptoTransferTransaction
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCryptoTransferTransactionRequest $request, CryptoTransferTransaction $cryptoTransferTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CryptoTransferTransaction  $cryptoTransferTransaction
     * @return \Illuminate\Http\Response
     */
    public function destroy(CryptoTransferTransaction $cryptoTransferTransaction)
    {
        //
    }
}
