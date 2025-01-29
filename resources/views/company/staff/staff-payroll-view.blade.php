@include('inc.styles')
<div class="layout-px-spacing">
    @if(cando(['root','manage-staff']))
    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Role</th>
                                <th>Payroll Setup</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(count($staff) > 0)
                            @foreach($staff as $rdata)
                            <tr>
                                <td>{{$rdata->id}}</td>

                                <td>{{$rdata->first_name}} {{$rdata->last_name}}</td>
                                <td>{{$rdata->branch_id?$rdata->branch->branch_name:'none'}}</td>
                                <td><span class="shadow-none badge badge-primary">{{$rdata->status->name}}</span></td>
                                <?php
                                                $staffrole = $rdata->staffpermission?$rdata->staffpermission->staffrole->name:'';
                                                ?>
                                <td>{{($staffrole)}}</td>

                                <td>
                                    <span class="badge badge-{{$rdata->payroll_setup == 0 ? 'danger' : 'success'}}">
                                        {{$rdata->payroll_setup == 0 ? "Not Setup" : "Completed"}}
                                    </span>
                                </td>

                                <td>
                                    {{$rdata->created_at}}
                                </td>
                                <td>
                                    @if($rdata->payroll_setup == 0)
                                    <button class='btn btn-info'
                                        onclick="add_payroll('{{$rdata->first_name}} {{$rdata->last_name}}', '{{$rdata->id}}')">Setup</button>
                                    @endif
                                    @if($rdata->payroll_setup == 1)
                                    <button class='btn btn-warning'
                                        onclick="edit_payroll('{{$rdata->id}}')">Edit</button>
                                    @endif
                                    <!-- <button class='btn btn-secondary' @if($staffrole !='Company Owner' )
                                            onclick="delete_staff('{{$rdata->uuid}}','2')" @else disabled @endif><svg
                                                xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round"
                                                class="feather feather-x-circle table-cancel">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg></button> -->
                                </td>


                </div>
                </td>
                </tr>
                @endforeach
                @endif
                </tbody>

                <tfoot>
                    <tr>
                        <th>S/N</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Role</th>
                        <th>Payroll Setup</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
                </table>
            </div>
        </div>
    </div>
    @else
    <div class='alert alert-warning'>You dont have enough privilage to access this module</div>
    @endif
</div>

</div>
@include('inc.scripts')