<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCommentAttachment extends Model
{
    use HasFactory;
    public function Comment()
    {
        return $this->belongsTo(LoanComment::class);
    }
}
