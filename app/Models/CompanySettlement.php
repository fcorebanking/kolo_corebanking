<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySettlement extends Model
{
    use HasFactory;
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function bank()
    {
        return $this->belongsTo(banks::class, 'bank_id', 'id');
    }
}
