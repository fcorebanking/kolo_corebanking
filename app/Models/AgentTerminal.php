<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTerminal extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "terminal_code"
    ]; 

    
    public function agent(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
