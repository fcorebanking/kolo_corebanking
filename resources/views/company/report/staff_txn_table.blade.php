@extends('layouts.app')

@section('content')
<style>
.px-md-5{
    margin:5px;
}
.custom_active{
    margin:5px;
}

</style>
<div class="row invoice layout-top-spacing layout-spacing" id='printinvoice'>
                    <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">

                        <div class="doc-container">
                            <div class="invoice-container">
                                <div class="invoice-inbox">

                                    <div id="ct" class="">

                                        <div class="invoice-00001">
                                            <div class="content-section">

                                                <div class="inv--head-section inv--detail-section">

                                                    <div class="row">

                                                        <div class="col-sm-6 col-12 mr-auto">
                                                            <div class="d-flex">

                                                                <h3 class="in-heading align-self-center">.</h3>
                                                            </div>
                                                        </div>
                                                        <form style="display:flex">
                                                            <input type='date' name='from' class='form-control' placeholder="From">
                                                            <input type='date' name='to' class='form-control' placeholder="To">
                                                            @if(cando('view-all-teller-transaction|root'))
                                                                <select class="form-control" name='staff'>
                                                                    <option value=''>All</option>
                                                                    @foreach ($staff as $staffs)
                                                                        <option value={{$staffs->uuid}} @if(isset($request->staff) && $staffs->uuid === $request->staff) selected @endif> {{$staffs->first_name.' '.$staffs->last_name}}</option>
                                                                    @endforeach
                                                                </select>
                                                            @endif
                                                            <button type='submit' class='btn btn-primary'>Search</button>
                                                        </form>
                                                        <div class="col-sm-6 text-sm-right">
                                                                    </div>

                                                        <div class="col-sm-6 align-self-center mt-3">
                                                            <p class="inv-street-addr"></p>
                                                            <h3 class="inv-customer-name">{{auth()->user()->company->company_name}} Staff Transaction</h3>


                                                        </div>
                                                        <div class="col-sm-6 align-self-center mt-3 text-sm-right">

                                                        </div>

                                                    </div>

                                                </div>

                                                <div class="inv--detail-section inv--customer-detail-section">



                                                </div>
                                                <h5>Teller Transaction</h5>
                                                <hr>

                                                <div class="inv--product-table-section">
                                                    <div class="table-responsive">
                                                        <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                                            <thead>
                                                                <th>Created At</th>
                                                                <th>Client</th>
                                                                <th>Username</th>
                                                                <th>Type</th>
                                                                <th>Balance Before</th>
                                                                <th>Amount</th>
                                                                <th>Balance After</th>
                                                                <th>Transaction Type</th>
                                                                <th>Description</th>


                                                            </thead>
                                                            <tbody>
                                                                @foreach ($query as $data)
                                                                    <tr>
                                                                        <td>{{$data->created_at}}</td>
                                                                        <td>{{$data->first_name.' '.$data->last_name}}</td>
                                                                        <td>{{$data->username}} </td>
                                                                        <td>@if($data->credit_debit_type_id===1) <div class='btn btn-success'>Credit</div> @else <div class='btn btn-danger'>Debit</div> @endif</td>
                                                                        <td>{{$data->balance_before}}</td>
                                                                        <td>{{$data->amount}}</td>
                                                                        <td>{{$data->balance_after}}</td>
                                                                        <td>{{$data->transaction_type}}</td>
                                                                        <td>{{$data->description}}</td>

                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>


<script>
    function renderStaff() {
        $('select[name="officer"]').empty();
        $branch = $('#branch').val();
        $('select[name="officer"]').append('<option value="">Select Staff</option>')
        $.ajax({
            url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
            type: 'GET',
            dataType: 'json',
            success: function (data) {

                data.forEach(function (staff) {

                    $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                });
            }
        });
    }
</script>
@endsection






