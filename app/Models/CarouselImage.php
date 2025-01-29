<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselImage extends Model
{
    use HasFactory;


    public function status()
    {
        return $this->hasOne(status::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, "created_by", "id");
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, "modified_by", "id");
    }
}
