<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class savings_product extends Model
{
    use HasFactory;
    public function frequency()
    {      
        return $this->belongsTo(frequency::class,'frequency_id','id');
    }
    public function editor()
    {      
        return $this->belongsTo(User::class,'modified_by','id');
    }
    public function author()
    {      
        return $this->belongsTo(User::class,'created_by','id');
    }
}
