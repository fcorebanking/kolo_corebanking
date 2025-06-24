<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
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
        $user        = (auth()->check()) ? auth()->user() : User::where('id', $request->userid)->first();
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
        return $request;
    }
    public function processLoan(Request $request)
    {
        $newrequest = $this->postDataProcessing($request);
        if ($newrequest->status == 1) {
            $repayment = json_decode(app('App\Http\Controllers\LoanController')->doProcessing($newrequest));
            return ($repayment);
        } else {
            return response()->json($newrequest);
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
            $appdata          = ApploanSettings::where(['company_id' => $user->company_id])->where('transactionMin', '<=', $transactions_sum)->where('transactionMax', '>=', $transactions_sum)->where('minWalletBalance', '<=', $balance)->where('maxWalletBalance', '>=', $balance)->where('NumOngoinAllowed', '>', $loanCount)->first();
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
            return response()->json(["status" => 0, "message" => 'Your pending loan need to be approved before booking for another!']);
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
        $loan = loan::join('loan_status', 'loan_status.statusID', '=', 'loans.loan_status_id')
        // ->join('loan_repayments','loan_repayments.loan_id','=','loans.id')
            ->where('user_id', auth()->user()->id)->select('loans.*', DB::raw('loan_status.name as loanstatusName'), DB::raw('loan_status.tag as loanstatustag'))->get();
        return (count($loan)) ? response()->json(['message' => 'Data Found!', 'data' => $loan, 'status' => 1]) : response()->json(['message' => "no loan data", 'status' => 0]);
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
