<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;
    public function bank()
    {
        return $this->belongsTo(banks::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
