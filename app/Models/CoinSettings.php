<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinSettings extends Model
{
    use HasFactory;
    protected $table='coin_settings';
    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
