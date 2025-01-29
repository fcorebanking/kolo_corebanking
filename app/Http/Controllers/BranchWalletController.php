<?php

namespace App\Http\Controllers;

use App\Models\BranchWallet;
use App\Models\branch;
use App\Models\wb_account_details;
use App\Models\User;
use App\Http\Requests\StoreBranch_walletRequest;
use App\Http\Requests\UpdateBranch_walletRequest;

class BranchWalletController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function branchwallet_Reset(){
        $companyid = auth()->user()->company_id;
        $branchs = branch::where('company_id',$companyid)->get();
        if($branchs){
            foreach($branchs as $branchsData){
                $total = 0;
                // echo $branchsData->wallet->balance." <br>";
                $User = User::with('wb_account_details')->where(['company_id'=>$companyid,'branch_id'=>$branchsData->id,'user_type_id'=>1])->get();
                if($User){
                    foreach($User as $UserData){
                        // echo $UserData->id."?<br>";
                        foreach($UserData->wb_account_details as $wb_account_detailsData){
                            //echo $wb_account_detailsData->user_wallet->balance." added <br>";
                            $total +=$wb_account_detailsData->user_wallet->balance;
                        }
                    }
                }
                $BranchWallet =BranchWallet::where('branch_id',$branchsData->id)->update(['balance'=>$total]);
                echo "branch $branchsData->branch_name update with $total<br>";
            }
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreBranch_walletRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreBranch_walletRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Branch_wallet  $branch_wallet
     * @return \Illuminate\Http\Response
     */
    public function show(Branch_wallet $branch_wallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Branch_wallet  $branch_wallet
     * @return \Illuminate\Http\Response
     */
    public function edit(Branch_wallet $branch_wallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateBranch_walletRequest  $request
     * @param  \App\Models\Branch_wallet  $branch_wallet
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateBranch_walletRequest $request, Branch_wallet $branch_wallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Branch_wallet  $branch_wallet
     * @return \Illuminate\Http\Response
     */
    public function destroy(Branch_wallet $branch_wallet)
    {
        //
    }
}
