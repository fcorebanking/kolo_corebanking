<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tempuser extends Model
{
    use HasFactory;
    protected $fillable =[
        'first_name',
        'last_name',
        'email',
        'phone',
        'dob',
        'address',
        'sex',
      'bank_code',
        'username',
      'old_wba_user',
      'acc_ref',
        'bvn',
        'nin',
        'ac_no',
     'loanBalance',
        'reg_date',
        'balance',
        'company_id',

    ];
}
