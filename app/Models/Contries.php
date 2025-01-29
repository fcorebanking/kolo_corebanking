<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contries extends Model
{
    use HasFactory;
    protected $table = 'contries';

    public function currency()
    {
        return $this->hasOne(currencies::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class);
    }
}
