<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customersavings extends Model
{
    use HasFactory;
    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function agent()
    {
        return $this->belongsTo(User::class,'agent_id','id');
    }

    public function payments()
    {
        return $this->hasMany(Pendingcustomersaving::class, 'customersaving_id', 'id');
    }
    public function pastPayment()
    {
        return $this->hasMany(Pendingcustomersaving::class, 'customersaving_id', 'id')->whereDate('paymentDate', '<=', date('Y-m-d'))
        ->sum('amount');
    }
}
