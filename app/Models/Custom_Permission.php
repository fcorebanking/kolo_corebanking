<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Custom_Permission extends Model
{
    use HasFactory;
    protected $table ='permissions';
}
