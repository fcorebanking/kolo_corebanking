<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TalentCategory extends Model
{
    use HasFactory;
    protected $fillable =['name','icon','company_id'];
}
