@extends('layouts.app')
@section('content')
<div class='container-fluid'>
    <input type='hidden' value='main=true' id='page'>

<button type="button" style="margin:10px;" class="btn btn-primary mb-2 mr-2" onclick='reset()'>
    Add Expenses &nbsp<i class='fa fa-plus'></i>
</button>

<div class='row'>
<div class='col-md-11 form-group'>
    <select name='expense_type_idx' class='form-control' id='expense_type_idx'>
    <option value=''>Select Expenses</option>
    @if($expenseType)
        @foreach ($expenseType as $typedata)
            <option value='{{$typedata->id}}'>{{$typedata->name}}</option>
        @endforeach
    @endif
</select></div>
    <div class='col-md-6 form-group'>
    <input type='date' id='from' class='form-control'>
</div>
<div class='col-md-6 form-group'>
    <input type='date' id='to' class='form-control'>
</div>

</div>
<div class=' form-group'>

<button class='btn btn-info' onclick="searchExpenses()">Search</button>
</div>
<div class="widget-content widget-content-area br-6">
    <div class="table-responsive mb-4 mt-4">
        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
                <tr>
                    <th>S/N</th>
                    <th>Expenses Type</th>
                    <th>Amount</th>
                    <th>Expenses Date</th>
                    <th>Recurring Type</th>
                    <th>Recurring Period</th>
                    <th>Description</th>
                    <th>Invoice Receipt</th>
                    <th>Created By</th>
                    <th>Modified By</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add Expenses</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                </button>
            </div>
            <form id='add-expenses'>
                <input type="hidden" class="token" name="_token" value="<?php echo csrf_token(); ?>">
                <input type="hidden" id='type_id' name='type_id'>
                <div class="modal-body">
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Type</span>
                        </div>
                        <select name='expense_type_id' class='form-control' id='expense_type_id'>
                            <option>Select Expenses</option>
                            @if($expenseType)
                                @foreach ($expenseType as $typedata)
                                    <option value='{{$typedata->id}}'>{{$typedata->name}}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text" id="basic-addon7">Amount</span>
                        </div>
                        <input type="text" class="form-control" id='amount' name='amount' aria-describedby="basic-addon3"
                            placeholder="Amount 1..">
                    </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Expense Date</span>
                        </div>
                        <input type="date" class="form-control" id='expenses_date' name='expenses_date' aria-describedby="basic-addon3"
                            placeholder="50...">
                    </div>
                    <p class='alert alert-info'>Select below if you want the system to periodically perform expenses</p>
                    <div class="input-group mb-4 row">
                        <div class="col-md-4">
                        <label><input type='radio' name='is_recurring' id='is_recurring' value='0' checked onclick='recuring(0)'> No</label>
                        </div>
                        <div class="col-md-4">
                           <label> <input type='radio' name='is_recurring' id='is_recurring2'  value='1' onclick='recuring(1)'> Yes</label>
                    </div>
                </div>
                    <div class="input-group mb-4">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Add Recurring Expense</span>
                        </div>
                        <select name='recurring_expense_period' disabled="disabled" class='form-control' id='recurring_expense_period'>
                            <option></option>
                            @for ($i=1;$i<32;$i++)
                            @php
                            $strpost ='';
                            if(substr($i, -1) == 1){
                                $strpost = 'st';
                            }else if(substr($i, -1) == 2){
                                $strpost = 'nd';
                            }else if(substr($i, -1) == 3){
                                $strpost = 'rd';
                            }else{
                                $strpost = 'th';
                            }
                            @endphp
                            <option value='{{$i}}'>{{$i.$strpost}} of every Month</option>
                            @endfor
                        </select>
                    </div>

                    <div class="form-group mb-4">
                        <label>Description</label>
                        <textarea class='form-control' name='description' id='description'></textarea>
                    </div>

                    <div class="form-group  mb-4">
                        <label>Invoice Receipt (optional)</label>
                        <input type='file' name='invoice_receipt'>
                    </div>
                    <h4>Accounting </h4>
                    <p>Select your financial account for journal entry:</p>
                    @include('company.inc.ledger_dr')
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="submit" class="btn btn-primary save-btn" id='btn-progress'>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
function recuring(val){
    if(val == 0){
    $('#recurring_expense_period').attr('disabled','disabled');
    }else{
        $('#recurring_expense_period').removeAttr('disabled');
    }
}
recuring(0);
</script>
@endsection
