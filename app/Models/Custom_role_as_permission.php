<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Permission;

class Custom_role_as_permission extends Model
{

    use HasFactory;
    protected $fillable = [
        'staffrole_id',
        'permission_id',
       
    ];
    public function staffrole()
    {
        return $this->belongsTo(Staffrole::class);
    }
    public function permission()
    {
        return $this->belongsTo(Custom_Permission::class);
    }
}
