<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class guarantor extends Model
{
    use HasFactory;
    protected $fillable =['signature','passport'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
