<div class='contaner'>
    @if(cando('root'))
    @if(isset($loanID))
    <button class='btn btn-info' onclick='swap1()'>View History</button>
    <button class='btn btn-danger' onclick='swap2()'>Transfer This loan Repayment</button>

    <div class='transferpayment  m-5'>
        <p class='alert alert-warning'>Note: This function enable you change the staff that perform a particular loan repayment, once changed all repayment from this loan will be assign to the selected staff</p>
        <p class='alert alert-danger'>Note: In a case where more than one staff parformed a loan repayment, the system will still assign all the transaction to the selected staff</p>
        <form action='/company/loan/Swapping-repayment' method='post' onsubmit="return confirm('Do you really want to Transfer all repayment of this loan to this user, Note this transaction cannot be reversed?');">
            {{ csrf_field() }}{{ method_field('POST') }}
            <input type='hidden' value='{{$loanID ?? ""}}' name='loanid'>
            <div class='row m-5'>
                <div class='form-group col-sm-6 col-md-6'>
                    <select name='to'  class='form-control form-small'  id='customerx'></select>
                </div>
                <div class='form-group col-sm-6 col-md-6'>
                    <button type='submit' class='btn btn-primary' onclick="">Swap</button>
                </div>
            </div>
        </form>
    </div>
    @endif
    @endif

    <div class='tablepaymenthistory'>
        @if (isset($loanID))
            <div class='row m-5'>
                <div class='col-sm-6 col-md-6'><label>From:</label><input type='date' id='fromDate' class='form-control' onchange="GeneraLoanRepayment('{{$loanID}}')"></div>
                <div class='col-sm-6 col-md-6'><label>To:</label><input type='date' id='toDate' class='form-control' onchange="GeneraLoanRepayment('{{$loanID}}')"></div>
            </div>
        @endif
        <div class='table-responsive'>
            <table class="table table-striped" >
                <thead>
                    <tr>
                    <th scope="col">#</th>
                    <th scope="col">Collection Date</th>
                    <th scope="col">Created By</th>
                    <th scope="col">Method</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Balance Before</th>
                    <th scope="col">Balance After</th>
                    <th scope="col">Action</th>
                    <th scope="col">Reciept</th>
                    </tr>
                </thead>
                <tbody class='PaymentHistory'>
                </tbody>
            </table>
        </div>
    </div>
</div>
