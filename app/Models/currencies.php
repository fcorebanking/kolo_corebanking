<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class currencies extends Model
{
    use HasFactory;

    public function company()
    {
        return $this->hasMany(Company::class);
    }

    public function branch()
    {
        return $this->belongsTo(branch::class);
    }

    public function country()
    {
        return $this->belongsTo(Contries::class);
    }
}
