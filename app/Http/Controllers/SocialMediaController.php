<?php

namespace App\Http\Controllers;

use App\Models\Social_media;
use App\Http\Requests\StoreSocial_mediaRequest;
use App\Http\Requests\UpdateSocial_mediaRequest;

class SocialMediaController extends Controller
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
     * @param  \App\Http\Requests\StoreSocial_mediaRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSocial_mediaRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Social_media  $social_media
     * @return \Illuminate\Http\Response
     */
    public function show(Social_media $social_media)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Social_media  $social_media
     * @return \Illuminate\Http\Response
     */
    public function edit(Social_media $social_media)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateSocial_mediaRequest  $request
     * @param  \App\Models\Social_media  $social_media
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSocial_mediaRequest $request, Social_media $social_media)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Social_media  $social_media
     * @return \Illuminate\Http\Response
     */
    public function destroy(Social_media $social_media)
    {
        //
    }
}
