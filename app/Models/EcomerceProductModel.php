<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomerceProductModel extends Model
{
    use HasFactory;
    public function author()
    {
        return $this->belongsTo(user::class,'created_by','id');
    }
    public function status()
    {
        return $this->belongsTo(company_status::class,'status_id','id');
    }
     public function category()
    {
        return $this->belongsTo(EcommerceCategory::class,'ecommerce_categorie_id','id');
    }

}
