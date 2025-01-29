<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Temp_Charges extends Model
{
    use HasFactory;
    protected $table ='temp__charges';
    protected $fillable = [
        'wb_account_detail_id',
        'amount',
        'type',
        'date_due',
        'status',
        'created_by'
    ];
    public function wb_account_details()
    {
        return $this->hasMany(wb_account_details::class);
    }
}
