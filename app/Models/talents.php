<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class talents extends Model
{
    use HasFactory;
    protected $fillable =['votes','user_id','category_id','avarter','bio_info','audio_link','video_link','company_id','name'];
    protected $hidden =['user_id'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function vote()
    {
        return $this->hasMany(vote::class, 'talent_id', 'id')->where('status','active');
    }
}
