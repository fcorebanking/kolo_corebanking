<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Models\company_status;
use App\Models\frequency;
use App\Models\Investmentbooking;
use App\Models\InvestmentProduct;
use App\Models\status;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    public function investmentVariable()
    {
        $data = [
            'product'      => InvestmentProduct::with('investmentfrequency')->where('company_id', auth()->user()->company_id)->get(),
            'freq'         => frequency::where('id', '>', 1)->get(),
            'status'       => company_status::all(),
            'userAccounts' => auth()->user()->wb_account_details,
        ];
        return json_encode($data);
    }

    public function allInvestmentVariable()
    {
        $investmentProduct = InvestmentProduct::with('investmentfrequency')->where('company_id', auth()->user()->company_id)->get();
        $frequency         = frequency::where('id', '>', 1)->get();
        $company           = company_status::all();

        $data = [
            'product'      => $investmentProduct,
            'freq'         => $frequency,
            'status'       => $company,
            'userAccounts' => auth()->user()->wb_account_details,
        ];
        //  return json_encode($data);

        return response()->json(['message' => 'Data Found!', 'data' => $data, 'status' => 1], 200);
    }
    public function processInvestment(Request $request)
    {
        $calculate = (app('App\Http\Controllers\InvestmentController')->calculateinvestment($request));
        return $calculate;
    }

