<?php
namespace App\Http\Controllers;

use App\Imports\LoanImport;
use App\Models\AddedLoanLog;
use App\Models\banks;
use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\CompanyValut;
use App\Models\company_wallet;
use App\Models\currencies;
use App\Models\Grouploan;
use App\Models\guarantor;
use App\Models\ImportLoan;
use App\Models\loan;
use App\Models\LoanGuarantor;
use App\Models\LoanRepayment;
use App\Models\loan_period;
use App\Models\loan_product;
use App\Models\payLoanPenalty;
use App\Models\PendingRepayment;
use App\Models\transactions;
use App\Models\User;
use App\Models\UserGroupLoan;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\FlutterWave;
use App\Services\LipilaService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class LoanController extends Controller
{
    // product page
    public function index()
    {
        if (! cando('loan-product|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_product',
            'page_menu'        => 'loan_product',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'period'           => loan_period::all(),
        ];
        return view('company.loan.product')->with($data);
    }
    public function save_loanproduct(Request $request)
    {
        if (! cando('loan-product|root')) {
            return response()->json(['status' => 0, 'message' => 'Access Denied!']);
        }

        // add pproduct
        $validator = Validator::make($request->all(), [
            'product_name'                   => 'required|string|max:50|min:2',
            'interest_rate'                  => 'required|numeric|min:0|max:100',
            'keep_as_past_onMissed_onExtend' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $messages,
                'subtext' => $messages,
                'status'  => 0,
            ];
            return response()->json($datax);
        }
        try {
            $request->period = ($request->period) ? Crypt::decryptString($request->period) : null;
            $response        = '';
            $check_loan      = loan_product::where(['company_id' => auth()->user()->company_id, 'product_name' => $request->product_name])->count();
            if ($check_loan > 0) {
                return response()->json(['status' => 0, 'message' => 'Name Already Exist!']);
            }
            if ($request->minPrincipal >= $request->maxPrincipal) {
                return response()->json(['status' => 0, 'message' => 'Minimum Principal cannot be bigger or equal to Maximum Principal']);
            }
            if ($request->minPrincipal >= $request->maxPrincipal) {
                return response()->json(['status' => 0, 'message' => 'Minimum Principal cannot be bigger or equal to Maximum Principal']);
            }
            $loan_product                                 = new loan_product;
            $response                                     = 'Product Created';
            $loan_product->repayment_cycle_id             = $request->period;
            $loan_product->company_id                     = auth()->user()->company_id;
            $loan_product->product_name                   = $request->product_name;
            $loan_product->interest_rate                  = $request->interest_rate;
            $loan_product->minPrincipal                   = $request->minPrincipal;
            $loan_product->DefaultPrincipal               = $request->DefaultPrincipal;
            $loan_product->maxPrincipal                   = $request->maxPrincipal;
            $loan_product->loanFeeMethod                  = $request->loanFeeMethod;
            $loan_product->loanFee                        = $request->loanFee;
            $loan_product->loan_interest_type             = $request->loan_interest_type;
            $loan_product->managementFeeMethod            = $request->managementFeeMethod;
            $loan_product->managementFee                  = $request->managementFee;
            $loan_product->penaltyChargeMethod            = $request->penaltyChargeMethod;
            $loan_product->minDuration                    = $request->minDuration;
            $loan_product->DefaultDuration                = 1; //$request->DefaultDuration;
            $loan_product->maxDuration                    = $request->maxDuration;
            $loan_product->orderOfRepayment               = $request->orderOfRepayment;
            $loan_product->approximate_status             = $request->approximate_status;
            $loan_product->auto_pay                       = $request->auto_pay;
            $loan_product->missedFee                      = $request->missedFee;
            $loan_product->minExtendDuration              = $request->minExtendDuration;
            $loan_product->maxExtendDuration              = $request->maxExtendDuration;
            $loan_product->keep_as_past_onMissed_onExtend = $request->keep_as_past_onMissed_onExtend;
            $loan_product->penaltyChargePeriod            = $request->penaltyChargePeriod;
            $loan_product->save();
            return response()->json(['status' => 1, 'message' => $response]);
        } catch (\Throwable $e) {
            // Do something with your exception
            return response()->json(['message' => $e->getMessage(), 'status' => 0]);
        }
    }
    public function update_loanproduct(Request $request)
    {
        if (! cando('loan-update-product|root')) {
            return response()->json(['status' => 0, 'message' => 'Access Denied!']);
        }

        // add pproduct
        $validator = Validator::make($request->all(), [
            'product_name'  => 'required|string|max:50|min:2',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'product_id'    => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $messages,
                'subtext' => $messages,
                'status'  => 0,
            ];
            return response()->json($datax);
        }
        try {
            $request->period = ($request->period) ? Crypt::decryptString($request->period) : null;
            $response        = '';

            $productID    = Crypt::decryptString($request->product_id);
            $response     = 'Product Updated';
            $loan_product = loan_product::where(['company_id' => auth()->user()->company_id, 'id' => $productID])->first();
            if ($loan_product == null) {
                return response()->json(['status' => 0, 'message' => 'Invalid Product!']);
            }
            $check_loan = loan_product::where(['company_id' => auth()->user()->company_id, 'product_name' => $request->product_name])->where('id', '!=', $productID)->count();
            if ($check_loan > 0) {
                return response()->json(['status' => 0, 'message' => 'Name Already Exist!' . $request->product_id]);
            }
            $loan_product->repayment_cycle_id             = ($request->period) ? $request->period : $loan_product->repayment_cycle_id;
            $loan_product->company_id                     = auth()->user()->company_id;
            $loan_product->product_name                   = $request->product_name;
            $loan_product->interest_rate                  = $request->interest_rate;
            $loan_product->minPrincipal                   = $request->minPrincipal;
            $loan_product->DefaultPrincipal               = $request->DefaultPrincipal;
            $loan_product->maxPrincipal                   = $request->maxPrincipal;
            $loan_product->loanFeeMethod                  = $request->loanFeeMethod;
            $loan_product->loanFee                        = $request->loanFee;
            $loan_product->orderOfRepayment               = $request->orderOfRepayment;
            $loan_product->managementFeeMethod            = $request->managementFeeMethod;
            $loan_product->loan_interest_type             = $request->loan_interest_type;
            $loan_product->managementFee                  = $request->managementFee;
            $loan_product->penaltyChargeMethod            = $request->penaltyChargeMethod;
            $loan_product->minDuration                    = $request->minDuration;
            $loan_product->DefaultDuration                = 1; //$request->DefaultDuration;
            $loan_product->maxDuration                    = $request->maxDuration;
            $loan_product->approximate_status             = $request->approximate_status;
            $loan_product->auto_pay                       = $request->auto_pay;
            $loan_product->missedFee                      = $request->missedFee;
            $loan_product->minExtendDuration              = $request->minExtendDuration;
            $loan_product->maxExtendDuration              = $request->maxExtendDuration;
            $loan_product->keep_as_past_onMissed_onExtend = $request->keep_as_past_onMissed_onExtend;
            $loan_product->penaltyChargePeriod            = $request->penaltyChargePeriod;
            $loan_product->save();
            return response()->json(['status' => 1, 'message' => $response]);
        } catch (\Throwable $e) {
            // Do something with your exception
            return response()->json(['message' => $e->getMessage(), 'status' => 0]);
        }
    }
    public function view_product(Request $request)
    {
        if (! cando('loan-product|root')) {
            return response()->json(['status' => 0, 'message' => 'Access Denied!']);
        }

        //Display Product
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 100);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'id',
            1 => 'product_name',
            2 => 'interest_rate',
            3 => 'DefaultPrincipal',
            4 => 'loanFee',
            5 => 'managementFee',
            6 => 'missedFee',
            7 => 'id',
        ];
        $query = loan_product::where(['company_id' => auth()->user()->company_id]);
        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('product_name', 'like', '%' . $filter . '%');
                $query->orWhere('interest_rate', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
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
        $loop = 0;
        foreach ($info as $infoData) {
            $btnlink = Crypt::encryptString($infoData->id);
            $edit    = "edit('$btnlink')";
            $delete  = "permanent_delete('$btnlink')";
            $btn     = '<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#" onclick=' . $edit . '>Edit</a>
                    <a class="dropdown-item"  href="#" onclick=' . $delete . '>Delete Permanently</a>
                </div>
            </div>';
            $loop++;
            $json['data'][] = [
                $loop,

                $infoData->product_name,
                $infoData->interest_rate,
                $infoData->DefaultPrincipal,
                $infoData->loanFee,
                $infoData->managementFee,
                $infoData->missedFee,
                $btn,
            ];
        }
        return $json;
    }
    public function edit_product($id)
    {
        if (! cando('loan-product|root')) {
            return response()->json(['status' => 0, 'message' => 'Access Denied!']);
        }

        $id      = Crypt::decryptString($id);
        $product = loan_product::where(['company_id' => auth()->user()->company_id, 'id' => $id])->first();
        if ($product) {
            $id                          = Crypt::encryptString($product->id);
            $product->repayment_cycle_id = Crypt::encryptString($product->repayment_cycle_id);
            return response()->json(['product' => $product, 'productID' => $id]);
        } else {return 0;}
    }
    // borrower
    public function borrower()
    {
        if (! cando('loan-book|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'loan_main',
            'page_menu'        => 'loan_main',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'refLoan'          => str_shuffle(time()),
            'branch'           => branch::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
            'period'           => loan_period::all(),
            // 'product'=>loan_product::where(['company_id'=>auth()->user()->company_id,'status_id'=>1])->get(),
        ];
        return view('company.loan.bookloan')->with($data);
    }
    public function loanprpoducts_list(Request $request)
    {
        $search     = $request->searchTerm;
        $company_id = auth()->user()->company_id;
        $query      = loan_product::where(['status_id' => 1, 'company_id' => $company_id]);
        if ($search != null) {

            if (! empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->orwhere('product_name', 'like', '%' . $search . '%');
                    $query->orWhere('interest_rate', 'like', '%' . $search . '%');
                });
            }
        }
        $query    = $query->paginate(10);
        $datainfo = [];
        if ($query) {
            foreach ($query as $querydata) {
                $datainfo[] = ['id' => Crypt::encryptString($querydata->id), 'text' => $querydata->product_name . ' - ' . $querydata->interest_rate . '%'];
            }
            return (json_encode($datainfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function getpoducts_list($id)
    {
        $company_id = auth()->user()->company_id;
        $id         = Crypt::decryptString($id);
        $query      = loan_product::where(['id' => $id, 'status_id' => 1, 'company_id' => $company_id])->first();
        return json_encode(['maxPrincipal' => $query->maxPrincipal, 'maxInterest' => $query->maxInterest, 'maxDuration' => $query->maxDuration]);
    }
    public function doProcessing($request)
    {
        $company  = auth()->user()->company_id;
        $branchid = Crypt::decryptString($request->branch);

        $productid = Crypt::decryptString($request->loan_products);

        $LoanType     = $request->loan_type;
        $RepaymentAmt = $request->RepaymentAmt;
        logInfo(json_encode($branchid), "loan user branchid");
        logInfo(json_encode($productid), "loan user productid");
        logInfo(json_encode($LoanType), "loan LoanType");
        logInfo(json_encode($RepaymentAmt), "loan user RepaymentAmt");

        $checkProducts = loan_product::where(['id' => $productid, 'status_id' => 1, 'company_id' => $company])->first();
        logInfo(json_encode($checkProducts), "loan user checkProducts");
        if ($checkProducts) {
            $pickguarantor = [];
            $branch        = branch::where(['id' => $branchid, 'company_id' => $company])->first();
            if ($branch) {
                $user_data = User::where(['uuid' => $request->officer, 'company_id' => $company])->first();
                if ($user_data) {
                    $period         = Crypt::decryptString($request->period);
                    $decimaPoint    = $checkProducts->approximate_status;
                    $maxPrincipal   = $checkProducts->maxPrincipal;
                    $minPrincipal   = $checkProducts->minPrincipal;
                    $maxDuration    = $checkProducts->maxDuration;
                    $principal      = $request->principal;
                    $duration       = $request->Duration;
                    $DurationPeriod = $request->DurationPeriod;
                    $finalRepayment = 0;
                    if ($LoanType == 2 && $period != 4) {
                        return json_encode([
                            'status'  => 0,
                            'message' => "Reducing balance is only available monthly!",
                        ]);
                    }
                    $convertinterest = $checkProducts->interest_rate;
                    $periodRepay     = loan_period::where('id', $period)->first();
                    $looper          = calculatePeriodByPeriod($periodRepay->name, $DurationPeriod) * $duration;

                    if ($checkProducts->loan_interest_type == 2) {
                        $checkProducts->interest_rate = $checkProducts->interest_rate * $duration;
                    }
                    $interestRate = $checkProducts->interest_rate;

                    if ($LoanType == 2) {
                        $RepaymentAmt = $this->EMI($principal, $interestRate, $looper);
                    }

                    if ($principal < $minPrincipal && $minPrincipal != null) {
                        return json_encode([
                            'status'  => 0,
                            'message' => 'Principal amount is below minimum amount  for this product!',
                        ]);
                    }
                    if ($principal > $maxPrincipal && $maxPrincipal != null) {
                        return json_encode([
                            'status'  => 0,
                            'message' => 'Principal Amount exceed maximum amount  for this product!',
                        ]);
                    }

                    $TotalLoanInterest = ($interestRate / 100) * ($request->principal);
                    if ($duration < 1) {
                        return json_encode([
                            'status'  => 0,
                            'message' => "Maximum Duration is $maxDuration!",
                        ]);
                    }
                    if ($LoanType != 1 && $LoanType != 2) {
                        return json_encode([
                            'status'  => 0,
                            'message' => 'Invalid Loan Type!',
                        ]);
                    }
                    $Principel_Interest = ($LoanType == 1) ? ($TotalLoanInterest + $principal) : $principal;
                    $FirstRepayment     = ($Principel_Interest / $duration);
                    if ($duration > $maxDuration && $maxDuration != null) {
                        return json_encode([
                            'status'  => 0,
                            'message' => "Maximum Duration is $maxDuration!",
                        ]);
                    }
                    $repayData              = ($request->loan_type == 1) ? $this->FlatRepaymentBreakdown($decimaPoint, $principal, $Principel_Interest, $periodRepay, $looper, $interestRate, "$duration $DurationPeriod", $convertinterest) : $this->ReducingRepaymentBreakdown($decimaPoint, $principal, $periodRepay, $looper, $interestRate, $RepaymentAmt, "$duration $DurationPeriod", $convertinterest);
                    $finalRepayment         = $repayData[0]['PI'];
                    $managementFeeMethodTXT = '';
                    if ($request->managementFeeMethod == 1) {
                        $managementFeeMethodTXT = 'No Management Charges';
                    } else if ($request->managementFeeMethod == 2) {
                        $managementFeeMethodTXT = $request->managementFee . ' Management Charge from Principal';
                    } else if ($request->managementFeeMethod == 3) {
                        $managementFeeMethodTXT = $request->managementFee . 'Management Charge from Interest';
                    } else if ($request->managementFeeMethod == 4) {
                        $managementFeeMethodTXT = $request->managementFee . 'Management Charge from Principal + Interest';
                    }
                    $loanFeeMethodTXT = '';
                    if ($request->loanFeeMethod == 1) {
                        $loanFeeMethodTXT = 'No Loan Fee Charges';
                    } else if ($request->loanFeeMethod == 2) {
                        $loanFeeMethodTXT = $request->loanFee . '% Loan Fee Charge from Principal';
                    } else if ($request->loanFeeMethod == 3) {
                        $loanFeeMethodTXT = $request->loanFee . '% Loan Fee Charge from Interest';
                    } else if ($request->loanFeeMethod == 4) {
                        $loanFeeMethodTXT = $request->loanFee . '% Loan Fee Charge from Principal + Interest';
                    }

                    if ($request->loanID != null) {
                        $cid                      = Crypt::decryptString($request->loanID);
                        $loanrec                  = loan::where('id', $cid)->first();
                        $request->customer        = [$loanrec->user->uuid];
                        $request->guarantorOption = $loanrec->on_guarantor;
                        if ($request->guarantorOption == 1) {
                            // LoanGuarantor
                            $guarantors = [];
                            foreach ($loanrec->Guarantor as $guarant) {
                                $guarantors[] = Crypt::encryptString($guarant->guarantor_id);
                            }
                            $request->guarantor = $guarantors;
                        } else {
                            $request->guarantor = [];
                        }
                    }
                    $guarantorOption = $request->guarantorOption;
                    $guarantor       = $request->guarantor;
                    $customer        = $request->customer;
                    $borrowerRay     = [];

                    foreach ($customer as $customerData) {
                        $borrowerRay[] = $userrec = User::where(['uuid' => $customerData, 'company_id' => $company])->first();
                        if ($guarantorOption == 1) {
                            if ($guarantor == null) {
                                return json_encode([
                                    'status'  => 0,
                                    'message' => 'Guarantor required for all borrowers!',
                                ]);
                            }
                            $ihave = 0;
                            foreach ($guarantor as $guarantorData) {
                                $guarantorDataid = Crypt::decryptString($guarantorData);
                                $guarantorRec    = guarantor::where(['id' => $guarantorDataid, 'user_id' => $userrec->id])->first();
                                if ($guarantorRec) {
                                    $ihave++;
                                    $guarantorRec->id    = Crypt::encryptString($guarantorRec->id);
                                    $guarantorRec->owner = "$userrec->first_name $userrec->last_name";
                                    $pickguarantor[]     = $guarantorRec;
                                }
                            }
                            if ($ihave == 0) {
                                return json_encode([
                                    'status'  => 0,
                                    'message' => "$userrec->first_name $userrec->last_name does not have a guarantor!",
                                ]);
                            }
                        }
                    }
                    $loan_response = json_encode([
                        'status'              => 1,
                        'response_status'     => 'success',
                        'repayData'           => $repayData,
                        'EachRepayment'       => $finalRepayment,
                        'duration'            => $duration,
                        'LoanType'            => $LoanType,
                        'FirstRepayment'      => $FirstRepayment,
                        'TotalLoanInterest'   => $TotalLoanInterest,
                        'interestRate'        => $convertinterest,
                        'principal'           => $principal,
                        'Principel_Interest'  => $Principel_Interest,
                        'loanFee'             => $request->loanFee,
                        'managementFee'       => $request->managementFee,
                        'loanFeeMethod'       => $loanFeeMethodTXT,
                        'managementFeeMethod' => $managementFeeMethodTXT,
                        'ExtendDuration'      => ($request->ExtendDuration > 0) ? $request->ExtendDuration : 'No extension for missed loan',
                        'guarantor'           => $pickguarantor,
                        'borrower'            => $borrowerRay,
                    ]);
                    logInfo(json_encode($loan_response), "loan user response");
                    return json_encode([
                        'status'              => 1,
                        'response_status'     => 'success',
                        'repayData'           => $repayData,
                        'EachRepayment'       => $finalRepayment,
                        'duration'            => $duration,
                        'LoanType'            => $LoanType,
                        'FirstRepayment'      => $FirstRepayment,
                        'TotalLoanInterest'   => $TotalLoanInterest,
                        'interestRate'        => $convertinterest,
                        'principal'           => $principal,
                        'Principel_Interest'  => $Principel_Interest,
                        'loanFee'             => $request->loanFee,
                        'managementFee'       => $request->managementFee,
                        'loanFeeMethod'       => $loanFeeMethodTXT,
                        'managementFeeMethod' => $managementFeeMethodTXT,
                        'ExtendDuration'      => ($request->ExtendDuration > 0) ? $request->ExtendDuration : 'No extension for missed loan',
                        'guarantor'           => $pickguarantor,
                        'borrower'            => $borrowerRay,
                    ]);

                } else {
                    return json_encode(['message' => 'User Account is invalid!' . $request->officer, 'status' => 0]);
                }
            } else {
                return json_encode(['message' => 'Branch is invalid!', 'status' => 0]);
            }
        } else {
            return json_encode(['message' => 'Invalid Product!', 'status' => 0]);
        }
    }
    public function processLoan(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'customer'                       => 'required',
            'loan_products'                  => 'required',
            'loan_type'                      => 'required|min:1',
            'branch'                         => 'required',
            'officer'                        => 'required',
            'loanFeeMethod'                  => 'required|max:1',
            'loanFee'                        => 'required',
            'managementFee'                  => 'required',
            'managementFeeMethod'            => 'required|max:1',
            'period'                         => 'required',
            'Duration'                       => 'required|max:3',
            'principal'                      => 'required|min:2',
            'keep_as_past_onMissed_onExtend' => 'required|max:1',
            'guarantorOption'                => 'required|max:1',
            'auto_pay'                       => 'required|max:1',
            'ExtendDuration'                 => 'required|max:1',
            'missedFee'                      => 'required',
            'DurationPeriod'                 => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message <br>";
                }
            }
            return json_encode(['message' => $errors, 'status' => 0]);
        }
        return $this->doProcessing($request);
    }
    public function FlatRepaymentBreakdown($decimaPoint, $principal, $Principel_Interest, $period, $duration, $Interest, $totalDuration = null, $loan_interest_type = null)
    {
        $repay                = [];
        $durationpicker_array = explode(" ", $totalDuration);
        $rate                 = $Interest / $durationpicker_array[0];
        if ($loan_interest_type == null) {
            $loan_interest_type = $Interest;
        }
        $j        = 0;
        $princRet = $PItotal = $repayItotal = $Itotal = 0;
        for ($i = 0; $i < $duration; $i++) {
            $due          = ($principal / $duration);
            $IT           = $Principel_Interest - $principal;
            $repayI       = $IT / $duration;
            $pI           = ($due + $repayI);
            $j           += 1;
            $princRet    += $due;
            $PItotal     += $pI;
            $repayItotal += $repayI;
            $Itotal      += $IT;
            $repay[]      = ['principal' => $principal, 'principal_return' => round($due, $decimaPoint), 'PI' => round($pI, $decimaPoint), 'I' => round($IT, $decimaPoint), 'duration' => "$period->name-$j", 'repayI' => round($repayI, $decimaPoint) . " - ($loan_interest_type%)"];
        }
        $repay[] = ['principal' => '<b>Summery</b>', 'principal_return' => "<b>" . $princRet . "</b>", 'PI' => "<b>" . $PItotal . "</b>", 'I' => '<b>' . round($IT, $decimaPoint) . '</b>', 'duration' => "<b>$totalDuration</b>", 'repayI' => "<b>$repayItotal - ($rate%)</b>"];
        return $repay;
    }
    public function ReducingRepaymentBreakdown($decimaPoint, $principal, $period, $duration, $Interest, $emiInterest, $totalDuration = null, $loan_interest_type = null)
    {
        // Amortisation Table - Reducing Balance Loan
        $repay = [];
        $j     = 0;
        $IT    = 0;
        // $durationpicker_array = explode(" ",$totalDuration);
        // $rate = $Interest/$durationpicker_array[0];
        if ($loan_interest_type == null) {
            $loan_interest_type = $Interest;
        }
        $principal_reduction = 0;
        $loan_balance        = $principal;
        $RepaymentAmt        = $emiInterest + $principal / $duration;
        $IX                  = $payment_amountX                  = $principal_reductionX                  = $principal_reduction                  = 0;
        for ($i = 0; $i < $duration; $i++) {
            $j                    += 1;
            $repay[]               = ['PI' => $RepaymentAmt, 'loan_balance' => round($loan_balance, $decimaPoint), 'principal_reduction' => round($principal_reduction, $decimaPoint), 'I' => round($IT, $decimaPoint), 'duration' => "$period->name-$j", 'rate' => "$loan_interest_type%", "payment_amount" => round($RepaymentAmt, $decimaPoint)];
            $IT                    = $emiInterest; //$this->EMI($loan_balance,$Interest,$duration);
            $principal_reduction   = $RepaymentAmt - $IT;
            $loan_balance         -= $principal_reduction;
            $principal_reductionX += $principal_reduction;
            $payment_amountX      += $RepaymentAmt;
            $IX                   += $IT;
        }
        $repay[] = ['PI' => $RepaymentAmt, 'loan_balance' => '<b>Summery</b>', 'principal_reduction' => "<b>" . round($principal_reductionX, $decimaPoint) . "</b>", 'I' => "<b>" . round($IX, $decimaPoint) . "</b>", 'duration' => "<b>$totalDuration</b>", 'rate' => '<b>--</b>', 'payment_amount' => '<b>' . round($payment_amountX, $decimaPoint) . '</b>'];
        return $repay;
    }
    public function EMI($P, $r, $n)
    {
        $R = ($r * 0.01);
        return ($R / $n) * $P;
    }
    public function FeeCollector($fee = [])
    {
        if (! is_array($fee)) {
            return ['status' => 0, 'message' => 'parameter not an array!'];
        }

        $mgt        = $fee['mgtfee'];
        $mgtMethod  = $fee['mgtfeeMethod'];
        $loan       = $fee['loanfee'];
        $loanMethod = $fee['loanfeeMethod'];

    }

    public function PostBooklLoan(Request $request)
    {
        if ($request->action == null) {
            $loanCount = loan::where(['created_by' => auth()->user()->id])->orderBy('id', 'desc')->first();
            if ($loanCount) {
                if ($loanCount) {
                    $dbtimestamp = strtotime($loanCount->created_at);
                    if (time() - $dbtimestamp < 1 * 20) {
                        return json_encode(["status" => 0, "message" => 'To avoid duplicate you must wait for 20 seconds to book another loan!']);
                    }
                }
            }
        }
        try {
            return DB::transaction(function () use ($request) {
                $ValidateRequest = json_decode($this->processLoan($request));

                if ($ValidateRequest->status) {
                    $interestRate                   = $ValidateRequest->interestRate;
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
                        if ($request->loanID != null) {
                            if (! cando('loan-pending-approve|root')) {
                                return json_encode(["status" => 0, "message" => 'Permission Denied!']);
                            }

                            if ($request->actions == null || ($request->actions != 2 && $request->actions != 4 && $request->actions != 5 && $request->actions != 1)) {
                                return json_encode(["status" => 0, "message" => 'Action needed please specify if the loan is approved,rejected or deleted!']);
                            }
                            $loanID = Crypt::decryptString($request->loanID);
                            if ($request->actions == 5) {
                                $loan = loan::where('id', $loanID)->delete();
                                return json_encode(["status" => 1, "message" => 'Loan Successfully Deleted!']);
                            }
                            $loan = loan::where('id', $loanID)->first();
                            if ($loan->loan_status_id == 2) {
                                return json_encode(["status" => 0, "message" => 'Loan Already Approved!']);
                            }
                            $loan->loan_status_id = $request->actions;
                        } else {
                            if (! cando('loan-book|root')) {
                                return json_encode(["status" => 0, "message" => 'Permission Denied!']);
                            }

                            $loan = new loan;
                            //loanref
                            $noBorrower++;
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
                                    return json_encode(["status" => 0, "message" => 'Loan Reference ID  Exist!' . $request->loanref]);
                                }
                            }
                            $loanrefID[]          = $request->loanref;
                            $loan->ref            = $request->loanref;
                            $loan->created_by     = auth()->user()->id;
                            $loan->loan_status_id = 3;
                            $user                 = User::where('uuid', $borrowerData->uuid)->first();
                            $loan->user_id        = $user->id;

                            $GrouploanUser  = UserGroupLoan::where(['user_id' => $user->id])->first();
                            if ($GrouploanUser) {

                                $Grouploan = $GrouploanUser->Group;
                                if ($Grouploan) {
                                    if ($Grouploan->max_principal < $principal && ($Grouploan->max_principal != 0 || $Grouploan->max_principal != null)) {
                                        return json_encode([
                                            'status'  => 0,
                                            'message' => "Maximum Principal for this group is $Grouploan->max_principal!",
                                        ]);
                                    }
                                    if ($Grouploan->max_interest < $interestRate && ($Grouploan->max_interest != 0 || $Grouploan->max_interest != null)) {
                                        return json_encode([
                                            'status'  => 0,
                                            'message' => "Maximum Interest in % for this group is $Grouploan->max_interest!",
                                        ]);
                                    }
                                }
                            }
                        }
                        $loan->loan_product_id = $productid;
                        $loan->remark          = $request->remark;
                        $loan->LoanBranch_id   = $branchid;
                        $loan->LoanOfficer_id  = $loanOfficer->id;
                        $loan->DurationPeriod  = $request->DurationPeriod;
                        $loan->modified_by     = auth()->user()->id;
                        $loan->duration        = $ValidateRequest->duration;
                        $loan->period_id       = $period;
                        $loan->rate            = $ValidateRequest->interestRate;
                        if ($request->actions != null) {
                            if ($request->actions == 1) {
                                $p             = 0;
                                $i             = 0;
                                $amt           = 0;
                                $LoanRepayment = LoanRepayment::where(['loan_id' => $loan->id, 'status_id' => 1])->get();
                                if ($LoanRepayment) {
                                    foreach ($LoanRepayment as $LoanRepaymentData) {
                                        $p   += $LoanRepaymentData->P;
                                        $i   += $LoanRepaymentData->I;
                                        $amt += $LoanRepaymentData->amount;
                                    }
                                }
                                $loan->loanBalance   = $ValidateRequest->Principel_Interest - $amt;
                                $loan->InterestLeft  = $ValidateRequest->TotalLoanInterest - $i;
                                $loan->principalLeft = $ValidateRequest->principal - $p;
                            } else {
                                $loan->loanBalance   = $ValidateRequest->Principel_Interest;
                                $loan->InterestLeft  = $ValidateRequest->TotalLoanInterest;
                                $loan->principalLeft = $ValidateRequest->principal;
                            }
                        } else {
                            $loan->loanBalance   = $ValidateRequest->Principel_Interest;
                            $loan->InterestLeft  = $ValidateRequest->TotalLoanInterest;
                            $loan->principalLeft = $ValidateRequest->principal;
                        }
                        $loan->principal                      = $ValidateRequest->principal;
                        $loan->LoanType                       = $request->loan_type;
                        $loan->EachRepayment                  = $EachRepayment;
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
                        if ($request->loanID == null) {
                            $guarantorOption = $request->guarantorOption;
                            if (count($ValidateRequest->guarantor) > 0 && $guarantorOption == 1) {
                                foreach ($guarantor as $guarantorData) {
                                    $guarantorDataid = Crypt::decryptString($guarantorData);
                                    $guarantorRec    = guarantor::where(['id' => $guarantorDataid, 'user_id' => $user->id])->first();
                                    if ($guarantorRec) {
                                        $LoanGuarantor               = new LoanGuarantor;
                                        $LoanGuarantor->guarantor_id = $guarantorRec->id;
                                        $LoanGuarantor->loan_id      = $loan->id;
                                        $LoanGuarantor->created_at   = date('Y-m-d H:i:s');
                                        $LoanGuarantor->updated_at   = date('Y-m-d H:i:s');
                                        $LoanGuarantor->save();
                                    }
                                }
                            }
                        }
                    }
                    if ($request->loanID == null) {
                        // $ref = implode($loanrefID);
                        return json_encode(['status' => 1, 'newref' => str_shuffle(time()), 'message' => "Loan Successfully Booked! <br> LoanID: $loan->ref;  <br>  Loan now requires approval and disbursment"]);
                    } else {
                        $ap = 'updated';
                        if ($request->actions == 2) {
                            $companName = auth()->user()->company->company_name;
                            $appMSG     = "We are highly pleased to inform you that your application for a loan of $loan->principal has been approved by $companName. You can visit our office for more.";
                            sendSMS($loan->user_id, "$appMSG", true, true, 'Loan Approval');
                            $ap = 'Successfully Approved! <br> Go-to Disbursment to finalise loan';
                        } else if ($request->actions == 4) {
                            $ap         = 'Rejected! ';
                            $companName = auth()->user()->company->company_name;
                            $appMSG     = "Your request for a loan has been declined. Reason : Your credit score was too low. Start saving with us to qualify for a loan.";
                            sendSMS($loan->user_id, "$appMSG", true, true, 'Loan Approval');
                        }
                        return json_encode(['status' => 1, 'message' => "(LoanID: $loan->ref) $ap!"]);
                    }
                } else {
                    return $ValidateRequest->message;
                }
            });
            return json_encode(['status' => 0, 'message' => "An error occured!"]);
        } catch (\Throwable $e) {
            return json_encode(['message' => $e->getMessage(), 'status' => 0]);
        }
    }
    public function PendingLoan()
    {
        if (! cando('loan-pending-approve|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_pending',
            'page_menu'        => 'loan_pending',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'period'           => loan_period::all(),
            'branch'           => branch::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
            'product'          => loan_product::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
        ];
        return view('company.loan.pendingloans')->with($data);
    }
    public function viewPendingLoan(Request $request)
    {
        if (! cando('loan-pending-approve|root|loan-disbursement')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        //Display Product
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 100);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0 => 'loans.id',
            1 => 'users.first_name',
            2 => 'loans.rate',
            3 => 'loans.ref',
            4 => 'loans.rate',
            5 => 'loans.principal',
            6 => 'product_name',
            7 => 'status_id',
            8 => 'loans.id',
        ];
        $query = loan::join('users', 'loans.user_id', 'users.id')
            ->join('loan_products', 'loans.loan_product_id', 'loan_products.id')
            ->where(['users.company_id' => auth()->user()->company_id, 'users.status_id' => 1])
            ->select('loans.*', 'loan_products.product_name', 'users.virtual_account', 'users.first_name', 'users.last_name', 'users.phone', 'users.remittar_accountNo', 'users.remittar_bankCode', 'uuid');
        if ($request->all == null) {
            if ($request->disburse != null) {
                $query = $query->where(['loans.loan_status_id' => 2]);
            } else if ($request->rejected != null) {
                $query = $query->where(['loans.loan_status_id' => 4]);
            } else if ($request->ongoing != null) {
                $query = $query->where(['loans.loan_status_id' => 1]);
            } else {
                $query = $query->where(['loans.loan_status_id' => 3]);
            }
        }
        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('loans.loanBalance', 'like', '%' . $filter . '%');
                $query->orwhere('loans.principal', 'like', '%' . $filter . '%');
                $query->orwhere('loans.ref', 'like', '%' . $filter . '%');
                $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
                $query->orWhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('users.email', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
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
        $loop = 0;
        foreach ($info as $infoData) {
            $btnlink = Crypt::encryptString($infoData->id);
            $uuid    = $infoData->uuid;
            if ($request->disburse != null) {
                $approve = "viewLoan('$btnlink','$uuid')";
            } else {
                $approve = "viewLoan('$btnlink')";
            }
            //
            $remitaCre = "viewRemitaSalary('" . $infoData->phone . "','" . $infoData->remittar_accountNo . "','" . $infoData->remittar_bankCode . "')";
            $remitta   = (auth()->user()->company->setting->activate_remitta) ? '<a class="dropdown-item" href="#" onclick=' . $remitaCre . '>View Salary History(NGN 50)</a>' : '';
            $btn       = '<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#" onclick=' . $approve . '>View</a>
                ' . $remitta . '
                </div>
            </div>';
            $loop++;
            $json['data'][] = [
                $loop,
                $infoData->first_name . ' ' . $infoData->last_name,
                $infoData->virtual_account,
                $infoData->ref,
                $infoData->rate,
                $infoData->principal,
                $infoData->product_name,
                ($infoData->loan_status_id == 1) ? '<span class="badge badge-info">Ongoing</span>' : (($infoData->loan_status_id == 4) ? '<span class="badge badge-danger">Rejected</span>' : (($infoData->loan_status_id == 10) ? '<span class="badge badge-success">Complete Loan</span>' : '<span class="badge badge-warning">Waiting Approval</span>')),
                $btn,
            ];
        }
        return $json;
    }
    public function LoanDetailsData($id)
    {
        $id                   = Crypt::decryptString($id);
        $loan                 = loan::where('id', $id)->first();
        $loan->user_id        = Crypt::encryptString($loan->user_id);
        $loan->productText    = $loan->LoanProduct->product_name . '-' . $loan->LoanProduct->interest_rate . "%";
        $loan->periodUid      = $loan->PeriodID->name;
        $loan->LoanBranch_id  = $loan->branch->branch_name;
        $loan->LoanOfficer_id = $loan->LoanOfficer->uuid;
        $loan->modified_by    = $loan->editor->first_name . ' ' . $loan->editor->last_name;
        $loan->created_by     = $loan->author->first_name . ' ' . $loan->author->last_name;
        return json_encode($loan);
    }

    public function LoanDisbursment()
    {
        if (! cando('loan-disbursement|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_disbursment',
            'page_menu'        => 'loan_disbursment',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'currencies'       => currencies::all(),

        ];
        return view('company.loan.disbursloans')->with($data);
    }
    public function DisburseLoan(Request $request)
    {
        if (! cando('loan-disbursement|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        if ($request->loanID == null || $request->startDate == null || $request->graceDate == null || $request->disbursementCash == null) {
            return json_encode(['status' => 0, 'message' => 'All field are required!']);
        }
        if ($request->disbursementCash == 2 && $request->savetype == null) {
            return json_encode(['status' => 0, 'message' => 'Select Destination Account Type!']);
        }
        try {
            return DB::transaction(function () use ($request) {
                $LoanID = Crypt::decryptString($request->loanID);
                $loan   = loan::find($LoanID);
                if (isset($loan->Guarantor)) {
                    if ($loan->Guarantor) {
                        foreach ($loan->Guarantor as $gr) {
                            if ($gr->GuarantorDetails->status_id != 1) {
                                return json_encode(['status' => 0, 'message' => "Guarantor " . $gr->GuarantorDetails->first_name . " " . $gr->GuarantorDetails->last_name . " is not yet verified! "]);
                            }
                        }
                    }
                }
                if ($loan->loan_status_id == 1) {
                    return json_encode(['status' => 0, 'message' => "Loan Ref: $loan->ref Already Disbursed!"]);
                }
                $loan->disbursementCash = $request->disbursementCash;
                $loan->start_at         = $request->startDate;
                $loan->grace_at         = $request->graceDate;

                $loan->save();
                $principal = $loan->principal;

                if ($request->disbursementCash == 2) {
                    $req                        = $loan;
                    $rate                       = $req->rate;
                    $loanfee                    = $req->LoanFee;
                    $loanfeeMethod              = $req->LoanFeeMethod;
                    $mgrtFee                    = $req->mrgtFee;
                    $mrgfeeMethod               = $req->mrgtFeeMethod;
                    $interest                   = ($rate / 100) * $principal;
                    $Feemrgt                    = $this->LoanMethodPercentReturn($principal, $interest, $loanfee, $loanfeeMethod, $mgrtFee, $mrgfeeMethod);
                    $amount                     = $principal - ($Feemrgt['loanFee'] + $Feemrgt['mrgFee']);
                    $req->uuid                  = $loan->user->uuid;
                    $req->LoanFeeAmt            = $Feemrgt['loanFee'];
                    $req->LoanmgrtFeeAmt        = $Feemrgt['mrgFee'];
                    $req->amount                = $amount;
                    $req->desc                  = "Loan Disbursment credit";
                    $req->account_type          = $request->savetype;
                    $req->transaction_status_id = 1;
                    $req->transaction_type_id   = 53;
                    $req->credit_debit_type_id  = 1;
                    $creditor                   = app('App\Http\Controllers\SavingsController')->GeneralCreditCustomer_script($req);
                    if ($creditor['status'] == 0) {
                        return json_encode($creditor);
                    }
                } else if ($request->disbursementCash == 4) {
                    DB::beginTransaction();

                    $created_by     = auth()->user();
                    $baniController = new FlutterWave();
                    $lipilaService  = new LipilaService();
                    $transactionId  = generate_txnID();
                    $data           = [];
                    $currencies     = currencies::where('id', $request->currency_id)->first();
                    if ($currencies->code == 'XAF') {
                        $data["account_number"]   = $request->mobile; //
                        $data["debit_currency"]   = 'NGN';
                        $data["tx_ref"]           = $transactionId; //
                        $data["beneficiary_name"] = $created_by->first_name . ' ' . $created_by->last_name;
                        $data["narration"]        = 'WBA Loan disbursement'; //
                    } elseif ($currencies->code == 'ZMW') {
                        $data["currency"]      = 'ZMW'; //
                        $data["accountNumber"] = $request->mobile;
                        $data["fullName"]      = $created_by->first_name . ' ' . $created_by->last_name;
                        $data["phoneNumber"]   = $request->mobile;
                        $data["email"]         = auth()->user()->email;
                        $data["externalId"]    = "$transactionId";
                        $data["narration"]     = 'WBA Loan disbursement'; //
                    } else {
                        return response()->json(['status' => false, 'message' => 'Currency not available!']);
                    }
                    $data["currency"] = $currencies->code; //
                    $data["amount"]   = $principal;        //s
                    $company_wallet   = company_wallet::where(['company_id' => $created_by->company_id, 'currency_code' => $data["currency"]])->first();
                    if (! $company_wallet) {
                        return response()->json(['status' => false, 'message' => 'Company error!']);
                    }
                    if ($company_wallet->balance < $principal) {
                        return response()->json(['status' => false, 'message' => 'insufficient balance!']);
                    }
                    $transferCompanyId                    = getTransactionIdByName('Company Mobile Money Transaction'); //
                    $company_data                         = [];
                    $company_data['auth']                 = $created_by;
                    $company_data['transaction_id']       = $transferCompanyId->id;
                    $company_data['desc']                 = 'loan disbursement to ' . $request->mobile;
                    $company_data['currency']             = $currencies->code;
                    $company_data['amount']               = $principal;
                    $company_data['credit_debit_type_id'] = 2;
                    $debit                                = debitCreditCompany($company_data);
                    $response                             = ($currencies->code == 'XAF') ? ($baniController->sendMoney($data)) : ($lipilaService->disburseMobileMoney($data));
                    // return response()->json(['status' => 0, 'message' => 'Payment error','data'=>$response]);
                    // message
                    if (isset($response['status'])) {
                        if ($response['status'] == 'success' || $response['status'] == 'Successful') {
                            $txnID = ($currencies->code == 'XAF') ? $response['data']['reference'] : $response['transactionId'];
                            transactions::where('id', $debit->id)->update(['instrumentCode' => $txnID]);
                            // return response()->json(['status' => true, 'message' => 'Operation complete','data'=>$response]);
                        } else {
                            DB::rollBack();
                            return response()->json(['status' => 0, 'message' => 'Error:' . $response['message']]);
                        }
                    } else {
                        DB::rollBack();
                        return response()->json(['status' => 0, 'message' => 'Error Processing request', 'response' => $response]);
                    }
                }
                $updateloan                 = loan::where('id', $LoanID)->first();
                $updateloan->loan_status_id = 1;
                $updateloan->modified_by    = auth()->user()->id;
                $updateloan->save();
                DB::commit();

                return json_encode(['status' => 1, 'message' => "Loan Disbursed Successfully! <br> Loan Ref: $loan->ref"]);
            });
        } catch (\Throwable $e) {
            DB::rollBack();

            // Do something with your exception
            return json_encode(['message' => $e->getMessage() . " at line " . $e->getLine(), 'status' => 0]);
        }
    }
    public function LoanMethodPercentReturn($principal, $interest, $loanfee, $loanfeeMethod, $mgrtFee, $mrgfeeMethod)
    {
        $managementFeeMethodTXT = 0;
        if ($mrgfeeMethod == 1) {
            $managementFeeMethodTXT = 0;
        } else if ($mrgfeeMethod == 2) {
            $managementFeeMethodTXT = $principal * ($mgrtFee / 100);
        } else if ($mrgfeeMethod == 3) {
            $managementFeeMethodTXT = $interest * ($mgrtFee / 100);
        } else if ($mrgfeeMethod == 4) {
            $managementFeeMethodTXT = ($principal + $interest) * ($mgrtFee / 100);
        }
        $LoanFeeCash = 0;
        if ($loanfeeMethod == 1) {
            $LoanFeeCash = 0;
        } else if ($loanfeeMethod == 2) {
            $LoanFeeCash = $principal * ($loanfee / 100);
        } else if ($loanfeeMethod == 3) {
            $LoanFeeCash = $interest * ($loanfee / 100);
        } else if ($loanfeeMethod == 4) {
            $LoanFeeCash = ($principal + $interest) * ($loanfee / 100);
        }
        return (['loanFee' => $LoanFeeCash, 'mrgFee' => $managementFeeMethodTXT]);
    }
    public function LoanRejected()
    {
        if (! cando('loan-rejected|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_Rejected',
            'page_menu'        => 'loan_Rejected',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.loan.rejectedloan')->with($data);
    }
    public function RejectedLoanAction(Request $request)
    {
        if (! cando('loan-rejected|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        $id     = Crypt::decryptString($request->loanID);
        $loan   = loan::where('id', $id)->first();
        $action = $request->actions;
        if ($loan->user->company_id != auth()->user()->company_id) {
            return json_encode(['status' => 0, 'message' => 'User access Restricted']);
        }

        if ($action == 5) {
            PendingRepayment::where('loan_id', $id)->delete();
            $loan->delete();
            return json_encode(['status' => 1, 'message' => 'Loan Deleted Successfully!']);
        }
        if ($action == 3) {
            $loan->update(['loan_status_id' => 3, 'remark' => $request->remark, 'modified_by' => auth()->user()->id]);
            return json_encode(['status' => 1, 'message' => 'Loan Status Updated Successfully!']);
        }

    }
    public function OngoingLoan(Request $request)
    {
        if (! cando('loan-ongoing|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_ongoing',
            'page_menu'        => 'loan_ongoing',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        if ($request->group) {
            $id        = Crypt::decryptString($request->group);
            $Grouploan = Grouploan::where(['company_id' => auth()->user()->company_id, 'id' => $id])->first();
            if ($Grouploan) {
                $data['group']     = $request->group;
                $data['groupData'] = $Grouploan;
            }
        }
        return view('company.loan.ongoingloan')->with($data);
    }
    public function LoanRecord(Request $request)
    {
        //Display Product
        if (! cando('loan-ongoing|loan-complete|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        $root        = cando('root');
        $search      = $request->query('search', ['value' => '', 'regex' => false]);
        $draw        = $request->query('draw', 0);
        $start       = $request->query('start', 0);
        $length      = $request->query('length', 100);
        $order       = $request->query('order', [1, 'asc']);
        $filter      = $search['value'];
        $sortColumns = [
            0  => 'loans.id',
            1  => 'users.first_name',
            2  => 'loans.ref',
            3  => 'loans.rate',
            4  => 'loans.principal',
            5  => 'loans.loanBalance',
            6  => 'loans.start_at',
            7  => 'loans.grace_at',
            8  => 'loans.id',
            9  => 'loans.principalLeft',
            10 => 'loans.loan_status_id',
            11 => 'loans.loan_status_id',
        ];
        if ($request->showTotal) {
            $sortColumns[12] = 'loans.principalLeft';
        }
        $query = loan::join('users', 'loans.user_id', 'users.id')
            ->join('loan_products', 'loans.loan_product_id', 'loan_products.id')
            ->join('loan_status', 'loans.loan_status_id', 'loan_status.statusID')
            ->where(['users.company_id' => auth()->user()->company_id, 'users.status_id' => 1])
            ->select('loans.*', 'loan_products.product_name', 'users.virtual_account', 'users.first_name', 'users.last_name', 'loan_status.name', 'loan_status.tag');
        \Log::info('Loan query count:', json_encode(loan::count()));
        \Log::info('Loan query result:', loan::get()->toArray());

        if ($request->all == null) {
            if ($request->complete == null) {
                $query = $query->where(['loans.loan_status_id' => 1]);
            } else {
                $query->where(function ($query) {
                    $query->orwhere('loans.loan_status_id', 10); //completed
                    $query->orwhere('loans.loan_status_id', 8);  //liquidate
                    $query->orwhere('loans.loan_status_id', 7);  //writtenoff
                });
            }
        }
        if ($request->from != null) {
            $query = $query->where('loans.created_at', '>=', $request->from);
        }
        if ($request->to != null) {
            $query = $query->where('loans.created_at', '<=', $request->to);
        }
        if ($request->loan_products != null) {
            $loanProductdecrypt = Crypt::decryptString($request->loan_products);
            $query              = $query->where(['loans.loan_product_id' => $loanProductdecrypt]);
        }
        if ($request->branch != null) {
            $query = $query->where(['loans.LoanBranch_id' => $request->branch]);
        }

        if ($request->officer != null && $request->officer != '' && $request->officer != 'null') {
            $LoanOfficer = User::where('uuid', $request->officer)->first();
            $query       = $query->where(['loans.LoanOfficer_id' => $LoanOfficer->id]);
        }

        if ($request->status != null) {
            $query = $query->where(['loans.loan_status_id' => $request->status]);
        }
        if ($request->group != null) {
            $request->group = Crypt::decryptString($request->group);
            $query          = $query->join('user_group_loans', 'user_group_loans.user_id', 'users.id')
                ->join('grouploans', 'user_group_loans.group_id', 'grouploans.id');
            $query = $query->where(['user_group_loans.group_id' => $request->group]);
        }
        if (! $root) {
            if (! cando('loan-all|view-all-customer')) {
                $query = $query->where(['loans.LoanOfficer_id' => auth()->user()->id]);
            }
        }

        if (! empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('loans.loanBalance', 'like', '%' . $filter . '%');
                $query->orwhere('loans.principal', 'like', '%' . $filter . '%');
                $query->orwhere('loans.ref', 'like', '%' . $filter . '%');
                $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
                $query->orWhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.phone', 'like', '%' . $filter . '%');
                $query->orWhere('users.email', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal   = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        if ($request->paginate == null || $request->paginate == 'paginate') {
            $query->orderBy($sortColumnName, $order[0]['dir'])
                ->take($length)
                ->skip($start);
        }
        $json = [
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => [],
        ];
        $info           = $query->get();
        $loop           = 0;
        $totalLeft      = 0;
        $totalPrincipal = 0;
        $totalBalance   = 0;

        foreach ($info as $infoData) {
            $btnlink       = Crypt::encryptString($infoData->id);
            $btnlinkUserID = Crypt::encryptString($infoData->user_id);
            $view          = "viewLoan('$btnlink')";
            $delete        = "DeleteLoan('$btnlink')";
            $rollover      = "loanrollover('$btnlinkUserID','$btnlink')";
            $payloan       = url('company/' . Auth()->user()->company->short_name . '/loan/pay-loan/' . $btnlink);
            $loanWrite     = "loanWrite('$btnlink')";
            //complete
            $btn = '<div class="dropdown">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Action
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    ';
            if ($request->all == null) {

                if ($request->complete == null) {
                    if (cando('view-loan-repayment|loan-repayment|root')) {
                        $btn .= ' <a class="dropdown-item" href="' . $payloan . '" >PayLoan</a>';
                    }
                    if (cando('loan-rollover|root')) {
                        $btn .= '<a class="dropdown-item" href="#" onclick=' . $rollover . '>Roll Over</a>';
                    }

                    if (cando('loan-writeoff|root')) {

                        $btn .= '<a class="dropdown-item" href="#" onclick=' . $loanWrite . '>Write Off</a>';
                    }
                }
            }
            $liks = '<a class="dropdown-item" href="/company/' . auth()->user()->company->short_name . '/loan/offer/' . $btnlink . '">Loan Offer</a>';
            $btn  .= $liks;
            $btn  .= '<a class="dropdown-item" href="#" onclick=' . $view . '>Quick View</a>
                    <a class="dropdown-item" href="#" onclick=' . $delete . '>Delete Loan</a></div>
                </div>';
            $loop++;
            // $PeriodID = $infoData->PeriodID->days;
            //$name = str_replace('ly','',$infoData->PeriodID->name);
            $Duration       = $infoData->duration;
            $DurationPeriod = $infoData->DurationPeriod;
            // echo "$infoData->grace_at +$Duration $DurationPeriod";
            $maturity = date("Y-m-d", strtotime($infoData->grace_at . "+$Duration $DurationPeriod"));
            // $maturity =$infoData->grace_at
            // $status ='<span class="badge badge-success">Active</span>';
            // $active ='<span class="badge badge-success">Ongoing</span>';
            // if($infoData->loan_status_id == 10){
            //     $active ='<span class="badge badge-success">Completed</span>';
            //     $status ='<span class="badge badge-success">Fully Paid</span>';
            // }
            $totalPrincipal += $infoData->principal;
            $totalBalance   += $infoData->loanBalance;
            if ($request->showTotal) {

                $json['data'][]  = [
                    $loop,
                    $infoData->first_name . ' ' . $infoData->last_name,
                    $infoData->virtual_account,
                    $infoData->ref,
                    $infoData->rate,
                    number_format($infoData->principal),
                    number_format($infoData->loanBalance),
                    $infoData->start_at,
                    $infoData->grace_at,
                    $maturity,
                    number_format($infoData->principalLeft),
                    ($infoData->loan_status_id != null) ? '<span class="badge badge-' . $infoData->tag . '">' . $infoData->name . '</span>' : '<span class="badge badge-danger">Unknown</span>',
                    ($infoData->loanBalance > 0) ? '<span class="badge badge-warning">Incomplete</span>' : '<span class="badge badge-success">Fully Paid</span>',
                    $btn,
                ];
                $totalLeft += $infoData->principalLeft;
            } else {
                $json['data'][] = [
                    $loop,
                    $infoData->first_name . ' ' . $infoData->last_name,
                    $infoData->virtual_account,
                    $infoData->ref,
                    $infoData->rate,
                    number_format($infoData->principal),
                    number_format($infoData->loanBalance),
                    $infoData->start_at,
                    $infoData->grace_at,
                    $maturity,
                    ($infoData->loan_status_id != null) ? '<span class="badge badge-' . $infoData->tag . '">' . $infoData->name . '</span>' : '<span class="badge badge-danger">Unknown</span>',
                    ($infoData->loanBalance > 0) ? '<span class="badge badge-warning">Incomplete</span>' : '<span class="badge badge-success">Fully Paid</span>',
                    $btn,
                ];
            }
        }
        if ($request->showTotal) {
            $json['data'][] = [
                '#',
                'Total',
                '',
                '',
                '',
                '<span class="badge badge-info">' . number_format($totalPrincipal) . '</span>',
                '<span class="badge badge-warning">' . number_format($totalBalance) . '</span>',
                '',
                '',
                '',
                '<span class="badge badge-success">' . number_format($totalLeft) . '</span>',
                '',
                '',
                '',
            ];
        }
        return $json;
    }
    public function viewLoanDetails($id)
    {
        $id                                   = Crypt::decryptString($id);
        $loan                                 = loan::where('id', $id)->with('loanExtension')->first();
        $loan->user_id                        = $loan->user->first_name . ' ' . $loan->user->last_name;
        $loan->borrowerAccount                = $loan->user->virtual_account;
        $loan->Loantype                       = ($loan->Loantype == 1) ? 'Reducing Balance' : 'Flat';
        $loan->LoanBranch_id                  = $loan->branch->branch_name;
        $loan->LoanOfficer_id                 = $loan->LoanOfficer->first_name . ' ' . $loan->LoanOfficer->last_name;
        $loan->period_id                      = $loan->PeriodID->name;
        $loan->on_guarantor                   = ($loan->on_guarantor == 2) ? 'Guarantor option not set' : 'Loan guarantor option set';
        $feemethod                            = $this->LoanfeeMgrMethod($loan->LoanFeeMethod, $loan->mrgtFeeMethod);
        $loan->LoanFeeMethod                  = $feemethod['loanfeeMethod'];
        $loan->mrgtFeeMethod                  = $feemethod['mgrtFee'];
        $loan->loan_product_id                = $loan->LoanProduct->product_name;
        $loan->ExtendDuration                 = "$loan->ExtendDuration extension duration";
        $loan->auto_pay                       = ($loan->auto_pay == 1) ? "Payment option: Auto-Debit User Wallet" : "Payment option: Manualy Credit loan";
        $loan->created_by                     = $loan->author->first_name . ' ' . $loan->author->last_name;
        $loan->modified_by                    = $loan->editor->first_name . ' ' . $loan->editor->last_name;
        $loan->keep_as_past_onMissed_onExtend = ($loan->keep_as_past_onMissed_onExtend == 1) ? 'Loan Will Extend Untill fully paid' : (($loan->keep_as_past_onMissed_onExtend == 2) ? 'Loan willextend with penalty fee' : 'Loan will extend according to extend duration');
        $guarantor                            = $loan->Guarantor;
        $pickguarantor                        = [];
        if ($guarantor) {
            foreach ($guarantor as $guarantorData) {
                $guarantorRec = guarantor::where(['id' => $guarantorData->guarantor_id])->first();
                if ($guarantorRec) {
                    $guarantorRec->id    = Crypt::encryptString($guarantorRec->id);
                    $guarantorRec->owner = $loan->borrowerAccount;
                    $pickguarantor[]     = $guarantorRec;
                }
            }
        }
        $loan->pickguarantor = $pickguarantor;
        return json_encode($loan);
    }
    public function LoanfeeMgrMethod($loanfee, $mgrtFee)
    {
        $managementFeeMethodTXT = '';
        if ($mgrtFee == 1) {
            $managementFeeMethodTXT = 'No Management Charges';
        } else if ($mgrtFee == 2) {
            $managementFeeMethodTXT = 'Management Charge from Principal';
        } else if ($mgrtFee == 3) {
            $managementFeeMethodTXT = 'Management Charge from Interest';
        } else if ($mgrtFee == 4) {
            $managementFeeMethodTXT = 'Management Charge from Principal + Interest';
        }
        $LoanFeeMethod = '';
        if ($loanfee == 1) {
            $LoanFeeMethod = 'No Loan Fee Charges';
        } else if ($loanfee == 2) {
            $LoanFeeMethod = 'Loan Fee Charge from Principal';
        } else if ($loanfee == 3) {
            $LoanFeeMethod = 'Loan Fee Charge from Interest';
        } else if ($loanfee == 4) {
            $LoanFeeMethod = 'Loan Fee Charge from Principal + Interest';
        }
        return ['loanfeeMethod' => $LoanFeeMethod, 'mgrtFee' => $managementFeeMethodTXT];
    }
    public function loadUserLoan($userid, $loanID)
    {
        $userid       = Crypt::decryptString($userid);
        $loanID       = Crypt::decryptString($loanID);
        $loans        = loan::join('users', 'users.id', '=', 'loans.user_id')->where('users.company_id', auth()->user()->company_id)->where(['user_id' => $userid, 'loan_status_id' => 1])->where('id', '!=', $loanID)->select('ref')->get();
        $loansDetails = loan::where(['id' => $loanID, 'loan_status_id' => 1])->select('ref', 'loanBalance')->first();
        return json_encode(['loan' => $loans, 'detail' => $loansDetails]);
    }
    public function rollLoan(Request $request)
    {
        if (! cando('loan-rollover|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        $loanID = Crypt::decryptString($request->loanID);
        $ref    = ($request->destinationLoan);
        $source = loan::join('users', 'users.id', '=', 'loans.user_id')->where('users.company_id', auth()->user()->company_id)->where(['id' => $loanID, 'loan_status_id' => 1])->first();
        if ($source) {
            $loanBalance = $source->loanBalance;
            if ($loanBalance > 0) {
                $destination = loan::where(['ref' => $ref, 'loan_status_id' => 1])->first();
                if ($destination) {
                    $destination->loanBalance += $source->loanBalance;
                    $destination->save();
                    $source->loanBalance     = 0;
                    $source->loan_status_id  = 6; //roll over status code
                    $source->save();
                    return json_encode(['status' => 1, 'message' => "Loan has been rolled over to $destination->ref!"]);
                } else {
                    return json_encode(['status' => 0, 'message' => 'Destination ID invalid!']);
                }
            } else {
                return json_encode(['status' => 0, 'message' => 'Source loan balance is zero!']);
            }
        } else {
            return json_encode(['status' => 0, 'message' => 'Invalid Loan ID!']);
        }
    }
    public function writeLoan(Request $request)
    {
        if (! cando('loan-writeoff|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        $loanID = Crypt::decryptString($request->loanID);
        $amount = $request->writeoffamount;
        $option = $request->writeoffoptions;
        $source = loan::where(['id' => $loanID, 'loan_status_id' => 1])->first();
        if (! $source) {
            return json_encode(['status' => 0, 'message' => 'Loan not found!']);
        }
        $user = User::where(['company_id' => auth()->user()->company_id, 'id' => $source->user_id])->first();
        if (! $user) {
            return json_encode(['status' => 0, 'message' => 'Loan user not found!']);
        }
        if ($source) {
            if ($option == 2 || $option == 3 || $option == 4) {
                $source->loanBalance -= $amount;
                if ($option == 2) {
                    if ($source->principalLeft < $amount) {
                        return json_encode(['status' => 0, 'message' => "$amount is greater than principal!"]);
                    }
                    $source->principalLeft -= $amount;
                }
                if ($option == 3) {
                    if ($source->InterestLeft < $amount) {
                        return json_encode(['status' => 0, 'message' => "$amount is greater than Interest ($source->interestLeft) !"]);
                    }
                    $source->InterestLeft -= $amount;
                }
                if ($option == 4) {
                    $cryptLoan = $request->loanID;
                    $schedule  = json_decode($this->loanRepaymentBreakDown($cryptLoan, new Request), true);
                    $fixRate   = $schedule['repaymentTable'][0]['EachRepayment'] - $schedule['repaymentTable'][0]['principalRepayment'];
                    $rate      = ($fixRate / $schedule['repaymentAmut']) * 100;
                    $interest  = ($rate / 100 * $amount);
                    $pricipal  = $amount - $interest;
                    if ($source->interestLeft < $interest) {
                        return json_encode(['status' => 0, 'message' => "$amount is greater than Interest!"]);
                    }
                    if ($source->principalLeft < $pricipal) {
                        return json_encode(['status' => 0, 'message' => "$amount is greater than Principal!"]);
                    }
                    $source->interestLeft  -= $interest;
                    $source->principalLeft -= $pricipal;
                }
                $source->EachRepayment = ($source->interestLeft + $source->principalLeft) / $source->duration;
                $source->save();
                return json_encode(['status' => 1, 'message' => "$amount as been ritten off your loan!"]);

            }
            $source->loan_status_id = 7; //written off loan
            $source->save();
            return json_encode(['status' => 1, 'message' => "Loan has been written off!"]);
        } else {
            return json_encode(['status' => 0, 'message' => 'Invalid Loan ID or loan not active!']);
        }
    }
    public function liquidate(Request $request)
    {
        if (! cando('loan-liquidate|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        $loanID = Crypt::decryptString($request->loanID);
        $source = loan::join('users', 'users.id', '=', 'loans.user_id')->where('users.company_id', auth()->user()->company_id)->where(['loans.id' => $loanID, 'loans.loan_status_id' => 1])->select('loans.*')->first();
        if ($source) {
            $source->loan_status_id = 8; //liquidate off loan
            $source->save();
            return json_encode(['status' => 1, 'message' => "Loan has been liquidated!"]);
        } else {
            return json_encode(['status' => 0, 'message' => 'Invalid Loan ID or loan not active!']);
        }
    }
    public function PayLoan($company_name, $id)
    {
        if (! cando('view-loan-repayment|root')) {
            return abort(403);
        }

        $loanID   = Crypt::decryptString($id);
        $infoData = loan::where(['id' => $loanID])->first();
        $auth     = auth()->user();
        if ($infoData->loan_status_id == 1 && $infoData->user->company_id == $auth->company_id) {
            $data = [
                'category_name'    => 'loan',
                'page_name'        => 'pay_loan',
                'page_menu'        => 'pay_loan',
                'has_scrollspy'    => 0,
                'scrollspy_offset' => '',
                'loanData'         => $infoData,
                'auth'             => $auth,
                'loanID'           => $id,
                'banks'            => banks::all(),
                'CompanyValut'     => CompanyValut::where('company_id', $auth->company_id)->get(),
                'alt_menu'         => 0,
                'infoData'         => $infoData,
                'branch'           => branch::where(['company_id' => $auth->company_id])->get(),
            ];
            return view('company.loan.pay_loan')->with($data);
        } else {
            return redirect()->route('company.complete-loan', $auth->company->short_name)->with('success', 'You have been redirected to the completed loan page because this loan is marked as complete!!');
        }
        abort(404);
    }
    public function getLoanCurrentRepayment($data, $AmountAbtToPay)
    {
        try {
            $principalLeft  = 0;
            $repaymentOrder = $data->LoanProduct->orderOfRepayment;
            $cryptLoan      = Crypt::encryptString($data->id);
            $schedule       = json_decode($this->loanRepaymentBreakDown($cryptLoan, new Request), true);
            $principalLeft  = $data->principalLeft;
            $interestLeft   = $data->InterestLeft;
            $rateProduct    = $data->rate;
            $fixRate        = $schedule['repaymentTable'][0]['EachRepayment'] - $schedule['repaymentTable'][0]['principalRepayment'];
            $rate           = ($fixRate / $schedule['repaymentAmut']) * 100;
            $paymentP       = $amountToPayP       = 0;
            $paymentI       = $amountToPayI       = 0;
            $log            = '';
            if ($rateProduct < 1 || ! $rateProduct) {
                $log = "Principal $principalLeft - amount $AmountAbtToPay) = " . ($principalLeft - $AmountAbtToPay);
                return (['status' => 1, 'loanid' => $data->ref, 'Pleft' => ($principalLeft - $AmountAbtToPay), 'Ileft' => 0, 'paymentP' => $AmountAbtToPay, 'paymentI' => 0, 'message' => $log]);
            }
            if ($repaymentOrder == 2) {
                if ($principalLeft >= $AmountAbtToPay) {
                    $paymentP        = $AmountAbtToPay;
                    $principalLeft  -= $AmountAbtToPay;
                    $AmountAbtToPay  = 0;
                } else {
                    $paymentP        = $principalLeft;
                    $AmountAbtToPay -= $principalLeft;
                    $principalLeft   = 0;
                }
                if ($AmountAbtToPay > 0) {
                    if ($interestLeft >= $AmountAbtToPay) {
                        $paymentI        = $AmountAbtToPay;
                        $interestLeft   -= $AmountAbtToPay;
                        $AmountAbtToPay  = 0;
                    } else {
                        $paymentI        = $interestLeft;
                        $AmountAbtToPay -= $interestLeft;
                        $interestLeft    = 0;
                    }
                }
                $log .= " I Paid =$paymentI  P paid=$paymentP<br> ";
                return (['status' => 1, 'loanid' => $data->ref, 'Pleft' => $principalLeft, 'Ileft' => $interestLeft, 'paymentP' => $paymentP, 'paymentI' => $paymentI, 'message' => $log]);
            } else if ($repaymentOrder == 3) {
                if ($interestLeft >= $AmountAbtToPay) {
                    $paymentI        = $AmountAbtToPay;
                    $interestLeft   -= $AmountAbtToPay;
                    $AmountAbtToPay  = 0;
                } else {
                    $paymentI        = $interestLeft; //$AmountAbtToPay - $interestLeft;
                    $AmountAbtToPay -= $interestLeft;
                    $interestLeft    = 0;

                }
                if ($AmountAbtToPay > 0) {
                    if ($principalLeft >= $AmountAbtToPay) {
                        $paymentP        = $AmountAbtToPay;
                        $principalLeft  -= $AmountAbtToPay;
                        $AmountAbtToPay  = 0;
                    } else {
                        $paymentP        = $principalLeft;
                        $AmountAbtToPay -= $principalLeft;
                        $principalLeft   = 0;
                    }
                }
                $log .= " I Paid =$paymentI  P paid=$paymentP<br> ";
                return (['status' => 1, 'loanid' => $data->ref, 'Pleft' => $principalLeft, 'Ileft' => $interestLeft, 'paymentP' => $paymentP, 'paymentI' => $paymentI, 'message' => $log]);
            } else {
                $paymentI  = ($rate / 100) * $AmountAbtToPay;
                $log      .= "($rate / 100) * $AmountAbtToPay <br> ";
                $log      .= "amount I =$paymentI <br> ";
                if ($interestLeft < $paymentI) {
                    $paymentI  = $interestLeft;
                    $log      .= "swap interest to balance amount I =$paymentI <br> ";
                }

                $paymentP  = $AmountAbtToPay - $paymentI;
                $log      .= " $AmountAbtToPay - $paymentI<br> ";
                $log      .= " amount P =$paymentP <br> ";
                if ($principalLeft < $paymentP) {
                    $paymentP  = $principalLeft;
                    $log      .= "swap Principal to balance amount P =$paymentP <br> ";
                    $left      = $AmountAbtToPay - $principalLeft;
                    if ($interestLeft >= $left) {
                        $paymentI = $left;
                    }
                }

                $log           .= "P balance= $amountToPayP<br> ";
                $log           .= "I balance= $amountToPayI<br> ";
                $amountToPayP  = $paymentP;
                $amountToPayI  = $paymentI;
                $principalLeft -= $amountToPayP;
                $interestLeft  -= $amountToPayI;

                $log .= "Pleft = $principalLeft  Ileft = $interestLeft  <br> loanref = $data->ref ";
                return (['status' => 1, 'loanid' => $data->ref, 'Pleft' => $principalLeft, 'Ileft' => $interestLeft, 'paymentP' => $amountToPayP, 'paymentI' => $amountToPayI, 'message' => $log]);
            }
        } catch (\Throwable $e) {
            // Do something with your exception
            return (['message' => $e->getMessage(), 'status' => 0]);
        }
    }
    public function generalpayloan($request)
    {
        try {

            $loanID         = Crypt::decryptString($request->loanID);
            $amount         = $request->amount;
            $method         = $request->method;
            $repaymentExist = $request->repaymentExist ?? null;
            $payment        = $request->payment ? $request->payment : date('Y-m-d');
            $accountType    = $request->accountType;
            if ($accountType) {
                $wb_account_details = wb_account_details::where('id', $accountType)->first();
                if (! $wb_account_details) {
                    return (['status' => 0, 'message' => 'Invalid Account']);
                }

                $userid   = $wb_account_details->user_id;
                $ismyloan = loan::where(['user_id' => $userid, 'id' => $loanID])->count();
                if ($ismyloan == 0) {
                    return (['status' => 0, 'message' => 'User doesnt match loan']);
                }

            }
            $auth = null;
            if (auth()->check()) {
                if ($request->loan_initiator_id) {
                    $auth = User::where('id', $request->loan_initiator_id)->first();
                } else {
                    $auth = auth()->user();
                }
            } else {
                $auth = $request->companyOwner;
            }
            if (! $auth) {
                return (['status' => 0, 'message' => 'User not found']);
            }
            $isAuthloan = loan::where(['user_id' => $auth->id, 'id' => $loanID])->count();
            if ($request->companyVault) {
                $CompanyValut = CompanyValut::where(['id' => $request->companyVault, 'company_id' => $auth->company_id])->first();
                if ($CompanyValut->value < $amount) {
                    return ["status" => 0, "message" => "Insufficient vualt balance!"];
                }
            }

            if ($request->instrumentCode) {
                if (transactions::where(['instrumentCode' => $request->instrumentCode])->count() > 0) {
                    return ["status" => 0, "message" => "Transaction Instrument Code Already Exist!"];
                }
                if (LoanRepayment::where(['instrumentCode' => $request->instrumentCode])->count() > 0) {
                    return ["status" => 0, "message" => "Transaction Instrument Code Already Exist!"];
                }
            }

            if (auth()->check()) {
                if ($request->created_at == null && ! cando('root|ignore-pending-repayment') && ($isAuthloan == 0)) {
                    if (PendingRepayment::where(['loan_id' => $loanID, 'status_id' => 2])->count() > 0) {
                        return (['status' => 0, 'message' => 'Request Already Sent !']);
                    }
                    $PendingRepayment              = new PendingRepayment;
                    $PendingRepayment->amount      = $amount;
                    $PendingRepayment->loan_id     = $loanID;
                    $PendingRepayment->accountType = $accountType;
                    $PendingRepayment->payment     = $payment;
                    $PendingRepayment->method      = $method;
                    $PendingRepayment->created_by  = $auth->id;
                    $PendingRepayment->status_id   = 2;
                    $PendingRepayment->company_id  = $auth->company_id;
                    $PendingRepayment->save();
                    return (['status' => 1, 'message' => 'Request Sent !']);
                }
            }
            // return DB::transaction(function() use ($request,$auth,$repaymentExist) {
            DB::beginTransaction();
            $initiator = $auth->id;
            if ($request->created_by != null) {
                $initiator = $request->created_by;
            }
            $loanID      = Crypt::decryptString($request->loanID);
            $amount      = $request->amount;
            $method      = $request->method;
            $payment     = $request->payment ? $request->payment : date('Y-m-d');
            $accountType = $request->accountType;
            $infoData    = loan::where(['id' => $loanID, 'loan_status_id' => 1])->first();
            if (! $infoData) {
                return (['status' => 0, 'message' => 'Data error!']);
            }
            $loanBalBefore = $infoData->loanBalance;
            if ($infoData->loanBalance < $amount) {
                return (['status' => 0, 'message' => 'Deposit amount is greater than loan balance!']);
            }
            if ($amount < 0) {
                return (['status' => 0, 'message' => 'Deposit amount should be greater than 0!']);
            }
            if (! $infoData) {
                return (['status' => 0, 'message' => 'This loan is not active!']);
            }

            if ($method == 'cash' || $method == 'remita') {
                $AmtInterest        = ($infoData->rate / 100) * $amount;
                $principal4rAmount  = $amount - $AmtInterest;
                $loancharges        = $this->LoanMethodPercentReturn($principal4rAmount, $AmtInterest, $infoData->LoanFee, $infoData->LoanFeeMethod, $infoData->mrgtFee, $infoData->mrgtFeeMethod);
                $data               = [
                    'loandata'       => $infoData,
                    'loanid'         => $loanID,
                    'paymentDate'    => $payment,
                    'balanceBefore'  => 0,
                    'amount'         => $amount,
                    'account_typeid' => 0,
                    'branch_id'      => $auth->branch_id,
                    'balanceAfter'   => 0,
                    'backdate'       => $payment,
                    'loanFee'        => $loancharges['loanFee'],
                    'mrgFee'         => $loancharges['mrgFee'],
                    'method'         => $method,
                    'loanBalance'    => ($loanBalBefore -= $amount),
                    'initiator'      => $initiator,
                    'companyVault'   => $request->companyVault,
                    'instrumentCode' => $request->instrumentCode,
                    'repaymentExist' => $repaymentExist,
                ];

                $pay = $this->payloandDeposit($data, $auth);
                if ($pay['status']) {
                    //$infoData->loanBalance -= $amount;
                    $infoData->principalLeft = $pay['Pleft'];
                    $infoData->InterestLeft  = $pay['Ileft'];
                    $infoData->loanBalance   = $pay['Ileft'] + $pay['Pleft'];
                    if (($pay['Ileft'] + $pay['Pleft']) <= 0) {
                        $infoData->loan_status_id = 10;
                    }
                    $infoData->save();
                    $appMSG = "Your repayment of loan of $amount via ($method is acknowledged and your current loan balance is $infoData->loanBalance";
                    if (! $repaymentExist) {
                        sendSMS($infoData->user_id, "$appMSG", true, true, 'Loan Repayment');
                    }
                    DB::commit();
                    return (['status' => 1, 'message' => 'Cash Payment Successful!', 'redirectlink' => @$pay['redirectlink']]);
                } else {
                    DB::rollBack();
                    return (['status' => 0, 'message' => $pay['message'], 'data' => $pay]);
                }
            } elseif ($method == 'wallet' || $method == 'mobile') {
                $user_wallet   = user_wallet::where('wb_account_detail_id', $accountType)->first();
                $balanceBefore = 0;
                $balanceBefore = $user_wallet ? $user_wallet->balance : 0;
                if ($balanceBefore < $amount && ! $repaymentExist) {
                    return (['status' => 0, 'message' => 'Insufficient balance!']);
                }
                $balanceAfter             = $balanceBefore - $amount;
                $savingTypeMinimumBalance = $user_wallet ? $user_wallet->wb_account_details->minimum_balance : 0;
                if ($savingTypeMinimumBalance != null && $savingTypeMinimumBalance > 0 && ! $repaymentExist) {
                    if ($balanceAfter < $savingTypeMinimumBalance && ! $repaymentExist) {
                        return ["status" => 0, "message" => "Minimum balance for this product is $savingTypeMinimumBalance"];
                    }
                }
                $AmtInterest       = ($infoData->rate / 100) * $amount;
                $principal4rAmount = $amount - $AmtInterest;
                $loancharges       = $this->LoanMethodPercentReturn($principal4rAmount, $AmtInterest, $infoData->LoanFee, $infoData->LoanFeeMethod, $infoData->mrgtFee, $infoData->mrgtFeeMethod);
                $data              = [
                    'loandata'       => $infoData,
                    'loanid'         => $loanID,
                    'paymentDate'    => $payment,
                    'balanceBefore'  => $balanceBefore,
                    'amount'         => $amount,
                    'account_typeid' => $accountType,
                    'branch_id'      => $auth->branch_id,
                    'balanceAfter'   => $balanceAfter,
                    'backdate'       => $payment,
                    'loanFee'        => $loancharges['loanFee'],
                    'mrgFee'         => $loancharges['mrgFee'],
                    'method'         => 'wallet',
                    'loanBalance'    => ($loanBalBefore -= $amount),
                    'initiator'      => $initiator,
                    'companyVault'   => $request->companyVault,
                    'instrumentCode' => $request->instrumentCode,
                    'repaymentExist' => $repaymentExist,
                ];
                $pay = $this->payloandDeposit($data, $auth);
                if ($pay['status']) {

                    $infoData->principalLeft = $pay['Pleft'];
                    $infoData->InterestLeft  = $pay['Ileft'];
                    $infoData->loanBalance   = $pay['Ileft'] + $pay['Pleft'];
                    if (($pay['Ileft'] + $pay['Pleft']) <= 0) {
                        $infoData->loan_status_id = 10;
                    }
                    $infoData->save();
                    if (! $repaymentExist) { //if its not a resetting repayment call
                        $user_wallet->balance  = $balanceAfter;
                        $user_wallet->save();
                        $BranchWallet           = BranchWallet::where('branch_id', $auth->branch_id)->first();
                        $BranchWallet->balance -= $amount;
                        $BranchWallet->save();
                        $appMSG  = "Your repayment of loan of $amount via (Wallet is acknowledged and your current loan balance is $infoData->loanBalance";
                        // sendSMS($infoData->user_id, "$appMSG", true, true, 'Loan Repayment');
                    }
                    DB::commit();
                    return (['status' => 1, 'message' => $pay['message'], 'redirectlink' => @$pay['redirectlink']]);
                } else {
                    DB::rollBack();
                    return (['status' => 0, 'message' => $pay['message']]);
                }
            } else {
                return (['status' => 0, 'message' => 'Method no allowed!' . $method]);
            }
            // });
        } catch (\Throwable $e) {
            // Do something with your exception
            DB::rollBack();
            return (['message' => $e->getMessage() . $e->getLine(), 'status' => 0]);
        }
    }
    public function DoPayLoan(Request $request)
    {
        if (! cando('loan-repayment|root')) {
            return json_encode(['status' => 0, 'message' => 'Access Denied!']);
        }

        return json_encode($this->generalpayloan($request));
    }
    public function payloandDeposit($data, $auth)
    {
        try {

            // return DB::transaction(function() use ($data,$auth) {
            $loanid               = $data['loanid'];
            $paymentDate          = $data['paymentDate'];
            $balanceBefore        = $data['balanceBefore'];
            $amount               = $data['amount'];
            $account_typeid       = $data['account_typeid'];
            $branch_id            = $data['branch_id'];
            $balanceAfter         = $data['balanceAfter'];
            $backdate             = $data['backdate'];
            $loanFee              = $data['loanFee'];
            $mrgFee               = $data['mrgFee'];
            $loandata             = $data['loandata'];
            $initiator            = $data['initiator'];
            $companyVault         = $data['companyVault'];
            $instrumentCode       = $data['instrumentCode'];
            $updatePayment        = $data['repaymentExist'] ? $data['repaymentExist'] : 0;
            $loanPaymentStateData = $this->getLoanCurrentRepayment($loandata, $amount);
            if (empty($loanPaymentStateData)) {
                return (['message' => 'Fata Error empty response', 'status' => 0, 'resp' => $loanPaymentStateData]);

            }

            $principalLeft            = $loanPaymentStateData['Pleft'];
            $interestLeft             = $loanPaymentStateData['Ileft'];
            $loanPayment              = $updatePayment ? LoanRepayment::where('id', $updatePayment)->first() : new LoanRepayment;
            $loanPayment->loan_id     = $loanid;
            $loanPayment->paymentDate = $paymentDate ?? now("Y-m-d");
            $loanPayment->amount      = $amount;
            $loanPayment->branch_id   = $branch_id;
            $loanPayment->mgrtFee     = $mrgFee;
            $loanPayment->loanFee     = $loanFee;
            $loanPayment->P           = $loanPaymentStateData['paymentP'];
            $loanPayment->I           = $loanPaymentStateData['paymentI'];
            $loanPayment->remark      = $loanPaymentStateData['message'];
            $loanPayment->payment_ref = str_shuffle(time());
            $loanPayment->method      = ($data['method'] == 'wallet') ? 2 : 1;
            if ($updatePayment == 0) {
                $loanPayment->initiator_id = $auth->id;
                $loanPayment->created_by   = $initiator;
            }
            $loanPayment->balance        = $data['loanBalance'];
            $loanPayment->instrumentCode = $instrumentCode ?? generate_txnID();

            $loanPayment->save();
            $link  = url('/company/loan/loan-receipt/' . $loanPayment->id);
            $txnID = null;
            //loandata

            if ($data['method'] == 'wallet' && $updatePayment == 0) {
                $loanPayment->method = 2;
                $loanPayment->save();
                $loanTXN = transactions::create([
                    "balance_before"         => $balanceBefore,
                    "amount"                 => $amount,
                    "description"            => "Loan repayment of $amount",
                    "account_id"             => $account_typeid,
                    "branch_id"              => $auth->branch_id,
                    "transaction_status_id"  => 1,
                    "transaction_reference"  => generate_txnID(),
                    "transaction_type_id"    => 56, //Loan repayment 56
                    "credit_debit_type_id"   => 2,  //debit 2
                    "transaction_remarks_id" => 2,  //success 2
                    "balance_after"          => $balanceAfter,
                    "created_date"           => $backdate,
                    "created_by"             => $auth->id,
                    "loan_repayment_id"      => $loanPayment->id,
                    'instrumentCode'         => $instrumentCode,
                ]);
                $txnID = $loanTXN->id;
                if (! $loanTXN) {
                    LoanRepayment::where('id', $loanPayment->id)->delete();
                    return (['message' => 'Fata Error' . $loanPayment->id, 'status' => 0]);
                }
            }

            if ($updatePayment == 0) {
                $userinfo = $auth;

                if ($loanFee > 0) {
                    fundVault(['type' => 'loan_fee', 'value' => $loanFee, 'company_id' => $userinfo->company_id, 'created_by' => $userinfo->id, 'branch_id' => $userinfo->branch_id, 'transaction_id' => $txnID]);
                }
                if ($mrgFee > 0) {

                    fundVault(['type' => 'loan_management_fee', 'value' => $mrgFee, 'company_id' => $userinfo->company_id, 'created_by' => $userinfo->id, 'branch_id' => $userinfo->branch_id, 'transaction_id' => $txnID]);
                }

                if ($companyVault) {
                    if (isset($auth->company->Transactionsettings)) {
                        if ($auth->company->Transactionsettings->view_vault_on_txn == 1) {
                            if (! fundVaultByID(['vaultID' => $companyVault, 'value' => 0 - $amount, 'company_id' => $userinfo->company_id, 'created_by' => $userinfo->id, 'branch_id' => $userinfo->branch_id, 'transaction_id' => $txnID])) {
                                return (['message' => "Error Paying Loan", 'status' => 0]);
                            }
                        }
                    }
                }

            }

            return (['message' => 'Loan Wallet Payment Successful!', 'status' => 1, 'Pleft' => $principalLeft, 'Ileft' => $interestLeft, 'redirectlink' => $link]);
            // });
        } catch (\Throwable $e) {
            // Do something with your exception
            return (['message' => $e->getMessage() . $e->getLine(), 'status' => 0]);
        }

    }
    public function loanRepaymentBreakDown($loanID, Request $request)
    {
        try {
            $loanID              = Crypt::decryptString($loanID);
            $missloan            = 0;
            $lastmissloan        = '';
            $infoData            = loan::where(['id' => $loanID])->where('user_id', '!=', 0)->first();
            $EachRepayment       = $infoData->EachRepayment;
            $LoanProductDecima   = $infoData->LoanProduct->approximate_status;
            $Duration            = $infoData->duration;
            $DurationPeriod      = $infoData->DurationPeriod;
            $maturity            = date("Y-m-d", strtotime($infoData->grace_at . "+$Duration $DurationPeriod"));
            $period              = $infoData->PeriodID;
            $loanRepaymentPeriod = convertLY($period->name);
            $repayPeriod         = '+' . $loanRepaymentPeriod['value'] . ' ' . $loanRepaymentPeriod['key'];
            $principal           = $infoData->principal;
            $loan_balanceY       = $loan_balance       = $loanBalance       = $infoData->loanBalance;
            $rate                = $infoData->rate;
            $graceDate           = $infoData->grace_at;
            $loanFee             = $infoData->loanFee;
            $paidrec             = LoanRepayment::join('users', 'users.id', '=', 'loan_repayments.created_by')->select('loan_repayments.*', 'users.first_name', 'users.last_name')->where(['loan_repayments.loan_id' => $loanID, 'loan_repayments.status_id' => 1]);
            if ($request->fromDate != null && $request->toDate != null) {
                if ($request->fromDate != 'undefined' && $request->toDate != 'undefined') {
                    $paidrec = $paidrec->whereBetween('paymentDate', [$request->fromDate, $request->toDate]);
                } //
            }
            $LoanRepayment          = $paidrec->get();
            $LoanRepaymentTotalPaid = $paidrec->sum('amount');
            $looper                 = calculatePeriodByPeriod($period->name, $DurationPeriod) * $Duration;
            $IX                     = $statusType                     = 0;
            $today                  = date('Y-m-d');
            $backTotalLoanBalance   = 0;
            $currentRepayStatus     = 0;
            if ($infoData->LoanType == 1) {
                $statusType          = 1;
                $Principel_Interest  = $principal + ($principal * ($rate / 100));
                $j                   = 0;
                $newdate             = $infoData->grace_at;
                $payment_amountX     = $pendue     = 0;
                $loanba              = $loanBalance              = $loanBalance / $looper;
                $totalLoanBalance    = 0;
                $principal_reduction = $principal; ///$looper;
                $IT                  = $Principel_Interest - $principal;
                $PIRate              = $EachRepayment;
                $totalLoanBalance    = $EachRepayment;
                $totalLoanDue        = 0;
                for ($i = 0; $i < $looper; $i++) {
                    $daysmissed          = 0;
                    $due                 = ($principal / $looper);
                    $repayI              = $IT / $looper;
                    $RepaymentAmt        = $pI        = ($due + $repayI);
                    $payment_amountX    += $repayI;
                    $currentOutstanding  = 0;
                    $j                  += 1;
                    $pendue             += ($loanBalance / $looper);
                    $newdate             = date("Y-m-d", strtotime($newdate . "$repayPeriod"));
                    $statusTag           = '';
                    $outstanding         = $totalLoanBalance;
                    if ($LoanRepaymentTotalPaid >= round($totalLoanBalance, $LoanProductDecima)) {
                        //success
                        $statusTag          = "<b><i class='fa fa-check-circle' style='color:green;'></i> $totalLoanBalance</b>";
                        $currentRepayStatus = 1;
                        $outstanding        = $totalLoanBalance;
                    } elseif ($LoanRepaymentTotalPaid < $totalLoanBalance) {
                        $statusTag = 'badge-secondary';
                        if ($today > $newdate) {
                            $missloan           = $totalLoanBalance - $LoanRepaymentTotalPaid;
                            $lastmissloan       = $newdate;
                            $daysmissed         = dateDiffInDays($today, $newdate);
                            $currentOutstanding = $totalLoanBalance - $LoanRepaymentTotalPaid;
                            $statusTag          = "<i class='fa fa-times-circle text-danger' ></i> $totalLoanBalance <b>pay=></b>" . ($totalLoanBalance - $LoanRepaymentTotalPaid);
                            $currentRepayStatus = 3;
                            $outstanding        = $totalLoanBalance - $LoanRepaymentTotalPaid;
                        } else {
                            $overflow           = ($backTotalLoanBalance < $LoanRepaymentTotalPaid) ? '=> ' . ($LoanRepaymentTotalPaid - $backTotalLoanBalance) : '';
                            $statusTag          = "<b><i class='fa fa-spinner fa-spin text-warning' ></i></b> $totalLoanBalance $overflow";
                            $currentRepayStatus = 2;
                            $outstanding        = $totalLoanBalance - $LoanRepaymentTotalPaid;

                        }
                    }
                    $repay[]              = ['totalLoanBalance' => $totalLoanBalance, 'outstandingpay' => $outstanding, 'EachRepayment' => $EachRepayment, 'currentRepayStatus' => $currentRepayStatus, 'loanBalanceRecurring' => $statusTag, 'currentOutstanding' => $currentOutstanding, 'daysmissed' => $daysmissed, 'loanBalance' => round($PIRate, $LoanProductDecima), 'tag' => 'class="table-success "', 'principalRepayment' => round($due, $LoanProductDecima), 'principal_reduction' => round($principal_reduction, $LoanProductDecima), 'loan_balance' => round($loan_balance, $LoanProductDecima), 'totalDue' => round($payment_amountX, $LoanProductDecima), 'sn' => $j, 'fee' => $infoData->LoanFee . '%', 'description' => 'Repayment', 'pendingDue' => $pendue, 'repaymentDateDueloan' => $newdate, 'repaymentDate' => $newdate, 'principal' => round($principal, $LoanProductDecima), 'principal_return' => round($due, $LoanProductDecima), 'PI' => round($pI, $LoanProductDecima), 'I' => round($IT, $LoanProductDecima), 'duration' => "$period->name-$j", 'payment_amount' => round($repayI, $LoanProductDecima) . " - ($rate%)"];
                    $loanBalance          += $loanba;
                    $totalLoanBalance     += $PIRate;
                    $backTotalLoanBalance += $PIRate;
                    $loan_balance         -= $loanba;
                    $principal_reduction  -= $due;
                    $IX                   += $IT;
                }
                $totalLoanBalance = $infoData->loanBalance;
                $repay[]          = ['maturity' => $newdate, 'loanBalanceRecurring' => ($totalLoanBalance), 'tag' => 'class="bg-warning"', 'principalRepayment' => '<b>' . round($due, $LoanProductDecima) . '<b>', 'sn' => '#', 'fee' => '-', 'description' => 'Flat Balance', 'loanBalance' => '<b>' . round($totalLoanBalance, $LoanProductDecima) . '</b>', 'totalDue' => '<b>' . round($payment_amountX, $LoanProductDecima) . '</b>', 'repaymentDate' => '<b>Summery</b>', 'loan_balance' => '<b>' . round($loan_balanceY, $LoanProductDecima) . '</b>', 'principal_reduction' => "<b>" . round($principal, $LoanProductDecima) . "</b>", 'I' => "<b>" . round($IT, $LoanProductDecima) . "</b>", 'duration' => '<b>--</b>', 'rate' => '<b>--</b>', 'payment_amount' => round($payment_amountX, $LoanProductDecima)];
            } else {
                $statusType          = 2;
                $repay               = [];
                $j                   = 0;
                $IT                  = 0;
                $principal_reduction = 0;
                $loan_balance        = $principal;
                $due                 = ($principal / $looper);

                $newdate        = $infoData->grace_at;
                $emiInterest    = $this->EMI($principal, $rate, $looper);
                $RepaymentAmt   = $emiInterest + $principal / $looper;
                $emiInterestPER = $emiInterest / $looper;
                $loanba         = $loanBalance         = $loanBalance / $looper;
                $Ibal           = 0;
                // $LoanInterestPrincipal = $emiInterest + $principal ;
                $IX = $payment_amountX = $principal_reductionX = $principal_reduction = 0;
                for ($i = 0; $i < $looper; $i++) {
                    $newdate           = date("Y-m-d", strtotime($newdate . "$repayPeriod"));
                    $j                += 1;
                    $payment_amountX  += $RepaymentAmt;
                    $statusTag         = '';
                    $totalLoanBalance  = $payment_amountX;
                    $outstanding       = $totalLoanBalance;

                    if ($LoanRepaymentTotalPaid >= round($totalLoanBalance, $LoanProductDecima)) {
                        //success
                        $currentRepayStatus = 1;
                        $outstanding        = $totalLoanBalance;

                        $statusTag = "<b><i class='fa fa-check-circle' style='color:green;'></i> $totalLoanBalance</b>";
                    } elseif ($LoanRepaymentTotalPaid < $totalLoanBalance) {
                        $statusTag = 'badge-secondary';
                        if ($today > $newdate) {
                            $currentRepayStatus = 3;
                            $missloan           = $totalLoanBalance - $LoanRepaymentTotalPaid;
                            $lastmissloan       = $newdate;
                            $statusTag          = "<i class='fa fa-times-circle text-danger' ></i> $totalLoanBalance <b>pay=></b>" . ($totalLoanBalance - $LoanRepaymentTotalPaid);
                            $outstanding        = $totalLoanBalance - $LoanRepaymentTotalPaid;
                        } else {
                            $currentRepayStatus = 2;
                            $overflow           = ($backTotalLoanBalance < $LoanRepaymentTotalPaid) ? '=> ' . ($LoanRepaymentTotalPaid - $backTotalLoanBalance) : '';
                            $statusTag          = "<b><i class='fa fa-spinner fa-spin text-warning' ></i></b> $totalLoanBalance $overflow";
                            $outstanding        = $totalLoanBalance;
                        }
                    }
                    $repay[]              = ['totalLoanBalance' => $totalLoanBalance, 'outstandingpay' => $outstanding, 'EachRepayment' => $EachRepayment, 'currentRepayStatus' => $currentRepayStatus, 'loanBalanceRecurring' => $statusTag, 'sn' => $j, 'IX' => round($IX, $LoanProductDecima), 'loanBalance' => round($loanBalance, $LoanProductDecima), 'fee' => (($loanFee) ? $loanFee : 0) . '%', 'EMI' => $emiInterestPER, 'description' => 'Repayment', 'repaymentDateDueloan' => $newdate, 'repaymentDate' => $newdate, 'loan_balance' => round($loan_balance, $LoanProductDecima), 'principal_reduction' => round($principal_reduction, $LoanProductDecima), 'I' => round($IT, $LoanProductDecima), 'duration' => "$period->name-$j", 'rate' => "$rate%", "payment_amount" => round($RepaymentAmt, $LoanProductDecima), 'principalRepayment' => round($due, $LoanProductDecima)];
                    $IT                   = $emiInterest;
                    $principal_reduction  = $RepaymentAmt - $IT;
                    $loan_balance         -= $principal_reduction;
                    $principal_reductionX += $principal_reduction;
                    $loanBalance          += $loanba;
                    $Ibal                 += $loanba;
                    $IX                   += $IT;
                    $backTotalLoanBalance += $RepaymentAmt;
                }
                $loan_balance = $infoData->loanBalance;
                $repay[]      = ['loanBalanceRecurring' => '<b>Paid: ' . $LoanRepaymentTotalPaid . '</b>', 'IX' => '<b>' . round($IX, $LoanProductDecima) . '</b>', 'sn' => '#', 'fee' => '-', 'description' => '<b>Reducing Balance</b>', 'loanBalance' => '<b>' . round($Ibal, $LoanProductDecima) . '</b>', 'totalDue' => '<b>' . round($payment_amountX, $LoanProductDecima) . '</b>', 'repaymentDate' => '<b>Summery</b>', 'loan_balance' => '<b>' . round($loan_balance, $LoanProductDecima) . '</b>', 'principal_reduction' => "<b>" . round($principal_reductionX, $LoanProductDecima) . "</b>", 'I' => "<b>" . round($IX, $LoanProductDecima) . "</b>", 'duration' => '<b>--</b>', 'rate' => '<b>--</b>', 'payment_amount' => '<b>' . round($payment_amountX, $LoanProductDecima) . '</b>'];
            }
            return json_encode(['id' => $infoData->id, 'lastmissed' => $lastmissloan, 'missed' => $missloan, 'maturity' => $newdate, 'repaymentAmut' => $infoData->EachRepayment, 'totalPaid' => $LoanRepaymentTotalPaid, 'type' => $statusType, 'repaymentTable' => $repay, 'repayment' => $LoanRepayment, 'Pleft' => '<b>Principal Left: ' . $infoData->user->company->currency->code . $infoData->principalLeft . '</b>', 'Ileft' => '<b>Interest Left: ' . $infoData->user->company->currency->code . $infoData->InterestLeft . '</b>', 'IPleft' => '<b>Total Left: ' . $infoData->user->company->currency->code . $infoData->loanBalance . '</b>', 'IPLeftNo' => $infoData->loanBalance]);
        } catch (Exception $e) {

        }
    }
    public function DeleteRepayment($ID)
    {
        if (! cando('loan-delete-repayment|root')) {
            return redirect()->back()->with('fail', 'Access Denied!');
        }

        try {
            return DB::transaction(function () use ($ID) {
                $LoanRepayment = LoanRepayment::where(['payment_ref' => $ID, 'status_id' => 1])->first();
                if ($LoanRepayment) {
                    if ($LoanRepayment->loan->user->company_id != auth()->user()->company_id) {
                        return redirect()->back()->with('fail', 'Access Denied (005)!');
                    }
                    $loanID = $LoanRepayment->id;
                    if ($LoanRepayment->method == 2) {
                        $transHistory = transactions::where(["loan_repayment_id" => $LoanRepayment->id])->first();
                        if ($transHistory) {
                            $account_id            = $transHistory->account_id;
                            $user_wallet           = user_wallet::where('wb_account_detail_id', $account_id)->first();
                            $user_wallet->balance += $transHistory->amount;
                            $user_wallet->save();
                            $BranchWallet           = BranchWallet::where('branch_id', $transHistory->branch_id)->first();
                            $BranchWallet->balance += $transHistory->amount;
                            $BranchWallet->save();
                            transactions::where(["loan_repayment_id" => $LoanRepayment->id])->delete();
                        } else {
                            return redirect()->back()->with('fail', 'Repayment history Error!' . $LoanRepayment->id);
                        }
                    }
                    $loan                 = loan::where('id', $LoanRepayment->loan_id)->first();
                    $loan->loanBalance    += $LoanRepayment->amount;
                    $loan->principalLeft  += $LoanRepayment->P;
                    $loan->InterestLeft   += $LoanRepayment->I;
                    $loan->loan_status_id = 1;
                    $loan->save();
                    $LoanRepayment->status_id = 0;
                    $LoanRepayment->save();
                    // LoanRepayment::where('id',$loanID)->delete();
                    return redirect()->back()->with('success', 'Repayment Deleted');
                } else {
                    return redirect()->back()->with('fail', 'Repayment Delete Failed!');
                }
            });
        } catch (\Throwable $e) {
            // Do something with your exception
            return redirect()->back()->with('fail', $e->getMessage() . $e->getLine());
        }
    }
    public function rollbackRepayment($ID)
    {
        if (! cando('loan-delete-repayment|root')) {
            return (['status' => 0, 'message' => 'Access Denied!']);
        }

        try {
            return DB::transaction(function () use ($ID) {
                $LoanRepayment = LoanRepayment::where(['payment_ref' => $ID, 'status_id' => 1])->first();
                if ($LoanRepayment) {
                    if ($LoanRepayment->loan->user->company_id != auth()->user()->company_id) {
                        return (['status' => 0, 'message' => 'Access Denied! (005)']);
                    }
                    $loanID = $LoanRepayment->id;
                    if ($LoanRepayment->method == 2) {
                        $transHistory = transactions::where(["loan_repayment_id" => $LoanRepayment->id])->first();
                        if ($transHistory) {
                            $account_id            = $transHistory->account_id;
                            $user_wallet           = user_wallet::where('wb_account_detail_id', $account_id)->first();
                            $user_wallet->balance += $transHistory->amount;
                            $user_wallet->save();
                            transactions::where(["loan_repayment_id" => $LoanRepayment->id])->delete();
                        } else {
                            return ['status' => 0, 'message' => 'Error']; //('fail','Repayment history Error!'.$LoanRepayment->id);
                        }
                    }
                    $loan                 = loan::where('id', $LoanRepayment->loan_id)->first();
                    $loan->loanBalance    += $LoanRepayment->amount;
                    $loan->principalLeft  += $LoanRepayment->P;
                    $loan->InterestLeft   += $LoanRepayment->I;
                    $loan->loan_status_id = 1;
                    $loan->save();
                    $LoanRepayment->status_id = 0;
                    $LoanRepayment->save();
                    // LoanRepayment::where('id',$loanID)->delete();
                    return ['status' => 1, 'message' => 'Repayment Deleted'];
                } else {
                    return ['status' => 0, 'message' => 'Repayment Delete Failed!'];
                }
            });
        } catch (\Throwable $e) {
            // Do something with your exception
            return ['status' => 0, 'message' => $e->getMessage()];

        }
    }
    public function rollbackFunction(Request $request)
    {
        try {
            if ($request->from != null && $request->to != null) {
                $from = $request->from;
                $to   = $request->to;
                $msg  = 'No record removed';
                if ($request->loanid) {
                    $Repay = LoanRepayment::join('loans', 'loans.id', '=', 'loan_repayments.loan_id')->join('users', 'users.id', '=', 'loans.user_id')->where('users.company_id', auth()->user()->company_id)->whereDate('loan_repayments.paymentDate', '>=', $from)->whereDate('loan_repayments.paymentDate', '<=', $to)->where('loan_repayments.status_id', 1)->where('loans.ref', $request->loanid)->select('loan_repayments.*', 'users.company_id', 'users.first_name', 'users.last_name', 'loans.ref')->get();
                } else {
                    $Repay = LoanRepayment::join('loans', 'loans.id', '=', 'loan_repayments.loan_id')->join('users', 'users.id', '=', 'loans.user_id')->where('users.company_id', auth()->user()->company_id)->whereDate('loan_repayments.paymentDate', '>=', $from)->whereDate('loan_repayments.paymentDate', '<=', $to)->where('loan_repayments.status_id', 1)->select('loan_repayments.*', 'users.company_id', 'users.first_name', 'users.last_name', 'loans.ref')->get();
                }
                if ($Repay) {
                    // $msg .= '1';
                    if (count($Repay) > 0) {
                        $msg = '';
                        foreach ($Repay as $RepayData) {
                            if ($RepayData->company_id == auth()->user()->company_id) {
                                // $msg .= '3';
                                $roll  = $this->rollbackRepayment($RepayData->payment_ref);
                                $msg  .= $roll['message'] . ' for ' . $RepayData->first_name . ' ' . $RepayData->last_name . '-' . $RepayData->ref . ' <br>';
                                // return $msg;
                            }
                        }
                    }
                }
                return ['status' => 1, 'message' => $msg];
            }
            return ['status' => 0, 'message' => 'Validation Error'];
        } catch (\Throwable $e) {
            // Do something with your exception
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }

    public function CompleteLoan()
    {
        if (! cando('loan-complete|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_Complete',
            'page_menu'        => 'loan_Complete',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        return view('company.loan.complete')->with($data);
    }
    public function loanPDF($repaymentRef)
    {
        $data = ['loan' => LoanRepayment::where(['payment_ref' => $repaymentRef])->first()];
        //return view('company.invoice.loanInvoice')->with($data); WBAHub Image not found or type unknown
        $pdf = PDF::loadView('company.invoice.loanInvoice', $data);
        return $pdf->download('loanPaymentInvoice.pdf');
    }
    public function loanInvoicePrint($repaymentRef)
    {
        $data = ['loan' => LoanRepayment::where('payment_ref', $repaymentRef)->first()];
        return view('company.invoice.loanInvoice')->with($data); // WBAHub Image not found or type unknown
    }
    public function DueLoanView()
    {
        if (! cando('loan-due-report|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'loan_Due',
            'page_menu'        => 'loan_Due',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => branch::where(['company_id' => auth()->user()->company_id])->get(),
        ];
        return view('company.loan.dueloan')->with($data);
    }
    public function AllLoan()
    {
        if (! cando('loan-all|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_all',
            'page_menu'        => 'loan_all',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'period'           => loan_period::all(),
            'branch'           => branch::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
            'product'          => loan_product::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
        ];
        return view('company.loan.allLoan')->with($data);
    }
    public function EditLoan()
    {
        if (! cando('loan-edit|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_edit',
            'page_menu'        => 'loan_edit',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'period'           => loan_period::all(),
            'branch'           => branch::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
            'product'          => loan_product::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
        ];
        return view('company.loan.editLoan')->with($data);
    }
    public function delete_loan($id)
    {
        try {
            $id            = Crypt::decryptString($id);
            $loan          = loan::where('id', $id)->first();
            $LoanRepayment = LoanRepayment::where('loan_id', $id)->first();
            payLoanPenalty::where('loan_id', $id)->delete();
            if ($LoanRepayment) {
                transactions::where(["loan_repayment_id" => $LoanRepayment->id])->delete();
                LoanRepayment::where('loan_id', $id)->delete();
            }
            PendingRepayment::where('loan_id', $id)->delete();

            $loan->delete();
            return (['message' => 'Loan Deleted Succefully', 'status' => 1]);
        } catch (\Throwable $e) {
            return (['message' => $e->getMessage() . $e->getLine(), 'status' => 0]);
        }
    }
    public function viewExtendedLoan($id)
    {
        $AddedLoanLog = AddedLoanLog::join('loans', 'loans.id', '=', 'added_loan_logs.loan.id')->
            join('users', 'users.id', '=', 'loans.user_id')->where(['loans.id' => $id, 'users.company_id' => auth()->user()->company_id])->first();
        return $AddedLoanLog;
    }
    public function LoanPrintOut($name, $id)
    {
        if (! cando('view-loan-repayment|root')) {
            return abort(403);
        }

        $loanID   = Crypt::decryptString($id);
        $infoData = loan::where(['id' => $loanID])->first();
        $data     = [
            'category_name'    => 'loan',
            'page_name'        => 'loan_print_out',
            'page_menu'        => 'loan_print_out',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'loanData'         => $infoData,
            'loanID'           => $id,
        ];
        return view('company.loan.inc.printout')->with($data);
    }
    public function extendLoanManually(Request $request)
    {
        if (! cando('extend-loan-manually|root')) {
            return response()->json(['status' => 0, 'message' => 'Access Denied!']);
        }

        $validator = Validator::make($request->all(), [
            'amount'   => 'required|numeric',
            'loanid'   => 'required',
            'extendby' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $messages,
                'subtext' => $messages,
                'status'  => 0,
            ];
            return response()->json($datax);
        }
        DB::beginTransaction();
        try {
            $loanId             = Crypt::decryptString($request->loanid);
            $loan               = loan::where('id', $loanId)->first();
            $amount             = $request->amount;
            $rate               = $loan->rate;
            $AddedLoanLog       = new AddedLoanLog;
            $loan->loanBalance += $amount;
            $frequency          = $loan->PeriodID->name;
            $DurationPeriod     = convertLY($frequency)['key'];
            $loanDuration       = convertLY($frequency)['value'] * $loan->duration;
            $loan->end_date     = $loan->end_date ?? date("Y-m-d", strtotime($loan->grace_at . "+$loanDuration $DurationPeriod"));

            if ($request->extendby == 'interest') {
                $loan->InterestLeft += $amount;
            } else if ($request->extendby == 'principal') {
                $loan->principalLeft += $amount;
            } else {
                $rate                 = $loan->rate;
                $interest             = (($rate / 100) * $amount);
                $loan->principalLeft += ($amount - $interest);
                $loan->InterestLeft  += $interest;
            }
            $loan->EachRepayment += ($amount / $loan->duration);
            $loan->save();
            $AddedLoanLog->end_date        = $loan->end_date;
            $AddedLoanLog->loan_id         = $loan->id;
            $AddedLoanLog->amount          = $amount;
            $AddedLoanLog->balance_before  = $loan->loanBalance - $amount;
            $AddedLoanLog->balance_after   = $loan->loanBalance;
            $AddedLoanLog->save();
            DB::commit();
            return (['message' => 'Loan extended!', 'status' => 1]);
        } catch (Exception $e) {
            DB::rollback();
            return (['message' => $e->getMessage(), 'status' => 0]);
        }

    }
    public function CollectionView()
    {
        if (! cando('loan-due-report|root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'loan_Due_collection',
            'page_menu'        => 'loan_Due_collection',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'branch'           => branch::where(['company_id' => auth()->user()->company_id])->get(),
        ];
        return view('company.loan.collection')->with($data);
    }

    public function bulk_upload(Request $request)
    {
        if (! cando('root')) {
            return abort(403);
        }

        $data = [
            'category_name'    => 'datatable',
            'page_name'        => 'bulk_loan_upload',
            'page_menu'        => 'bulk_loan_upload',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
            'refLoan'          => str_shuffle(time()),
            'branch'           => branch::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
            'period'           => loan_period::all(),
            'product'          => loan_product::where(['company_id' => auth()->user()->company_id, 'status_id' => 1])->get(),
        ];
        return view('company.loan.importloan')->with($data);
    }
    public function PostBooklLoanbulk(Request $request)
    {

        try {
            if (! cando('root')) {
                return abort(403);
            }

            Excel::import(new LoanImport, $request->file('cover_pic')->store('temp'));
            $auth      = auth()->user();
            $tempuser  = ImportLoan::where(['company_id' => $auth->company_id])->get();
            $message   = '';
            $nosuccess = 0;

            if ($tempuser) {
                foreach ($tempuser as $tempuserdata) {
                    ImportLoan::where(['id' => $tempuserdata->id])->delete();

                    $nosuccess++;
                    $tempuserdata->branch        = $request->branch;
                    $tempuserdata->officer       = $request->officer;
                    $tempuserdata->loan_products = $request->loan_products;
                    $loanperiod                  = loan_period::where('name', $tempuserdata->period)->first();
                    if (! $loanperiod) {

                        return back()->with(['message' => "Upload Failed period not in proper format for $tempuserdata->period!", 'title' => "Upload Error!", 'status' => 0]);
                    }
                    $virtual_account = $tempuserdata->customer;
                    $usercheck       = User::where('virtual_account', $tempuserdata->customer)->first();
                    if (! $usercheck) {
                        return back()->with(['message' => "Upload Failed user not in proper format for $tempuserdata->customer!", 'title' => "Upload Error!", 'status' => 0]);
                    }
                    $tempuserdata->period       = Crypt::encryptString($loanperiod->id);
                    $customer                   = [$usercheck->uuid];
                    $tempuserdata->customer     = $customer;
                    $tempuserdata->loan_type    = ($tempuserdata->loan_type == 'flate') ? 1 : 2;
                    $tempuserdata->RepaymentAmt = $tempuserdata->RepaymentAmt ?? 0;
                    $requestLoan                = new Request([ ...$tempuserdata->toArray()]);
                    ($this->PostBooklLoan($requestLoan));
                    $loan = Loan::where('ref', $tempuserdata->loanref)->first();
                    if ($loan) {
                        $loan->loan_status_id = 1;
                        $loan->modified_by    = auth()->user()->id;
                        $loan->save();
                        if ($tempuserdata->paid > 0) {
                            if ($loan) {
                                $wb_account_details = wb_account_details::where('virtual_accnum', $virtual_account)->first();
                                if ($wb_account_details) {
                                    $payrequest = new Request([
                                        'method'      => 'cash',
                                        'loanID'      => Crypt::encryptString($loan->id),
                                        'amount'      => $tempuserdata->paid,
                                        'accountType' => $wb_account_details->id,
                                    ]);
                                    ($this->generalpayloan($payrequest));
                                }
                            }

                        }
                    }

                }
                return back()->with(['message' => "$message", 'title' => "Total of $nosuccess records uploaded", 'status' => $nosuccess]);
            }

            return back()->with(['message' => "Upload Failed!", 'title' => "Upload Error!", 'status' => 0]);
        } catch (Exception $e) {
            $error = 'Message: ' . $e->getMessage() . ' ' . $e->getLine();
            $error = $error . ' | Invalid or Corrupt Document';
            echo $error;

            return back()->with(['message' => "$error", 'title' => "Upload Error !", 'status' => 0]);
        }
    }
}
