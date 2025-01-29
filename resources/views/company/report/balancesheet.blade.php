@extends('layouts.app')

@section('content')
<div class="box-body">
    <div class="row m-5">

        <div class="col-sm-6 col-md-3">
            <input type="date" name="from" class="form-control is-datepick" id="from" placeholder="To Date" value="10/01/2023" required="">
        </div>
        <div class="col-sm-6 col-md-3">
            <input type="date" name="to" class="form-control is-datepick" id="to" placeholder="To Date" value="10/01/2023" required="">
        </div>
        <div class="col-sm-6 col-md-6">
            <span class="input-group-btn">
                <button type="button" class="btn bg-info btn-flat" onclick='balancesheet()'>Search!</button>
            </span>
            <span class="input-group-btn">
                <button type="reset" class="btn bg-purple  btn-flat pull-right" >Reset!</button>
            </span>
        </div>
        </div>

    <div class="row">
        <div class="col-sm-6">  <div class="table-responsive">
            <table class="table table-bordered table-condensed table-hover" id='1'>
                <tbody><tr style="background-color: #F2F8FF">
                   <td colspan="3" class="text-center"><b>ASSETS</b></td>
               </tr>
               <tr>
                   <td class="text-bold text-blue"><b>Current Assets:</b></td>
                   <td></td>
                   <td></td>
               </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Loans Outstanding </b></td>
                   <td style="text-align:right"></td>
                   <td></td>
               </tr>
               <tr>
                <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;P-Luquidated</b></td>
                <td style="text-align:right" id="luquidate"></td>
                <td ></td>
            </tr>
            <tr>
                <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Balance Luquidated</b></td>
                <td style="text-align:right" id="luquidatebalance"></td>
                <td ></td>
            </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Current Loan Balance</b></td>
                   <td style="text-align:right" id="currentloan"></td>
                   <td ></td>
               </tr>

               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Past Due</b></td>
                   <td style="text-align:right" id="pastloan"></td>
                   <td></td>
               </tr>

               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Loans Outstanding (Gross)</b></td>
                   <td style="text-align:right;" class="text-bold" ></td>
                   <td style="text-align:right" id="totaloutstanding" class='text-primary'></td>
               </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Principal Written Off)</b></td>
                   <td style="text-align:right;  border-bottom: 1px solid #000" id="offloan"></td>
                   <td></td>

               </tr>
                    <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Initial Loan Principal)</b></td>
                   <td style="text-align:right;  border-bottom: 1px solid #000" id="principal"></td>
                   <td></td>

               </tr>
                     <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Current Principal Left)</b></td>
                   <td style="text-align:right;  border-bottom: 1px solid #000" id="PrincipalLeft"></td>
                   <td></td>
                   <tr>
                    <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Current Interest Left)</b></td>
                    <td style="text-align:right;  border-bottom: 1px solid #000" id="interest"></td>
                    <td></td>
               </tr>
                     <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Loan Completed)</b></td>
                   <td style="text-align:right;  border-bottom: 1px solid #000" id="completeloan"></td>
                   <td></td>
               </tr>

            <tr>
                <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Total Loan Paid)</b></td>
                <td></td>
                <td style="text-align:right;  border-bottom: 1px solid rgb(12, 65, 200)" class="text-bold text-primary"><div id="loanpaid">0</div></td>
            </tr>

               <tr>
                   <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Loans Net</b></td>
                   <td></td>
                   <td style="text-align:right" class="text-bold text-info"><div id="loannet" >0</div></td>
               </tr>
                <tr>
                   <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Transaction Vault</b></td>
                   <td style="text-align:right"></td>
                   <td></td>
               </tr>
               <tr>
                <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Loan Penalty Charge</b></td>
               <td style="text-align:right;" class="text-bold" id="penalty">0</td>
                <td ></td>
            </tr>
               <tbody id='vault' >

               </tbody>


                     <tr>
                   <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Total Charges</b></td>
                   <td style="text-align:right"><input type="number" id='totalchargehide' hidden></td>
                   <td style="text-align:right" id="totalcharge" class='text-info'>0</td>
               </tr>
            </tr>
            <tr>
                <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Other Income</b></td>
                <td style="text-align:right"><input type="number" id='other_income_value' hidden></td>
                <td style="text-align:right;" class="text-bold text-info" id="other_income">0</td>
       </tr>
       <tr>
        <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Esusu Overdraft</b></td>
        <td style="text-align:right"><input type="number" id='esusu_overdraft' hidden></td>
        <td style="text-align:right;" class="text-bold text-info" id="esusu_overdraft_view">0</td>
