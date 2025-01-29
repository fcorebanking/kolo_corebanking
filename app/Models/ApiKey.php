<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'api_key'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, "company_id", "id");
    }
}
