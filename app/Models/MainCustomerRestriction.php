<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainCustomerRestriction extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','customer_permission_id', 'company_id', 'transaction_reference', 'session_id', 'remark','created_by'];
}
