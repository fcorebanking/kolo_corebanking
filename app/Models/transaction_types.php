<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transaction_types extends Model
{
    use HasFactory;

    public function transaction()
    {
        return $this->hasMany(transactions::class);
    }
}
