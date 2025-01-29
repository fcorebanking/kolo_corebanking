<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyWalletTXN extends Model
{
    use HasFactory;
    protected $fillable =['company_id', 'staff_id', 'amount','branch_id','type'];
}
