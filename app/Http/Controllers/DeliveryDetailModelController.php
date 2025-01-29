<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliveryDetailModelRequest;
use App\Http\Requests\UpdateDeliveryDetailModelRequest;
use App\Models\DeliveryDetailModel;

class DeliveryDetailModelController extends Controller
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
     * @param  \App\Http\Requests\StoreDeliveryDetailModelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreDeliveryDetailModelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\DeliveryDetailModel  $deliveryDetailModel
     * @return \Illuminate\Http\Response
     */
    public function show(DeliveryDetailModel $deliveryDetailModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\DeliveryDetailModel  $deliveryDetailModel
     * @return \Illuminate\Http\Response
     */
    public function edit(DeliveryDetailModel $deliveryDetailModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateDeliveryDetailModelRequest  $request
     * @param  \App\Models\DeliveryDetailModel  $deliveryDetailModel
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateDeliveryDetailModelRequest $request, DeliveryDetailModel $deliveryDetailModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\DeliveryDetailModel  $deliveryDetailModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeliveryDetailModel $deliveryDetailModel)
    {
        //
    }
}
