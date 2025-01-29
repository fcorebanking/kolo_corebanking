<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyValut extends Model
{
    use HasFactory;
    protected $fillable=['accountno','company_id', 'vault', 'value' ,'deleteable','created_by'];
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