</tr>
<tr>
    <td class="text-bold"><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Esusu Accumulated Charge</b></td>
    <td style="text-align:right"><input type="number" id='esusu_accum_charge' hidden></td>
    <td style="text-align:right;" class="text-bold text-info" id="esusu_accum_charge_view">0</td>
</tr>
               {{-- <tr class="active">
                   <input type="number" id='asset' hidden>
                   <input type="number" id='asset1' hidden>
                   <td style=""></td>
                   <td style="text-align:right;" class="text-bold"><div id="currentasset">0</div></td>
               </tr> --}}

<tr class="active">
                   <td><b>TOTAL ASSETS</b></td>
                   <td></td>
                   <td style="text-align:right;" class="text-bold text-success"><div id="currentasset1">0</div></td>
               </tr>
            </tbody></table>
            </div>
        </div>

        <div class="col-sm-6">
              <div class="table-responsive">
            <table class="table table-bordered table-condensed table-hover" id='2'>
               <tbody><tr style="background-color: #F2F8FF">
                   <td colspan="3" class="text-center"><b>LIABILITY AND EQUITY</b></td>
               </tr>

               <tr>
                   <td class="text-blue" colspan="2"><b>Savings Product</b></td>
               </tr>
               <tbody id='savings_product'>

               </tbody>

               <tr class="warning">
                   <td ><b>&nbsp;&nbsp;Total Savings</b></td>
                   <td style="text-align:right"><input type="text"  placeholder="" value="" class="balance_sheet_input decimal-2-places" id="savings" onkeyup="update_balance_sheet()"></td>
               </tr>
               <tr>
                <td><b>&nbsp;&nbsp;Esusu Wallet</b></td>
                <td style="text-align:right"><input type="text" name="esusu"  placeholder="" id="esusuval" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
            </tr>
            <tr>
                <td><b>&nbsp;&nbsp;Esusu Withdrawable</b></td>
                <td style="text-align:right"><input type="text"  placeholder="" id="esusuwithdrawable_val" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
            </tr>
            <tr>
                <td><b>&nbsp;&nbsp;Investment</b></td>
                <td style="text-align:right"><input type="text" name="investment"  placeholder="" id="investmentval" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
            </tr>
                <tr>
                    <td><b>&nbsp;&nbsp;Expenses</b></td>
                    <td style="text-align:right"><input type="text" name="expenses"  placeholder="" id="expensesval" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
                </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;Wages Payable</b></td>
                   <td style="text-align:right"><input type="text" id="wages_payable"  placeholder="" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
               </tr>

               <tr>
                   <td><b>&nbsp;&nbsp;Income Taxes Payable</b></td>
                   <td style="text-align:right"><input type="text" id="income_taxes_payable"  placeholder="" value="0" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
               </tr>

               <tr class="info">
                   <td><b>TOTAL LIABILITIES</b></td>
                   <td style="text-align:right;" class="text-bold text-info"><div id="TotalLiabilities">0</div></td>
               </tr>
               <tr>
                   <td class="text-blue" colspan="2"><br><b>EQUITY</b></td>
               </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Loan Fund Capital</b></td>
                   <td style="text-align:right"><input type="text" id="fund_capital"  placeholder="" value="" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
               </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Retained Net Surplus/(Deficit) prior years</b></td>
                   <td style="text-align:right"><input type="text" id="retained_net_surplus"  placeholder="" value="" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
               </tr>
               <tr>
                   <td><b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Net Surplus/(Deficit) current year</b></td>
                   <td style="text-align:right"><input type="text" id="net_surplus"  placeholder="" value="" class="balance_sheet_input decimal-2-places" onkeyup="update_balance_sheet()"></td>
               </tr>
                <tr class="info">
                   <td><b>TOTAL EQUITY</b></td>
                   <td style="text-align:right;" class="text-bold text-info"><div id="TotalEquity">0</div></td>
               </tr>
               <tr class="info">
                   <td><b>TOTAL LIABILITIES AND EQUITY</b></td>
                   <td style="text-align:right;" class="text-bold text-success"><div id="TotalLiabilitiesEquity">0</div></td>
               </tr>
            </tbody></table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-info pull-right" onclick="tablesToExcel(array1, 'myfile.xls')"  data-loading-text="<i class='fa fa-spinner fa-spin '></i> Please Wait. This can take a few minutes.">Export to Excel</button>

</div>
@endsection
