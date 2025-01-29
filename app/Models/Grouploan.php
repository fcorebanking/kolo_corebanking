<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grouploan extends Model
{
    use HasFactory;
    public function leaderInfo()
    {
        return $this->belongsTo(User::class,'leader','id');
    }
    public function secretaryInfo()
    {
        return $this->belongsTo(User::class,'secretary','id');
    }
    public function managerInfo()
    {
        return $this->belongsTo(User::class,'manager_id','id');
    }
    public function AllLoans()
    {
        return $this->hasMany(loan::class,'group_id','id');
    }
    public function LoanActiveVolume()
    {
        return $this->hasMany(loan::class,'group_id','id')->where('loan_status_id',1);
    }
    public function LoanTotalVolume()
    {
        return $this->hasMany(loan::class,'group_id','id');
    }
    public function UserGroupLoan()
    {
        return $this->hasMany(UserGroupLoan::class,'group_id','id');
    }

}
