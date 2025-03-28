<?php
namespace App\Models;

use App\Http\Controllers\ThirdParty\SafehavenController;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCard extends Model implements MustVerifyEmail
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    protected $hidden = ['user_id'];

    public function owner()
    {
        return $this->belongsTo(User::class);
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

    public function markEmailAsVerified()
    {
    }

    public function sendEmailVerificationNotification()
    {
    }

    public function hasVerifiedEmail()
    {
    }

    public function getEmailForVerification()
    {
    }

    protected $appends = ['card_token'];

    public function getFundedAttribute()
    {

        $safehavenController = new SafehavenController();

        $getCardToken = $safehavenController->getCardToken($this->card_reference);

        if (isset($getCardToken['data']['token']) && $getCardToken['data']['token'] != null) {
            return $getCardToken['data']['token'];
        } else {
            return null;
        }

    }
}
