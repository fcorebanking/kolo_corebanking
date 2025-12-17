<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'branch_id',
        'account_id',
        'transaction_type_id',
        'amount',
        'summary_date',
    ];
}
