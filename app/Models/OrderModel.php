<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    public function DeliveryDetails()
    {
        return $this->belongsTo(DeliveryDetailModel::class,'delivery_detail_model_id','id');
    }
    public function product()
    {
        return $this->belongsTo(EcomerceProductModel::class,'ecomerce_product_model_id','id');
    }
}
