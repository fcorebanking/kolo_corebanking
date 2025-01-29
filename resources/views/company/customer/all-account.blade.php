@extends('layouts.app')

@section('content')


<div class="layout-px-spacing">

  <div class="row layout-top-spacing">

    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
      <div class="widget-content widget-content-area br-6">
        <div class='row'>
          <div class='col-sm-6 col-md-2'>
            <label for='option'>Sort By</label>

            <select id='option' class='form-control'>
              <option value='all'>All</option>
              {{-- <option value='date'>By Date</option> --}}
            </select>
          </div>
          <div class='col-sm-6 col-md-3'>
            <label for='from'>From</label>
            <input type='date' class='form-control' id='from'>
          </div>
          <div class='col-sm-6 col-md-3'>
            <label for='to'>To</label>
            <input type='date' class='form-control' id='to'>
          </div>
          <div class='col-sm-6 col-md-2'>
            <label for='product'>Product</label>
            <select id='productx' class='form-control'>
              <option value=''>All</option>
              @foreach($product as $productData)
              <option value='{{Crypt::encryptString($productData->id)}}'>{{$productData->savings_product}}</option>
              @endforeach
            </select>
          </div>
          <div class='col-sm-6 col-md-2'>
            <label for='branch'>Branch</label>
            <select id='branch' class='form-control' onchange="renderStaff()">
              <option value=''>All</option>
              @foreach($branch as $branchdata)
              <option value='{{Crypt::encryptString($branchdata->id)}}'>{{$branchdata->branch_name}}</option>
              @endforeach
            </select>
          </div>

          @if(cando('root'))
          <div class='col-sm-6 col-md-2'>
            <label for='officer'>Officer</label>
            <select name="officer" id='officer' class="form-control">
            </select>
          </div>
          @else
          <input type='hidden' id='officer'>
          @endif
        </div>
        <br>
        <center><button onclick='searchCustomer()' class='btn btn-primary'>Search<i class='fa fa-search'></i></button></center>

        <div class="table-responsive mb-4 mt-4">
          <span id='infobait'></span>
          <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
            <thead>
              <th>FirstName</th>
              <th>LastName</th>
              <th>Email</th>
              <th>Username</th>
              <th>AccountNo</th>
              <th>Phone</th>
              <th>Account Type</th>
              <th>Balance</th>
              <th>Branch</th>
              <th>Acc. Officer</th>
              <th>Created By</th>
              <th>Created At</th>
              <th>Action</th>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="user-profile layout-spacing">
          <div class="widget-content widget-content-area" id='view_profile'>

          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>

      </div>
    </div>
  </div>
</div>

<script>
  function renderStaff() {
    $branch = $('#branch').val();
    $.ajax({
      url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
      type: 'GET',
      dataType: 'json',
      success: function(data) {
        $('select[name="officer"]').empty();

        data.forEach(function(staff) {

          $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
        });
      }
    });
  }
</script>
@endsection