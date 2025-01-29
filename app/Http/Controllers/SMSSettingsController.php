<?php

namespace App\Http\Controllers;

use App\Models\SMS_Settings;
use App\Http\Requests\StoreSMS_SettingsRequest;
use App\Http\Requests\UpdateSMS_SettingsRequest;

class SMSSettingsController extends Controller
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
     * @param  \App\Http\Requests\StoreSMS_SettingsRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSMS_SettingsRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SMS_Settings  $sMS_Settings
     * @return \Illuminate\Http\Response
     */
    public function show(SMS_Settings $sMS_Settings)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SMS_Settings  $sMS_Settings
     * @return \Illuminate\Http\Response
     */
    public function edit(SMS_Settings $sMS_Settings)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSMS_SettingsRequest  $request
     * @param  \App\Models\SMS_Settings  $sMS_Settings
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSMS_SettingsRequest $request, SMS_Settings $sMS_Settings)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SMS_Settings  $sMS_Settings
     * @return \Illuminate\Http\Response
     */
    public function destroy(SMS_Settings $sMS_Settings)
    {
        //
    }
}
