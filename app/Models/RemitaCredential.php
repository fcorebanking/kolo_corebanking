<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RemitaCredential extends Model
{
    use HasFactory;
    protected $fillable = [
        'apiKey','machant','apiToken','ownership','company_id','iswba'
    ];
}
