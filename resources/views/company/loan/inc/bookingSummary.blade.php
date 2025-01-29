<ul class='sessions' style='list-style-type: none; text-align:left;'></ul>
<h5>Borrower Section</h5>
<div class='table-responsive'>
<table class='table'>
    <head>
        <tr>
            <th>#</th>
            <th>Logo</th>
            <th>Name</th>
            <th>AccountNo</th>
        </tr>
    </head>
    <tbody class='borrowerCard'>
    </tbody>
</table>
</div>
<div class='guarantorTitle'>
    <h5>Loan Guarantor</h5>
<div class='table-responsive'>
<table class='table'>
    <head>
        <tr>
            <th>#</th>
            <th>Logo</th>
            <th>Borrower</th>
            <th>Guarantor Name</th>
            <th>Salary</th>
            <th>Relationship</th>
            <th>Phone</th>
            <th>Status</th>
            <th>occupation</th>
            <th>BVN</th>
            <th>Address</th>
            <th>Remark</th>
            <th>Action</th>
        </tr>
    </head>
    <tbody class='guarantorCard'>
    </tbody>
</table>
<div class='gmsg'></div>
</div>
</div>
<h5>Other Info</h5>
<div class='row loanrecord'></div>
                <div class='d-flex justify-content-center repaymentTable table-responsive'>
                    <div class='row'>
                        <div class='col-md-12 col-sm-6'>
                            <h5 class='flat-table'>Flat Rate Loan Table  </h5>
                        </div>
                        <div class='col-md-12 col-sm-6'>
                                <table class='table flat-table'>
                                <head>
                                    <tr>
                                        <th>Principal</th>
                                        <th>Interest Rate%</th>
                                        <th>Total Interest</th>
                                        <th>Repayment</th>
                                        <th>Principal Return</th>
                                        <th>Duration</th>
                                                </tr>
                                    </head>
                                    <tbody id='flatTbody'>
                                    </tbody>
                                </table>
                        </div>
                    </div>
                        <div class='row'>
                            <div class='col-md-12 col-sm-6'>
                                <h3 class='repayment-table'>Amortisation Table - Reducing Balance Loan</h3>
                            </div>
                            <div class='col-md-12 col-sm-6'>
                                <table class='table repayment-table'>
                                    <head>
                                    <tr>

                                        <th>Loan Balance</th>
                                        <th>Monthly Installment</th>
                                        <th>Rate</th>
                                        <th>Paid to Principal</th>
                                        <th>Paid to Interest(RM)</th>
                                        <th>Duration</th>
                                    </tr>
                                    </head>
                                    <tbody id='repaymentTbody'>

                                    </tbody>
                                </table>
                            </div>
                        </div>
                </div>
                @if($page_menu == 'loan_pending')
                    <div class="row justify-content-center">
                        <div class='col-md-4 col-sm-6'>
                            <div class="form-check form-check-inline ">
                                <input class="btn-check" type="radio" name="actions" id="inlineRadio1" value="2" required autocomplete="off">
                                <label class="btn btn-outline-primary" for="inlineRadio1">Approve</label>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class="form-check form-check-inline">
                                <input class="btn-check" type="radio" name="actions" id="inlineRadio2" value="4" autocomplete="off">
                                <label class="btn btn-outline-primary" for="inlineRadio2">Reject</label>
                            </div>
                        </div>
                        <div class='col-md-4 col-sm-6'>
                            <div class="form-check form-check-inline">
                                <input class="btn-check" type="radio" name="actions" id="inlineRadio3" value="5" autocomplete="off">
                                <label class="btn btn-outline-primary" for="inlineRadio3">Delete</label>
                            </div>
                        </div>
                    </div>
                @endif