public function bookinvestment(Request $request)
{
    $traceId = (string) \Illuminate\Support\Str::uuid();
    $authUser = auth()->user();

    logInfo([
        'trace_id' => $traceId,
        'stage' => 'bookinvestment_wrapper_start',
        'authenticated' => (bool) $authUser,
        'auth_user_id' => $authUser?->id,
        'auth_company_id' => $authUser?->company_id,
        'auth_uuid' => $authUser?->uuid,
        'incoming_amount' => $request->amount,
        'incoming_account_type' => $request->account_type,
        'incoming_investmentproduct' => $request->investmentproduct,
        'incoming_status' => $request->status,
        'incoming_platform' => $request->platform,
    ], 'INVESTMENT DEBUG WRAPPER');

    if (! $authUser) {
        logInfo([
            'trace_id' => $traceId,
            'stage' => 'wrapper_auth_failed',
        ], 'INVESTMENT DEBUG WRAPPER ERROR');

        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.',
            'trace_id' => $traceId,
        ], 401);
    }

    $request->merge([
        'status' => 1,
        'uuid' => $authUser->uuid,
        'platform' => 'mobile',
        'trace_id' => $traceId,
    ]);

    logInfo([
        'trace_id' => $traceId,
        'stage' => 'bookinvestment_wrapper_after_merge',
        'uuid' => $request->uuid,
        'status' => $request->status,
        'platform' => $request->platform,
        'account_type' => $request->account_type,
        'amount' => $request->amount,
        'investmentproduct' => $request->investmentproduct,
    ], 'INVESTMENT DEBUG WRAPPER MERGED REQUEST');

    try {
        $send = app(\App\Http\Controllers\InvestmentController::class)
            ->makeinvestment($request);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'bookinvestment_wrapper_response',
            'response' => $send instanceof \Illuminate\Http\JsonResponse
                ? $send->getData(true)
                : $send,
        ], 'INVESTMENT DEBUG WRAPPER RESPONSE');

        return $send;

    } catch (\Throwable $e) {
        logInfo([
            'trace_id' => $traceId,
            'stage' => 'bookinvestment_wrapper_exception',
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 'INVESTMENT DEBUG WRAPPER EXCEPTION');

        return response()->json([
            'status' => false,
            'message' => 'Error processing investment request.',
            'trace_id' => $traceId,
        ], 500);
    }
}


    public function viewInvestment()
    {
        $Investmentbooking = Investmentbooking::with('plan') //->join('wb_account_details','wb_account_details.id','=','investmentbooking.wb_accountdetails_id')
            ->whereHas('wb_account_details', function ($q) {
                $q->where('wb_account_details.user_id', auth()->user()->id);
            })
            ->get();
        return json_encode($Investmentbooking);
        // return response()->json($Investmentbooking,200);

    }

    public function viewInvestmentUpdate()
    {
        $Investmentbooking = Investmentbooking::with('plan') //->join('wb_account_details','wb_account_details.id','=','investmentbooking.wb_accountdetails_id')
            ->whereHas('wb_account_details', function ($q) {
                $q->where('wb_account_details.user_id', auth()->user()->id);
            })
            ->get();
        //  return json_encode($Investmentbooking);
        return response()->json(['message' => 'Data Found!', 'data' => $Investmentbooking, 'status' => 1], 200);

    }
    public function TerminateInvestment(Request $request)
    {
        //ajax_investmentstatus
        $send = (app('App\Http\Controllers\InvestmentController')->ajax_investmentstatus($request));
        return $send;
    }
    public function ajax_investmentstatus($id)
    {
        $terminationoption = auth()->user()->company->setting->investment_termination_control;
        $runninginvestment = Investmentbooking::where('id', $id)->first();
        $cashleft          = 0;

        // wb_account_details
        //return $runninginvestment->wb_account_details->id;
        if ($runninginvestment->wb_account_details->id != $runninginvestment->wb_accountdetails_id) {
            return response(json_encode(['status' => 0, 'message' => 'Access Denied!']));

        }
        $termination_charge = $runninginvestment->plan->termination_charge;
        $amount             = $runninginvestment->amount;
        $PI                 = $amount + $runninginvestment->wallet;
        $percent_charge     = 0;
        if ($terminationoption == 1) {
            if ($runninginvestment->wallet > 1) {
                $percent_charge = ($termination_charge / 100) * $runninginvestment->wallet;
                if ($runninginvestment->wallet > $percent_charge) {
                    $cashleft = ($runninginvestment->wallet - $percent_charge) + $amount;
                } else {
                    // return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
                }
            } else {
                // return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later (Low interest)']));
            }
        } elseif ($terminationoption == 2) {
            $percent_charge = ($termination_charge / 100) * $amount;
            $cashleft       = ($amount - $percent_charge) + $runninginvestment->wallet;
        } elseif ($terminationoption == 3) {
            $percent_charge = ($termination_charge / 100) * $PI;
            if ($PI > $percent_charge) {
                $cashleft = $PI - $percent_charge;
            } else {
                return response(json_encode(['status' => 0, 'message' => 'You are not eligeble to withdraw at this time try again later']));
            }
        } elseif ($terminationoption == 4) {
            $cashleft = $amount;
        } else {
            return response(json_encode(['status' => 0, 'message' => 'Error occured']));
        }
        $wb = wb_account_details::where('id', $runninginvestment->wb_accountdetails_id)->first();
        if ($wb->user->company_id == auth()->user()->company_id) {
            if ($runninginvestment->status == 1) {
                $userwallet    = user_wallet::where('wb_account_detail_id', $runninginvestment->wb_accountdetails_id)->first();
                $balanceBefore = $userwallet->balance;
                $balanceAfter  = $balanceBefore + $cashleft;
                $userwallet->update(['balance' => $balanceAfter]);
                $desc = "Investment Termination";
                $date = date('Y-m-d H:i:s');
                $runninginvestment->update(['status' => 2]); //Terminated
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(21, auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $data = [
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $amount,
                    "description"            => $desc,
                    "withdrawal_charge"      => $percent_charge,
                    "account_id"             => $runninginvestment->wb_accountdetails_id,
                    "branch_id"              => $wb->user->branch_id,
                    "transaction_reference"  => generate_txnID(),
                    "transaction_type_id"    => 21, //investment plan
                    "credit_debit_type_id"   => 1,  //debit 2
                    "transaction_remarks_id" => 2,  //success 2
                    "balance_after"          => $balanceAfter,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ];
                transactions::create($data);
                sendSMS($runninginvestment->wb_accountdetails_id, "Credit Amt:$cashleft Charges:$percent_charge Desc:$desc Avail Bal:$userwallet->balance Date:$date", false, true, 'Investment Termination');
                return response(json_encode(['status' => 1, 'message' => "Investment Terminated!"]));
            } else {
                return response(json_encode(['status' => 0, 'message' => 'Investment no longer Active']));
            }
        } else {
            return response(json_encode(['status' => 0, 'message' => 'User restricted ' . $wb->user->id . '==' . auth()->user()->company_id . '==' . $id]));
        }
        return response(json_encode(['status' => 0, 'message' => 'An Error occured']));
    }
}
