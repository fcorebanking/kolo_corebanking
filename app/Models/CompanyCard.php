<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyCard extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'company_id',
        'card_category_id',
        'card_type_id',
        'card_name',
        'card_number',
        'masked_pan',
        'card_reference',
        'currency_id',
        'exp_date',
        'cvv',
        'created_by',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function currency()
    {
        return $this->belongsTo(currencies::class, 'currency_id', 'id');
    }
    public function cardType()
    {
        return $this->hasOne(CardType::class);
    }
    public function cardCategory()
    {
        return $this->hasOne(CardCategory::class);
    }
}
