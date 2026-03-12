<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pendingCustomerSavingMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'wb_account_id',
        'amount',
        'status',
        'mark_date',
    ];

    public function wb_account()
    {
        return $this->belongsTo(wb_account_details::class, 'wb_account_id', 'id');
    }
}
