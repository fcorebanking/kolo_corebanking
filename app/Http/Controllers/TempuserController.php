<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoretempuserRequest;
use App\Http\Requests\UpdatetempuserRequest;
use App\Models\tempuser;

class TempuserController extends Controller
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
     * @param  \App\Http\Requests\StoretempuserRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoretempuserRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\tempuser  $tempuser
     * @return \Illuminate\Http\Response
     */
    public function show(tempuser $tempuser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\tempuser  $tempuser
     * @return \Illuminate\Http\Response
     */
    public function edit(tempuser $tempuser)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatetempuserRequest  $request
     * @param  \App\Models\tempuser  $tempuser
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatetempuserRequest $request, tempuser $tempuser)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\tempuser  $tempuser
     * @return \Illuminate\Http\Response
     */
    public function destroy(tempuser $tempuser)
    {
        //
    }
}
