@extends('layouts.app')

@section('content')

<div class="row layout-top-spacing">
    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
        <div class="widget-content widget-content-area br-6">
        <div class="container">
            <div class="row flex-lg-nowrap">
            <div class="col-12 col-lg-auto mb-3" style="width: 200px;">
                <div class="card p-3">
                <div class="e-navlist e-navlist--active-bg">
                    <ul class="nav">
                    <li class="nav-item" id='profileBTN'><a class="nav-link px-2 active"  href="#"><i class="fa fa-fw fa-fa-th mr-1"></i><span>Profile</span></a></li>
                    <li class="nav-item" id='passwordBTN'><a class="nav-link px-2" href="#"  ><i class="fa fa-fw fa-key mr-1"></i><span>Password</span></a></li>
                    <li class="nav-item" id='pinBTN'><a class="nav-link px-2" href="#"  ><i class="fa fa-fw fa-key mr-1"></i><span>Pin</span></a></li>

                    </ul>
                </div>
                </div>
            </div>

            <div class="col">
                <div class="row">
                <div class="col mb-3">
                    <div class="card">
                    <div class="card-body">
                        <div class="e-profile">
                        <div class="row">
                            <div class="col-12 col-sm-auto mb-3">
                            <div class="user-avatar">

                                <?php $img = url(str_replace('public', 'storage', auth()->user()->profile_pic)); ?>


                            </div>
                            <div class="mx-auto" style="width: 140px;">
                                <div class="d-flex justify-content-center align-items-center rounded" style="height: 140px; background-img: url('{{$img}}');">
                                <!-- <span style="color: rgb(166, 168, 170); font: bold 8pt Arial;">140x140</span> -->
                                <img   src="{{auth()->user()->profile_pic == null ? 'http://safe.wbalite.com/wba/public/passports/default_image.jpg' : $img}}" id='img' alt="{{auth()->user()->username}}" width='140' height="140">

                                </div>
                            </div>
                            </div>
                            <div class="col d-flex flex-column flex-sm-row justify-content-between mb-3">
                            <div class="text-center text-sm-left mb-2 mb-sm-0">
                                <h4 class="pt-sm-2 pb-1 mb-0 text-nowrap">{{auth()->user()->first_name.' '.auth()->user()->last_name}}</h4>
                                <p class="mb-0">{{'@'.auth()->user()->username}}</p>
                                <div class="text-muted"><small><?php echo \Carbon\Carbon::createFromTimeStamp(strtotime(auth()->user()->last_login))->diffForHumans() ?></small></div>
                                <div class="mt-2">

                                <label for='upload'  class="btn btn-primary pencil profile-setting" type="button">
                                    <i class="fa fa-fw fa-camera"></i>
                                    <span>Change Photo</span>
                                </label>

                                </div>
                            </div>
                            <div class="text-center text-sm-right">
                                <span class="badge badge-secondary">{{(auth()->user()->staffpermission)?auth()->user()->staffpermission->staffrole->name:'no Role'}}</span>
                                <div class="text-muted"><small>Joined {{ \Carbon\Carbon::parse(auth()->user()->created_at)->format('d M Y')}}</small></div>

                                <span class="badge badge-success">@if(isset(auth()->user()->wallet->commission)) Commission: {{number_format(auth()->user()->wallet->commission,2)??'None'}} @endif</span>
                                <span class="badge badge-warning">@if(isset(auth()->user()->staff_settlement_wallet->balance)) Settlement Account: {{number_format(auth()->user()->staff_settlement_wallet->balance,2)??'None'}} @endif</span>
                                {{--  --}}
                            </div>
                            </div>
                        </div>
                        <ul class="nav nav-tabs">
                            <li class="nav-item"><a href="#" class="active nav-link state">Profile Settings</a></li>
                        </ul>
                        <div class="tab-content pt-3">
                            <div class="tab-pane active">
                            <form class="form" novalidate="" method='post' action='/company/staff-profile-post' enctype="multipart/form-data">
                                @csrf
                                <input type='hidden' id='statustxt' name='status' value='profile'>
                                <input type='file' accept='.jpeg,.jpg,.png' id="upload" name='avatar' style="display:none">

                                <div class="row profile-setting">
                                <div class="col">
                                    <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                        <label>First Name</label>
                                        <input class="form-control" type="text" disabled name="first_name" placeholder="John Smith" value="{{auth()->user()->first_name}}">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="form-group">
                                        <label>Last Name</label>
                                        <input class="form-control" type="text" disabled name="last_name" placeholder="johnny.s" value="{{auth()->user()->last_name}}">
                                        </div>
                                    </div>
                                    </div>
                                    <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                        <label>Phone</label>
                                        <input class="form-control" type="number" disabled  value="{{auth()->user()->phone}}">
                                        </div>
                                    </div>

                                    </div>
                                    <div class="row">
                                    <div class="col">
                                        <div class="form-group">
                                        <label>Email</label>
                                        <input class="form-control" type="email" disabled value='{{auth()->user()->email}}' placeholder="user@example.com">
                                        </div>
                                    </div>
                                    </div>

                                        <label for="c-branch">Branch</label>
                                        <div class="input-group mb-4">
                                            <select id="c-branch" name='branch' class="form-control" required>
                                                <option selected="" value="">Choose branch</option>
                                                @foreach($branch as $branchs)
                                                <option value='{{Crypt::encryptString($branchs->id)}}' @if(auth()->user()->branch_id == $branchs->id) selected @endif>{{$branchs->branch_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>



                                    <div class="row">
                                    <div class="col mb-3">
                                        <div class="form-group">
                                        <label>Address</label>
                                        <textarea class="form-control" name='address' rows="5" placeholder="Address">{{auth()->user()->address}}</textarea>
                                        </div>
                                    </div>
                                    </div>

                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Settlement Wallet</label>
                                            <input class="form-control" type="number" name='settlement_wallet_id'  value='{{auth()->user()->staff_settlement_wallet->wb_account_details->virtual_accnum??''}}' placeholder="112...">
                                            </div>
                                        </div>
                                        </div>
                                </div>
                                </div>

                                <div class="row password-setting">
                                    <div class="col-md-12 col-sm-6 mb-3">
                                        <div class="mb-2"><b>Change Password</b></div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Current Password</label>
                                            <input class="form-control" type="password" name='current_password' placeholder="••••••">
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>New Password</label>
                                            <input class="form-control" type="password" name='new_password' placeholder="••••••">
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Confirm <span class="d-none d-xl-inline">Password</span></label>
                                            <input class="form-control" type="password" name='comfirm_password' placeholder="••••••"></div>
                                        </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row pin-setting">
                                    <div class="col-md-12 col-sm-6 mb-3">
                                        <div class="mb-2"><b>Change Pin</b></div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Enter Password</label>
                                            <input class="form-control" name='mypassword' type="password" placeholder="••••">
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Enter Old Pin</label>
                                            <input class="form-control" name='old_pin' type="password" placeholder="••••">
                                            </div>
                                        </div>
                                        </div>
                                        <div class="row">
                                        <div class="col">
                                            <div class="form-group">
                                            <label>Enter New Pin <span class="d-none d-xl-inline">New Pin</span></label>
                                            <input class="form-control" name='new_pin' type="password" placeholder="••••"></div>
                                        </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                <div class="col d-flex justify-content-end">
                                    <button class="btn btn-primary" type="submit">Save Changes</button>
                                </div>
                                </div>
                            </form>

                            </div>
                        </div>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="col-12 col-md-3 mb-3">
                    <div class="card mb-3">
                    <div class="card-body">
                        <div class="px-xl-12">
                        <a href='{{ url("/logout")}}' class="btn btn-block btn-secondary">
                            <i class="fa fa-sign-out"></i>
                            <span>Logout</span>
                        </a>
                        </div>
                        <div class="px-xl-12">
                        <hr>
                            <button onclick="userpswreset('{{auth()->user()->uuid}}')" class="btn btn-block btn-warning" type="button"><span id='srpassword'>Send Reset Password</span></button>
                            <button onclick="userpinreset('{{auth()->user()->uuid}}')" class="btn btn-block btn-danger" type="button"><span id='srpin'>Send Reset Pin</span></button>
                            @if (isset(auth()->user()->staff_settlement_wallet->wb_account_details->virtual_accnum))
                                <button class="btn btn-block btn-success" type="button" data-toggle="modal" data-target="#exampleModalWithdraw"><span>Withdraw</span></button>
                            @endif

                        </div>

                    </div>
                    </div>
                    <div class="card">
                    <div class="card-body">
                        <h6 class="card-title font-weight-bold">Support</h6>
                        <p class="card-text">Always contact admin for assistants.</p>

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


<div class="modal fade" id="exampleModalWithdraw" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Withdraw to your settlement wallet</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
            <form action='/company/staff-settlement-withdraw' method='post'>
                @csrf

            <div class="form-group">

            <input type='number' name='amount' class='form-control' placeholder='Amount'>
            </div>
            <div class="form-group">

            <button type="submit" class="btn btn-primary">Withdraw</button>

            </div>
        </form>
    </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

@endsection
