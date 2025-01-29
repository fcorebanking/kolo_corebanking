<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CooperateCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_name',
        'tin',
        'cac_reg_no',
        'created_by'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }
}
