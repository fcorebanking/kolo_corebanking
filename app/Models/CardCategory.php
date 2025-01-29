<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardCategory extends Model
{
    use HasFactory;

    public function companyCard()
    {
        return $this->belongsTo(CompanyCard::class, 'card_category_id', 'id');
    }
}
