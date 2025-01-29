<?php

namespace App\Http\Controllers;

use App\Http\Requests\Storepush_notificationModelRequest;
use App\Http\Requests\Updatepush_notificationModelRequest;
use App\Models\push_notificationModel;

class PushNotificationModelController extends Controller
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
     * @param  \App\Http\Requests\Storepush_notificationModelRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Storepush_notificationModelRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\push_notificationModel  $push_notificationModel
     * @return \Illuminate\Http\Response
     */
    public function show(push_notificationModel $push_notificationModel)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\push_notificationModel  $push_notificationModel
     * @return \Illuminate\Http\Response
     */
    public function edit(push_notificationModel $push_notificationModel)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Updatepush_notificationModelRequest  $request
     * @param  \App\Models\push_notificationModel  $push_notificationModel
     * @return \Illuminate\Http\Response
     */
    public function update(Updatepush_notificationModelRequest $request, push_notificationModel $push_notificationModel)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\push_notificationModel  $push_notificationModel
     * @return \Illuminate\Http\Response
     */
    public function destroy(push_notificationModel $push_notificationModel)
    {
        //
    }
}
