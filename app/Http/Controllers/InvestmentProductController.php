<?php

namespace App\Http\Controllers;

use App\Models\InvestmentProduct;
use App\Http\Requests\StoreInvestmentProductRequest;
use App\Http\Requests\UpdateInvestmentProductRequest;

class InvestmentProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware("custom_permission:manage-investment-product|root");
    }
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
     * @param  \App\Http\Requests\StoreInvestmentProductRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInvestmentProductRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\InvestmentProduct  $investmentProduct
     * @return \Illuminate\Http\Response
     */
    public function show(InvestmentProduct $investmentProduct)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\InvestmentProduct  $investmentProduct
     * @return \Illuminate\Http\Response
     */
    public function edit(InvestmentProduct $investmentProduct)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInvestmentProductRequest  $request
     * @param  \App\Models\InvestmentProduct  $investmentProduct
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvestmentProductRequest $request, InvestmentProduct $investmentProduct)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\InvestmentProduct  $investmentProduct
     * @return \Illuminate\Http\Response
     */
    public function destroy(InvestmentProduct $investmentProduct)
    {
        //
    }
}
