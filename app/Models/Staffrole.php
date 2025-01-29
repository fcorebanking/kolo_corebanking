<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staffrole extends Model
{
    use HasFactory;
    protected $table ='staffrole';
    protected $fillable = [
        'name',
        'company_id',
        'created_by',
    ];
    public function company()
    {      
        return $this->belongsTo(Company::class,'company_id','id');
    }
    public function author()
    {      
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function has_permissions()
    {      
        return $this->hasMany(Custom_role_as_permission::class,'staffrole_id','id');
    }
}
