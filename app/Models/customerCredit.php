<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customerCredit extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id','name','method','url','authorization','body','callCost','status','access'
    ];
}
