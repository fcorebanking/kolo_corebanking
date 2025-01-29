<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPayRollLog extends Model
{
    use HasFactory;

    protected $fillable = [
        "transaction_reference",
        "staff_id",
        "basic_salary",
        "date_of_payment",
        "house_allowance",
        "medical_allowance",
        "special_allowance",
        "overtime_allowance",
        "transport_allowance",
        "paid_leave",
        "pension_deduction",
        "provident_fund_allowance",
        "unpaid_leave_deduction",
        "loan_deduction",
        "other_allowances",
        "tax_deductions",
        "provident_fund_deduction",
        "other_deductions",
        "gross_salary",
        "total_deduction",
        "bank_id",
        "account_number",
        "account_name",
        "payment_mode",
        "net_salary",
        "status_id"
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(status::class, 'status_id', 'id');
    }
}
