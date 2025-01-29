<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ThirdParty\BlackListController;
use App\Http\Controllers\ThirdParty\LendsqrController;
use App\Http\Requests\StorecustomerCreditRequest;
use App\Http\Requests\UpdatecustomerCreditRequest;
use App\Models\customerCredit;
use App\Models\frequency;
use Illuminate\Http\Request;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\WbaCharge;
use App\Models\WbaCompanyCharge;
use Illuminate\Support\Facades\Http;

class CustomerCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function bvnLoanVerification($company_name)
    {
        //
        if (!cando('bvn-loan-verification|root')) abort(403);
        $data = [
            'category_name' => 'forms',
            'page_name' => 'wizards',
            'page_menu' => 'bvn_loan_verification_menu',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'customerCredit' => customerCredit::where('status', 1)->where(function ($query) {
                $query->orwhere(['company_id' => auth()->user()->company_id, 'access' => 2]);
                $query->orwhere(['access' => 1]);
            })->get(),
            'alt_menu' => 0,
            'status' => frequency::all(),
        ];
        return view('company.loan.bvnchecker')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function verify($id, $bvn)
    {
        $auth =auth()->user();
        $id = encrypt_decrypt('decrypt', $id);
        $customerCredit = customerCredit::where(['id' => $id, 'status' => 1])->where(function ($query) {
            $query->orwhere(['company_id' => auth()->user()->company_id, 'access' => 2]);
            $query->orwhere(['access' => 1]);
        })->first();
        //  die($customerCredit->name.' '.$customerCredit->id.' '.$customerCredit->url);

        if (!$customerCredit) {
            return \response()->json(['status' => 0, "Invalid type selected"]);
        }


        $amount = 0;

        $searchId = 0;

        switch ($id) {
            case 1:
            case 2:
                $searchId = 23;
                break;
            case 3:
                $searchId = 22;
                break;
            default:
                $searchId = 21;
                break;
        }

        $wbaCharge = WbaCharge::find($searchId);
        $amount = $wbaCharge->charge;

        $chargeCompany = \debitCompanyWithCharge(auth()->user()->company_id, $amount, 0);

        if (!$chargeCompany['status']) {
            return \response()->json($chargeCompany);
        }

        $transactionId = generate_txnID();
        $ledger_cr =null;
        $ledger_dr =null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(61, $auth->company_id);
            if($ledger_Settings){
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
        // echo "false";
        $data = [
            "balance_before" => $chargeCompany["balance_before"],
            "amount" =>  $amount,
            "description" => "$customerCredit->name lookup Charge",
            "branch_id" => $auth->branch_id,
            "transaction_status_id" => 1,
            "transaction_reference" => $transactionId,
            "transaction_type_id" => 61, //debit 7
            "company_id" => $auth->company_id,
            "credit_debit_type_id" => 2, //debit 2
            "transaction_remarks_id" => 2, //success 2
            "balance_after" => $chargeCompany["balance_after"],
            "created_date"  => date('Y-m-d'),
            "created_by" => $auth->id,
            "ledger_cr"=>$ledger_cr,
            "ledger_dr"=>$ledger_dr
        ];
        $transaction = transactions::create($data);

        if ($transaction) {
            // echo "end";

            if ($id == 1) {
                $lendsqrController = new LendsqrController();

                $bvnLoanLookup = $lendsqrController->bvnLookup($bvn);
                return \response()->json($bvnLoanLookup);
            } else if ($id == 3) {
                $lendsqrController = new LendsqrController();

                $ecoSystemCheck = $lendsqrController->ecosystemChecker($bvn);
                return \response()->json($ecoSystemCheck);
            } else if ($id == 2) {
                $blacklistController = new BlackListController();

                $crcSearch = $blacklistController->crcSearch($bvn);
                return \response()->json($crcSearch);
            } else if ($id == 4) {
                $blacklistController = new BlackListController();

                $firstCentral = $blacklistController->firstCentral($bvn);
                return \response()->json($firstCentral);
            }
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorecustomerCreditRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorecustomerCreditRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\customerCredit  $customerCredit
     * @return \Illuminate\Http\Response
     */
    public function show(customerCredit $customerCredit)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\customerCredit  $customerCredit
     * @return \Illuminate\Http\Response
     */
    public function edit(customerCredit $customerCredit)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatecustomerCreditRequest  $request
     * @param  \App\Models\customerCredit  $customerCredit
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatecustomerCreditRequest $request, customerCredit $customerCredit)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\customerCredit  $customerCredit
     * @return \Illuminate\Http\Response
     */
    public function destroy(customerCredit $customerCredit)
    {
        //
    }
}
