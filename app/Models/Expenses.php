<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    use HasFactory;
    protected $fillable = ['ledger_cr','ledger_dr'];

    public function expenseType()
    {
        return $this->belongsTo(expenseType::class, 'expense_type_id', 'id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
