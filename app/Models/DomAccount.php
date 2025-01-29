<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomAccount extends Model
{
    use HasFactory;
    //wb_account_detail_id
    protected $fillable = [
        'wb_account_detail_id',  
        'created_by',  
        'status',  
        'uuid',
       ];
   
    public function wb_account_details()
    {
        return $this->belongsTo(wb_account_details::class);
    }
    public function author()
    {
        return $this->belongsTo(user::class);
    }
}
