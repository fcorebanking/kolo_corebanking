<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPermission extends Model
{
    use HasFactory;
    

    public function customers()
    {
        return $this->belongsToMany(User::class);
    }
}
