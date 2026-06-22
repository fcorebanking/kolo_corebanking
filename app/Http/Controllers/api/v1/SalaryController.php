<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\banks;
use App\Models\branch;
use App\Models\EmploymentType;
use App\Models\StaffPayRoll;
use App\Models\StaffPayRollLog;
use App\Models\Staffrole;
use App\Models\status;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalaryController extends Controller
{
    public function getStaffPayrolDetails()
    {
        $id           = auth('api')->user();
        $staffPayroll = StaffPayRoll::with('bank')->where('staff_id', $id)->first();
        if ($staffPayroll) {

            return response()->json(["status" => 1, "data" => $staffPayroll]);
        } else {
            return response()->json(["status" => 0, 'message' => "Operation failed: Some user are register to this branch!"]);
        }
    }

    public function staffPayroll()
    {

        $branch_subscription_status_id = 14;
       

        $data = [

            'employment_types' => EmploymentType::all(),
            'banks'            => banks::all(),
            'roles'            => Staffrole::where(['company_id' => auth('api')->user()->company_id])->get(),
            'branch'           => branch::has('author')->where('company_id', auth('api')->user()->company_id)->where('status_id', '!=', $branch_subscription_status_id)->get(),
        ];

        return response()->json(["status" => 1, "data" => $data ]);
    }
    public function ajax_generate_payroll_report(Request $request)
    {
        if (! cando('manage-payroll|root')) {
            return abort(403);
        }

        \logInfo("Testing", "Generate user details");
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 25);
        $order       = $request->query('order', [1, 'desc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'users.created_at',
        ];
        $query = StaffPayRollLog::join('users', 'users.id', '=', 'staff_pay_roll_logs.staff_id')
            ->join('statuses', 'statuses.id', '=', 'staff_pay_roll_logs.status_id')
            ->select('staff_pay_roll_logs.*', 'statuses.name as status', 'users.first_name', 'users.last_name', 'users.branch_id', DB::raw('staff_pay_roll_logs.created_at as pay_created_at'));
        $query = $query->where(['users.user_type_id' => '2', 'users.company_id' => auth()->user()->company_id]);

        $recordsTotal = $query->count();
        $recordsSql   = $query->toSql();
        logInfo($recordsTotal, "Total Salary logs");
        logInfo($recordsSql, "Salary sql logs");

        if ($request->from != null && $request->to != null) {
            $query = $query->whereDate('staff_pay_roll_logs.created_at', '<=', add_day_to_date(0, $request->to))
                ->whereDate('staff_pay_roll_logs.created_at', '>=', add_day_to_date(0, $request->from));
        }
        if ($request->branch != 0) {
            $query = $query->where('users.branch_id', $request->branch);
        }
        if (! empty($filter)) {
            $query->where('users.first_name', 'like', '%' . $filter . '%');
        }
        $recordsTotal = $query->count();

        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info = $query->get();
        $sn   = 0;

        $deduction  = 0;
        $net        = 0;
        $gross      = 0;
        $difference = 0;

        \logInfo(\count($info), "We are logged in");
        foreach ($info as $infoData) {
            $sn++;
            $btnlink    = $infoData->id;
            $deduction += $infoData->total_deduction;
            $net       += $infoData->net_salary;
            $gross     += $infoData->gross_salary;

            $statusText = $infoData->status_id == 3 ? 'success' : 'warning';

            $statusBadge = "<div class='badge badge-$statusText'>$infoData->status</div>";

            $json["caption"] = "We are going home";

            $json['data'][] = [
                $sn,
                date("Y-m-d", strtotime($infoData->pay_created_at)),
                $infoData->transaction_reference,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->net_salary,
                $infoData->account_name == null ? "Internal Account" : "Bank Transfer",
                $infoData->payment_mode == 0 ? "Automatic" : "Manual",
                $statusBadge,

                // "<a class='btn btn-info' href='#'>View Payslip</a>",
                $infoData->accountsavingsp,
            ];
        }
        return response()->json(["status" => 1, "data" => $json]);

    }

    public function ajax_setup_payroll($company_name, Request $request)
    {

        \logInfo($request->all(), "Payroll setup request");
        $token = csrf_token();

        $validator = Validator::make($request->all(), [
            'employment_type'             => 'required|min:0',
            'basic_salary'                => 'required|min:0',
            'day_of_payment'              => 'required',
            'house_allowance'             => 'required|min:0',
            'medical_allowance'           => 'required|min:0',
            'special_allowance'           => 'required|min:0',
            'provident_fund_contribution' => 'required|min:0',
            'other_allowances'            => 'required|min:0',
            'tax_deductions'              => 'required|min:0',
            'provident_fund_deduction'    => 'required|min:0',
            'other_deductions'            => 'required|min:0',
            'gross_salary'                => 'required|min:0',
            'staff_id'                    => 'required|min:0',
            'total_deductions'            => 'required|min:0',
            'net_salary'                  => 'required|min:0',
            'payment_mode'                => 'required',
        ]);

        if ($validator->passes()) {
            $payroll = StaffPayRoll::where(['staff_id' => $request->staff_id])->first();

            $bank = banks::where('bank_code', $request->bank)->first();

            if (! $payroll) {

                StaffPayRoll::create([
                    "staff_id"                 => $request->staff_id,
                    "employment_type_id"       => $request->employment_type,
                    "basic_salary"             => $request->basic_salary,
                    "day_of_payment"           => $request->day_of_payment,
                    "house_allowance"          => $request->house_allowance,
                    "medical_allowance"        => $request->medical_allowance,
                    "special_allowance"        => $request->special_allowance,
                    "pension_deduction"        => $request->pension_deduction,
                    "provident_fund_allowance" => $request->provident_fund_contribution,
                    "other_allowances"         => $request->other_allowances,
                    "tax_deductions"           => $request->tax_deductions,
                    "provident_fund_deduction" => $request->provident_fund_deduction,
                    "other_deductions"         => $request->other_deductions,
                    "gross_salary"             => $request->gross_salary,
                    "total_deduction"          => $request->total_deductions,
                    "net_salary"               => $request->net_salary,
                    "bank_id"                  => $bank ? $bank->id : "",
                    "account_number"           => $request->account_number,
                    "account_name"             => $request->account_name,
                    "payment_mode"             => $request->paymentMethod,
                    "overtime_allowance"       => $request->overtime,
                    "transport_allowance"      => $request->transport_allowance,
                    "wallet_account_number"    => $request->wallet_account_number,
                    "wallet_account_name"      => $request->wallet_account_name,
                    "paid_leave"               => $request->paid_leave,
                    "unpaid_leave_deduction"   => $request->unpaid_leave,
                    "loan_deduction"           => $request->loan_deduction,

                ]);
            } else {
                $payroll->employment_type_id       = $request->employment_type;
                $payroll->basic_salary             = $request->basic_salary;
                $payroll->day_of_payment           = $request->day_of_payment;
                $payroll->house_allowance          = $request->house_allowance;
                $payroll->medical_allowance        = $request->medical_allowance;
                $payroll->special_allowance        = $request->special_allowance;
                $payroll->pension_deduction        = $request->pension_deduction;
                $payroll->provident_fund_allowance = $request->provident_fund_contribution;
                $payroll->other_allowances         = $request->other_allowances;
                $payroll->tax_deductions           = $request->tax_deductions;
                $payroll->provident_fund_deduction = $request->provident_fund_deduction;
                $payroll->other_deductions         = $request->other_deductions;
                $payroll->gross_salary             = $request->gross_salary;
                $payroll->total_deduction          = $request->total_deductions;
                $payroll->net_salary               = $request->net_salary;
                $payroll->bank_id                  = $bank ? $bank->id : "";
                $payroll->account_number           = $request->account_number;
                $payroll->payment_method           = $request->paymentMethod;
                $payroll->account_name             = $request->account_name;
                $payroll->payment_mode             = $request->payment_mode;
                $payroll->overtime_allowance       = $request->overtime;
                $payroll->wallet_account_number    = $request->wallet_account_number;
                $payroll->wallet_account_name      = $request->wallet_account_name;
                $payroll->transport_allowance      = $request->transport_allowance;
                $payroll->paid_leave               = $request->paid_leave;
                $payroll->unpaid_leave_deduction   = $request->unpaid_leave;
                $payroll->loan_deduction           = $request->loan_deduction;
                $payroll->save();
            }

            User::where('id', $request->staff_id)->update(['payroll_setup' => 1]);

            return response()->json(["status" => 1, "token" => $token, 'message' => "Staff Payroll setup"]);
        } else {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {

                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message;
                }
            }
            // echo $request->email;
            // var_dump( $errors);
            return response()->json(["status" => 0, "token" => $token, 'message' => "$errors"]);
        }
    }

}
