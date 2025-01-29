<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendingcustomersaving extends Model
{
    use HasFactory;
    protected $fillable = ['status'];
    public function customersaving()
    {
        return $this->belongsTo(Customersavings::class,'customersaving_id','id');
    }
}
