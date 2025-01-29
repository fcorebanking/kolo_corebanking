<div class='response_message'></div>
<div class='row gx-5 justify-item-center justify-content-center text-center'>
   <div class='col-sm-6 col-md-4 '><button onclick="show_remita_salaryhistory()"  class='btn btn-info'>View Salary by Phone</button>
   </div>
   <div class='col-sm-6 col-md-4'><button  onclick="display_accountNo_salary()" class='btn btn-lg btn-info'>View Salary by Account Number</button>
   </div>



   <div class="table-responsive">
    <table  class="table table-bordered table-hover remitatable">
    <thead>
         <tr>
            <th>customerName</th>
            <th>CustomerId</th>
            <th>accountNumber</th>
            <th>bankCode</th>
            <th>companyName</th>
            <th>firstPaymentDate</th>
            <th>salaryCount</th>


         </tr>
      </thead>
      <tbody name="custmerdetails">

          </tbody>
          </table>
 </div>
 <div class="table-responsive">
 <table  class="table table-bordered table-hover remitatable">
    <thead>
       <tr>
          <th>ID</th>
          <th>paymentDate</th>
          <th>Amount</th>
          <th>accountNumber"</th>
          <th>bankCode</th>
       </tr>
    </thead>
    <tbody id="salaryPaymentDetails"></tbody>
    </table>
 </div>
 <div class="table-responsive">
 <table id="example3" class="table table-bordered table-hover remitatable">
 <thead>
     <tr>
         <th>ID</th>
         <th>loanProvider</th>
         <th>loanAmount</th>
         <th>outstandingAmount</th>
         <th>loanDisbursementDate</th>
         <th>status</th>
         <th>repaymentAmount</th>
         <th>repaymentFreq</th>
     </tr>
 </thead>
 <tbody id="loanHistoryDetails"></tbody></table>
 </div>




@if(!isset($pending))
   <div class='col-sm-6 col-md-4'>
      <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#myModalRemitta">
         Post Notification
      </button>
   </div>





<!-- Modal -->
<div class="modal fade" id="myModalRemitta" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
<div class="modal-dialog" role="document">
   <div class="modal-content">
      <div class="modal-header">
      <h4 class="modal-title" id="myModalLabel">Send Reitta Post Notification</h4>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      </div>
      <form method="POST" id='postNotification' action="/submit-form">
      <div class="modal-body">
         @csrf
         <input type="hidden" value="{{$loanData->id}}" id="loanid" name="loanid" >

         <div class="form-group">
            <label for="account-name">Customer ID:</label>
            <input type="text" class="form-control" value="{{$loanData->user->remittar_customer_id}}" id="customerId" name="customerId" placeholder="Enter Remitta ID">
            </div>
         <div class="form-group">
            <label for="account-number">Account Number:</label>
            <input type="text" class="form-control" name='acnumber' value="{{$loanData->user->remittar_accountNo}}" id="account-number" placeholder="Enter Account Number" name="accountnumber">
         </div>
         <div class="form-group">
            <label for="bankcode">Bank:</label>
            <select class="form-control" id="bankcode" name='bankcode'>
            <option value="" selected>Please Select Bank</option>
            <option value="044">Access Bank</option>
            <option value="323">Accessmobile</option>
            <option value="401">Aso Savings And Loans</option>
            <option value="317">Cellulant</option>
            <option value="001">Central Bank Of Nigeria</option>
            <option value="023">Citibank</option>
            <option value="559">Coronation Merchant Bank</option>
            <option value="310">Corporetti</option>
            <option value="551">Covenant Microfinance Bank</option>
            <option value="063">Diamond Bank</option>
            <option value="302">Eartholeum (qik Qik)</option>
            <option value="050">Ecobank Nigeria</option>
            <option value="307">Ecomobile</option>
            <option value="562">Ekondo Microfinance Bank</option>
            <option value="084">Enterprise Bank</option>
            <option value="040">Equitorial Trust Bank</option>
            <option value="306">E-tranzact</option>
            <option value="309">Fbn M-money</option>
            <option value="413">Fbn Mortgages</option>
            <option value="314">Fets (my Wallet)</option>
            <option value="070">Fidelity Bank</option>
            <option value="318">Fidelity Mobile</option>
            <option value="608">Finatrust Microfinance Bank</option>
            <option value="011">First Bank Of Nigeria</option>
            <option value="214">First City Monument Bank</option>
            <option value="085">First Inland Bank</option>
            <option value="501">Fortis Microfinance Bank</option>
            <option value="308">Fortis Mobile</option>
            <option value="601">Fsdh</option>
            <option value="315">Gt Mobile Money</option>
            <option value="058">Guaranty Trust Bank</option>
            <option value="324">Hedonmark</option>
            <option value="030">Heritage Bank</option>
            <option value="415">Imperial Homes Mortgage Bank</option>
            <option value="069">Intercontinental Bank</option>
            <option value="301">Jaiz Bank</option>
            <option value="402">Jubilee Life</option>
            <option value="303">Kegow</option>
            <option value="082">Keystone Bank</option>
            <option value="014">Mainstreet Bank</option>
            <option value="330">Mimoney (powered By Intellifin)</option>
            <option value="313">M-kudi</option>
            <option value="312">Monetize</option>
            <option value="325">Moneybox</option>
            <option value="561">New Prudential Bank</option>
            <option value="552">Npf Mfb</option>
            <option value="056">Oceanic Bank</option>
            <option value="606">Omoluabi Savings And Loans</option>
            <option value="565">One Finance</option>
            <option value="327">Paga</option>
            <option value="560">Page Mfbank</option>
            <option value="502">Parallex</option>
            <option value="311">Parkway (ready Cash)</option>
            <option value="329">Payattitude Online</option>
            <option value="101">Providus Bank</option>
            <option value="403">Safetrust Mortgage Bank</option>
            <option value="609">Seed Capital Microfinance Bank</option>
            <option value="076">Skye Bank</option>
            <option value="039">Stanbic Ibtc Bank</option>
            <option value="304">Stanbic Mobile</option>
            <option value="068">Standard Chartered Bank</option>
            <option value="232">Sterling Bank</option>
            <option value="326">Sterling Mobile</option>
            <option value="100">Suntrust</option>
            <option value="319">Teasy Mobile</option>
            <option value="523">Trustbond</option>
            <option value="316">U-mo</option>
            <option value="032">Union Bank Of Nigeria</option>
            <option value="033">United Bank For Africa</option>
            <option value="215">Unity Bank</option>
            <option value="566">Vfd Microfinance Bank</option>
            <option value="328">Visual Ict</option>
            <option value="320">Vtnetwork</option>
            <option value="035">Wema Bank</option>
            <option value="057">Zenith Bank</option>
            <option value="322">Zenith Mobile</option>
            </select>
         </div>
         <div class="form-group">
         <label for="phone-number">Phone Number</label>
         <input type="text" class="form-control" id="phone-number" name="phoneNumber" placeholder="Enter phone number" required value="{{$loanData->user->phone}}">
         </div>

         <div class="form-group">
         <label for="loan-amount">Loan Amount</label>
         <input type="number" class="form-control" id="loan-amount" name="loanAmount" placeholder="Enter loan amount" required value="{{$loanData->principal }}">
         </div>
         <div class="form-group">
         <label for="collection-amount">Collection Amount</label>
         <input type="number" class="form-control" id="collection-amount" name="collectionAmount" placeholder="Enter collection amount" required value="{{$loanData->EachRepayment}}">
         </div>
         <div class="form-group">
         <label for="disbursement-date">Date of Disbursement</label>
         @php
            $dateString =$loanData->created_at;
            $dateTime = new DateTime($dateString);
         @endphp
         <input type="date-time" class="form-control" id="disbursement-date" name="dateOfDisbursement" required value="{{ $dateTime->format('d-m-Y') }}">
         </div>
         <div class="form-group">
         <label for="collection-date">Date of Collection</label>
         <input type="date-time" class="form-control" id="collection-date" name="dateOfCollection" value="{{ $dateTime->format('d-m-Y') }}" required>
         </div>
         <div class="form-group">
         <label for="total-collection-amount">Total Collection Amount</label>
         <input type="number" class="form-control" id="total-collection-amount" value='{{$loanData->loanBalance}}' name="totalCollectionAmount" placeholder="Enter total collection amount" required>
         </div>
         <div class="form-group">
         <label for="number-of-repayments">Number of Repayments</label>
         <input type="number" class="form-control" id="number-of-repayments" value='{{$loanData->duration}}'  name="numberOfRepayments" placeholder="Enter number of repayments" required>
         </div>
      <div class='msg_three'></div>
      </div>
      <div class="modal-footer">
      <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      <button type="submit" class="btn btn-primary">Submit</button>
      </div>
   </form>

   </div>
