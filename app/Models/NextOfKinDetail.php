<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NextOfKinDetail extends Model
{
    use HasFactory;

    protected $guarded = [];
      protected $fillable = [
        'user_id',
        'nok_name',
        'nok_phone',
        'nok_address',
    ];
}
