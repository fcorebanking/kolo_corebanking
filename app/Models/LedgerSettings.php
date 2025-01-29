<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerSettings extends Model
{
    use HasFactory;
    public function author()
    {
        return $this->belongsTo(User::class,'created_by','id');
    }
    public function page()
    {
        return $this->belongsTo(transaction_types::class,'transaction_type_id','id');
    }
    public function ledgerCR()
    {
        return $this->belongsTo(LedgerAccountModel::class,'ledger_cr','id');
    }
    public function ledgerDR()
    {
        return $this->belongsTo(LedgerAccountModel::class,'ledger_dr','id');
    }
}
