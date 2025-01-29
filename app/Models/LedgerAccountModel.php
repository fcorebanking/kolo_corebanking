<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerAccountModel extends Model
{
    use HasFactory;
    protected $table='ledger_accounts';

    public function author()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
}
