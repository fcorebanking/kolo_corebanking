<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'subject',
        'message',
        'num_page',
        'amount',
        'status',
        'created_by'
    ];
    public function User()
    {
        return $this->belongsTo(user::class);
    }
    public function CreatedBy()
    {
        return $this->belongsTo(user::class,'created_by','id');
    }
}
