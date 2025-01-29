<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffpermissionTable extends Model
{
    use HasFactory;
    protected $table ='staffpermission';
    protected $fillable = [
        'staffrole_id',
        'user_id',
        'modified_by',
        'created_by',
    ];
    public function author()
    {      
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function editor()
    {      
        return $this->belongsTo(User::class,'modified_by','id');
    }
    public function user()
    {      
        return $this->belongsTo(User::class,'user_id','id');
    }
    public function staffrole()
    {      
        return $this->belongsTo(Staffrole::class,'staffrole_id','id');
    }

}
