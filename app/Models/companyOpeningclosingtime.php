<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class companyOpeningclosingtime extends Model
{
    use HasFactory;
    public function status()
    {      
        return $this->belongsTo(company_status::class,'status_id','id');
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
