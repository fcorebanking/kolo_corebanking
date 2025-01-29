<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class smsapi extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'phone_variable',
        'name',
        'message_variable',
        'api_url',
        'access_setup',
        'request_method_id',
        'authorization',
        'content_type',
        'smscharge'
   
       ];
    public function requestMethod()
    {
        return $this->belongsTo(request_methods::class,'request_method_id','id');
    }
}
