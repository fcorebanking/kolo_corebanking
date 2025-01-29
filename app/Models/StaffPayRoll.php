<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffPayRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        "staff_id",
        "employment_type_id",
        "basic_salary",
        "date_of_payment",
        "house_allowance",
        "medical_allowance",
        "special_allowance",
        "pension_deduction",
        "provident_fund_allowance",
        "other_allowances",
        "tax_deductions",
        "provident_fund_deduction",
        "other_deductions",
        "gross_salary",
        "total_deduction",
        "bank_id",
        "account_number",
        "account_name",
        "net_salary",
        "overtime_allowance",
        "transport_allowance",
        "paid_leave",
        "unpaid_leave_deduction",
        "loan_deduction"
    ];


    public function staff()
    {
        return $this->belongsTo(User::class, "staff_id", "id");
    }

    public function bank()
    {
        return $this->hasOne(banks::class, "id", "bank_id");
    }

    public function staffType()
    {
        return $this->belongsTo(EmployeeType::class, "employee_type_id", "id");
    }
}
