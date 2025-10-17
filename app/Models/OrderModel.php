<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'ecomerce_product_model_id',
        'qrty',
        'status_id',
        'wb_account_detail_id',
        'transaction_id',
        'delivery_detail_model_id',
        'amount',
        'orderID',
        'total_amount',
        'created_by',
    ];

    public function DeliveryDetails()
    {
        return $this->belongsTo(DeliveryDetailModel::class, 'delivery_detail_model_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(EcomerceProductModel::class, 'ecomerce_product_model_id', 'id');
    }
}
