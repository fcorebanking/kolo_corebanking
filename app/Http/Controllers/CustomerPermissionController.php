<?php

namespace App\Http\Controllers;

use App\Models\CustomerPermission;
use App\Http\Requests\StoreCustomerPermissionRequest;
use App\Http\Requests\UpdateCustomerPermissionRequest;

class CustomerPermissionController extends Controller
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
     * @param  \App\Http\Requests\StoreCustomerPermissionRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerPermissionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CustomerPermission  $customerPermission
     * @return \Illuminate\Http\Response
     */
    public function show(CustomerPermission $customerPermission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CustomerPermission  $customerPermission
     * @return \Illuminate\Http\Response
     */
    public function edit(CustomerPermission $customerPermission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCustomerPermissionRequest  $request
     * @param  \App\Models\CustomerPermission  $customerPermission
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerPermissionRequest $request, CustomerPermission $customerPermission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CustomerPermission  $customerPermission
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomerPermission $customerPermission)
    {
        //
    }
}
