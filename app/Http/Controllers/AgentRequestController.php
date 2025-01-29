<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentRequestRequest;
use App\Http\Requests\UpdateAgentRequestRequest;
use App\Models\AgentRequest;

class AgentRequestController extends Controller
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
     * @param  \App\Http\Requests\StoreAgentRequestRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAgentRequestRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\AgentRequest  $agentRequest
     * @return \Illuminate\Http\Response
     */
    public function show(AgentRequest $agentRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AgentRequest  $agentRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(AgentRequest $agentRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateAgentRequestRequest  $request
     * @param  \App\Models\AgentRequest  $agentRequest
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAgentRequestRequest $request, AgentRequest $agentRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AgentRequest  $agentRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(AgentRequest $agentRequest)
    {
        //
    }
}
