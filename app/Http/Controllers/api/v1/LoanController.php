<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListLoanResource;
use App\Models\ApploanSettings;
use App\Models\loan;
use App\Models\LoanRepayment;
use App\Models\loan_period;
use App\Models\transactions;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
{
    public function postDataProcessing(Request $request)
    {
        $user = (auth()->check()) ? auth()->user() : User::where('id', $request->userid)->first();
        logInfo(json_encode($user), "loan user");
        $officer     = User::where('id', $user->account_officer_id)->first();
        $eligibility = $this->eligibility();
        if ($eligibility->status == 0) {
            $request->status  = 0;
            $request->message = $eligibility->message;
            return ($eligibility);
        }
        $amount = $request->amount;
        $minamt = $eligibility->minamt;
        $maxamt = $eligibility->maxamt;
        if ($amount < $minamt || $amount > $maxamt) {
            $request->status  = 0;
            $request->message = "Amount should be greater than equal to $minamt and less than equal to $maxamt";
            return $request;
        }
        $request->customer                       = [$user->uuid];
        $request->loan_products                  = Crypt::encryptString($eligibility->loan_product_id);
        $request->loan_type                      = $eligibility->loan_type;
        $request->branch                         = Crypt::encryptString($user->branch_id);
        $request->officer                        = $officer->uuid;
        $request->loanFeeMethod                  = 1;
        $request->loanFee                        = 0;
        $request->managementFee                  = 0;
        $request->managementFeeMethod            = 1;
        $request->period                         = Crypt::encryptString($eligibility->repayment);
        $request->Duration                       = $eligibility->duration;
        $request->principal                      = $amount;
        $request->keep_as_past_onMissed_onExtend = 1;
        $request->guarantorOption                = 2;
        $request->auto_pay                       = 1;
        $request->ExtendDuration                 = 0;
        $request->missedFee                      = 0;
        $request->DurationPeriod                 = $eligibility->durationPeriod;
        $request->status                         = 1;
        logInfo(json_encode($request), "loan user reqest");
        return $request;
    }
public function processLoan(Request $request)
{
    $traceId = (string) \Illuminate\Support\Str::uuid();
    $request->merge(['trace_id' => $traceId]);

    logInfo([
        'trace_id' => $traceId,
        'stage' => 'API PROCESS LOAN START',
        'authenticated' => auth()->check(),
        'auth_user_id' => auth()->id(),
        'auth_company_id' => auth()->user()->company_id ?? null,
        'request_summary' => [
    'customer_type' => gettype($request->customer),
    'customer_count' => is_array($request->customer)
        ? count($request->customer)
        : ($request->customer ? 1 : 0),

    'amount' => $request->amount,

    'loan_products_present' => !empty($request->loan_products),
    'loan_type' => $request->loan_type,
    'branch_present' => !empty($request->branch),
    'officer' => $request->officer,
    'period_present' => !empty($request->period),
    'duration' => $request->Duration,
    'duration_period' => $request->DurationPeriod,
    'principal' => $request->principal,
],
    ], 'API LOAN DEBUG START');

    try {
        // postDataProcessing() is outside the supplied LoanController.
        // This log tells us whether the request is being denied before
        // LoanController::doProcessing() is even reached.
        $newrequest = $this->postDataProcessing($request);

        $newRequestStatus = is_object($newrequest)
            ? ($newrequest->status ?? null)
            : (is_array($newrequest) ? ($newrequest['status'] ?? null) : null);

        $newRequestMessage = is_object($newrequest)
            ? ($newrequest->message ?? null)
            : (is_array($newrequest) ? ($newrequest['message'] ?? null) : null);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'POST DATA PROCESSING RESULT',
            'result_class' => is_object($newrequest) ? get_class($newrequest) : gettype($newrequest),
            'status' => $newRequestStatus,
            'message' => $newRequestMessage,
        ], 'API LOAN DEBUG POST PROCESSING');

        if ((string) $newRequestStatus !== '1') {
            logInfo([
                'trace_id' => $traceId,
                'stage' => 'STOPPED BEFORE LOAN CONTROLLER',
                'status' => $newRequestStatus,
                'message' => $newRequestMessage,
            ], 'API LOAN DEBUG FAILURE');

            return response()->json($newrequest);
        }

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'CALLING LOAN CONTROLLER DO PROCESSING',
            'loan_controller' => 'App\\Http\\Controllers\\LoanController',
        ], 'API LOAN DEBUG ROUTING');

        $loanController = app('App\\Http\\Controllers\\LoanController');
        $rawResult = $loanController->doProcessing($newrequest);

        $decodedResult = is_string($rawResult)
            ? json_decode($rawResult, true)
            : (is_array($rawResult) ? $rawResult : null);

        logInfo([
            'trace_id' => $traceId,
            'stage' => 'LOAN CONTROLLER RESPONSE',
            'response' => $decodedResult ?? $rawResult,
        ], 'API LOAN DEBUG RESPONSE');

        if (is_string($rawResult)) {
            $decoded = json_decode($rawResult);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $rawResult;

    } catch (\Throwable $e) {
        logInfo([
            'trace_id' => $traceId,
            'stage' => 'API LOAN EXCEPTION',
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 'API LOAN DEBUG EXCEPTION');

        return response()->json([
            'status' => 0,
            'message' => 'Error processing loan request.',
        ], 500);
    }
}

    public function loanData()
    {
        $data = [
            'period'         => loan_period::all(),
            'loanType'       => [
                ['id' => 1, 'name' => 'Flat'],
                ['id' => 2, 'name' => 'Reducing Balance'],
            ],
            'durationPeriod' => [
                ['id' => 'weeks', 'name' => 'Weeks'],
                ['id' => 'months', 'name' => 'Months'],
                ['id' => 'years', 'name' => 'Years'],
            ],
        ];
    }
    public function eligibility()
    {
        $user = auth()->user();
        logInfo("{$user->username}", "Eligibility on mobile");
        $wb_account_details = $user->wb_account_details;
        $transactions_sum   = 0;
        $balance            = 0;
        foreach ($wb_account_details as $walletCheck) {
            $balance          = $walletCheck->user_wallet->balance;
            $loanCount        = loan::where(['user_id' => $user->id, 'loan_status_id' => 1])->count();
            $transactions_sum = transactions::where('account_id', $walletCheck->id)->sum('amount');

            logInfo("{$user->username}: $transactions_sum", "transactions_sum");
            logInfo("{$user->username}:  $balance", " balance");
            $appdata = ApploanSettings::where(['company_id' => $user->company_id])->where('transactionMin', '<=', $transactions_sum)->where('transactionMax', '>=', $transactions_sum)->where('minWalletBalance', '<=', $balance)->where('maxWalletBalance', '>=', $balance)->where('NumOngoinAllowed', '>', $loanCount)->first();
            $result  = json_encode($appdata);
            logInfo("$result", " App Data");
            if ($appdata) {
                $appdata->message       = 'Success';
                $appdata->status        = 1;
                $appdata->productName   = $appdata->LoanProduct->product_name;
                $appdata->productRate   = $appdata->LoanProduct->interest_rate;
                $appdata->repaymenttext = $appdata->PeriodID->name;
                $appdata->loantypeapp   = ($appdata->loan_type == 1) ? 'Flat' : 'Reducing';
                return $appdata;
                break;
            }
        }
        $req = [
            'status'  => 0,
            'message' => 'Unfortunately, you are not eligible for a loan at the moment.',
        ];

        return $req;
    }
    public function PostBooklLoan(Request $request)
    {
        $user = null;

        if (! auth()->check()) {
            if (! $request->userid) {
                return response()->json(["status" => 0, "message" => 'Error verifying account!']);
            }

            $user = User::where('id', $request->userid)->first();
            auth()->login($user);
        } else {
            $user = auth()->user();
        }
        if ($user == null) {
            return response()->json(["status" => 0, "message" => 'Error!']);
        }

        $loanCount = loan::where(['user_id' => $user->id, 'loan_status_id' => 1])->orderBy('id', 'desc')->count();

        if (! $request->userid) {
            $eligi = json_decode($this->eligibility());
            if ($eligi->status == 0) {
                return response()->json(["status" => 0, "message" => $eligi->message]);
            }
            if ($loanCount > $eligi->NumOngoinAllowed) {
                return response()->json(["status" => 0, "message" => 'You have exceed maximun number of time to book for loan!']);
            }
        }

        $loanCountPending = loan::where(['user_id' => $user->id, 'loan_status_id' => 3])->orderBy('id', 'desc')->count();

        if ($loanCountPending) {
            return response()->json(["status" => 0, "message" => 'Your pending loan need to be approved before booking for another!'], 400);
        }
        $ValidateRequest = [];
        try {
            $request = $this->postDataProcessing($request);

            if (! $request->status) {
                return response()->json(['status' => 0, 'message' => $request->message]);
            }
            // return response()->json($request);
            return DB::transaction(function () use ($request) {
                $ValidateRequest = json_decode(app('App\Http\Controllers\LoanController')->doProcessing($request));
                if ($ValidateRequest->status) {
                    $interestRate                   = $ValidateRequest->interestRate;
                    $duration                       = $ValidateRequest->duration;
                    $EachRepayment                  = $ValidateRequest->EachRepayment;
                    $loanFee                        = $ValidateRequest->loanFee;
                    $managementFee                  = $ValidateRequest->managementFee;
                    $loanFeeMethod                  = $request->loanFeeMethod;
                    $managementFeeMethod            = $request->managementFeeMethod;
                    $auto_pay                       = $request->auto_pay;
                    $missedFee                      = $request->missedFee;
                    $ExtendDuration                 = $request->ExtendDuration;
                    $keep_as_past_onMissed_onExtend = $request->keep_as_past_onMissed_onExtend;
                    $principal                      = $ValidateRequest->principal;
                    $LoanType                       = $ValidateRequest->LoanType;
                    $borrower                       = $ValidateRequest->borrower;
                    $productid                      = Crypt::decryptString($request->loan_products);
                    $branchid                       = Crypt::decryptString($request->branch);
                    $period                         = Crypt::decryptString($request->period);
                    $officer                        = $request->officer;
                    $loanOfficer                    = User::where('uuid', $officer)->first();
                    $guarantor                      = $request->guarantor;
                    $noBorrower                     = 0;
                    $loanrefID                      = [];
                    foreach ($borrower as $borrowerData) {
                        $loan = new loan;
                        //loanref
                        $noBorrower++;
                        $request->loanref = str_shuffle(time());
                        if ($noBorrower > 1) {
                            if (is_numeric($noBorrower)) {
                                $request->loanref += $noBorrower;
                            } else {
                                $request->loanref .= $noBorrower;
                            }
                        }
                        if ($request->loanref) {
                            $loanRefExist = loan::where('ref', $request->loanref)->count();
                            if ($loanRefExist > 0) {
                                return response()->json(["status" => 0, "message" => 'Loan Reference ID  Exist!' . $request->loanref]);
                            }
                        }
                        $loanrefID[]                          = $request->loanref;
                        $loan->ref                            = $request->loanref;
                        $loan->created_by                     = auth()->user()->id;
                        $loan->loan_status_id                 = 3;
                        $user                                 = User::where('uuid', $borrowerData->uuid)->first();
                        $loan->user_id                        = $user->id;
                        $loan->loan_product_id                = $productid;
                        $loan->remark                         = $request->remark;
                        $loan->LoanBranch_id                  = $branchid;
                        $loan->LoanOfficer_id                 = $loanOfficer->id;
                        $loan->DurationPeriod                 = $request->DurationPeriod;
                        $loan->modified_by                    = auth()->user()->id;
                        $loan->duration                       = $ValidateRequest->duration;
                        $loan->period_id                      = $period;
                        $loan->rate                           = $ValidateRequest->interestRate;
                        $loan->principal                      = $ValidateRequest->principal;
                        $loan->loanBalance                    = $ValidateRequest->Principel_Interest;
                        $loan->LoanType                       = $request->loan_type;
                        $loan->EachRepayment                  = $EachRepayment;
                        $loan->InterestLeft                   = $ValidateRequest->TotalLoanInterest;
                        $loan->principalLeft                  = $ValidateRequest->principal;
                        $loan->LoanFee                        = $loanFee;
                        $loan->mrgtFee                        = $managementFee;
                        $loan->LoanFeeMethod                  = $loanFeeMethod;
                        $loan->mrgtFeeMethod                  = $managementFeeMethod;
                        $loan->auto_pay                       = $auto_pay;
                        $loan->missedFee                      = $missedFee;
                        $loan->on_guarantor                   = $request->guarantorOption;
                        $loan->ExtendDuration                 = $ExtendDuration;
                        $loan->keep_as_past_onMissed_onExtend = $keep_as_past_onMissed_onExtend;
                        $loan->save();
                        if ($loan) {
                            return response()->json(['status' => 1, 'newref' => str_shuffle(time()), 'message' => "Loan Successfully Booked! \n LoanID: $loan->ref;  \n  Loan now requires approval and disbursment"]);
                        } else {
                            return response()->json(['status' => 0, 'newref' => str_shuffle(time()), 'message' => "An error occured!"]);
                        }
                    }
                    // $ref = implode($loanrefID);
                    return response()->json(['status' => 1, 'newref' => str_shuffle(time()), 'message' => "Loan Successfully Booked! <br> LoanID: $loan->ref;  <br>  Loan now requires approval and disbursment"]);

                } else {
                    return $ValidateRequest->message;
                }
            });
            return response()->json(['status' => 0, 'message' => "An error occured!"]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() . $e->getLine(), 'status' => 0]);
        }
    }
    public function listUserLoan()
    { //loan_repayments
        logInfo(json_encode(auth()->user()->id), "User Loan");
        /*   $loan = loan::join('loan_status', 'loan_status.statusID', '=', 'loans.loan_status_id')
        // ->join('loan_repayments','loan_repayments.loan_id','=','loans.id')
            ->where('user_id', auth()->user()->id)->select('loans.*', DB::raw('loan_status.name as loanstatusName'), DB::raw('loan_status.tag as loanstatustag'))->get();*/
        $loanResource = loan::where('user_id', auth()->user()->id)->get();
        $loan         = ListLoanResource::collection($loanResource);
        logInfo(json_encode($loan), "List User Loan");
        return (count($loan)) ? response()->json(['message' => 'Data Found!', 'data' => $loan, 'status' => 1]) : response()->json(['message' => "no loan data", 'data' => $loan, 'status' => 0]);
    }
    public function PaylLoan(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'loanRef'     => 'required',
            'amount'      => 'required',
            'accountType' => 'required',

        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message <br>";
                }
            }
            return response()->json(['message' => $errors, 'status' => 0]);
        }
        $loan = loan::where('ref', $request->loanRef)->first();
        if (! $loan) {
            return response()->json((['status' => 0, 'message' => 'Invalide Loan!']));
        }

        $request->merge(['method' => 'wallet', 'payment' => date('Y-m-d'), 'loanID' => Crypt::encryptString($loan->id)]);
        return response()->json(app('App\Http\Controllers\LoanController')->generalpayloan($request));
    }
    public function viewRepayment($id)
    {
        $userID = auth()->user()->id;
        $loan   = LoanRepayment::join('loans', 'loans.id', '=', 'loan_repayments.loan_id')->where(['loans.user_id' => $userID, 'loans.ref' => $id])->get();
        return response()->json($loan);
    }
}