</div>
</div>


{{-- mandate payment history --}}
<div class="modal fade" id="remitamandateModal" tabindex="-1" role="dialog" aria-labelledby="remitamandateModal" aria-hidden="true">
   <div class="modal-dialog modal-lg" role="document">
     <div class="modal-content">
       <div class="modal-header">
         <h5 class="modal-title" id="exampleModalLabel">Remiter Mandate</h5>
         <button type="button" class="close" data-dismiss="modal" aria-label="Close">
           <span aria-hidden="true">&times;</span>
         </button>
       </div>
       <div class="modal-body" id="mandates">
             <h3>Mandate Details</h3>
        <div class="table-responsive">
             <table  class="table table-bordered table-hover remitatable">
                 <thead>
                      <tr>

                         <th>customerName</th>
                         <th>CustomerId</th>
                         <th>phoneNumber</th>
                         <th>loanMandate</th>
                         <th>totalDisbursed</th>
                         <th>outstandingLoanBal</th>
                         <th>loanRepaymentRef</th>
                         <th>employerName</th>
                         <th>salaryAccount</th>
                         <th>salaryBankCode</th>
                         <th>disbursementAccountBank</th>
                         <th>collectionStartDate</th>
                         <th>dateOfDisbursement</th>
                         <th>disbursementAccount</th>
                         <th>status</th>
                         <th>lenderDetails</th>



                      </tr>
                   </thead>
                   <tbody name="custmerdetails">

                       </tbody>
                       </table>
                       </div>
                       <h3>Repayments</h3>
                        <div class="table-responsive">
             <table  class="table table-bordered table-hover remitatable">
                 <thead>
                      <tr>

                         <th>SN</th>
                         <th>transactionamount</th>
                         <th>deductiondate</th>
                         <th>paymentstatus</th>




                      </tr>
                   </thead>
                   <tbody id="repayment">

                       </tbody>
                       </table>
                       </div>
       </div>

       <div class="modal-footer">
         <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

       </div>
     </div>
   </div>
 </div>


<h2>View Mandate</h2>

   <div class="table-responsive">
   <table class='table table-striped'>
      <thead class="thead-dark"><th>SN</th><th>MandateRef</th><th>AuthCode</th><th>CustomerID</th><th>Status</th><th>Date</th><th>Action</th><th>Action</th></thead>
      <tbody id='mandateTable'>

      </tbody>
  </table>
</div>

<script>
    $('#bankcode').val('{{$loanData->user->remittar_bankCode}}').change();
</script>

@endif

</div>
