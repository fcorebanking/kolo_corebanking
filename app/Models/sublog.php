<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sublog extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'transaction_id',
        'plan_id',
        'quantity',
        'start_date',
        'end_date',
      
    ];
    public function plan()
    {
        return $this->belongsTo(plans::class, 'plan_id', 'id');
    }
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
    public function TXNlog()
    {
        return $this->belongsTo(transactions::class, 'transaction_id', 'id');
    }
}
