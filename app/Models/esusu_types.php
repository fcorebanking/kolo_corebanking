<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class esusu_types extends Model
{
    use HasFactory;
    protected $table= 'esusu_types';
    protected $fillable = [
        'company_id','esusu_type','min_rate','max_rate','circle_charge','duration','overdraft_charge','status'
    ];
}
