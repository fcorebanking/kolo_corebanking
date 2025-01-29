@extends('layouts.app')

@section('content')
<div class="layout-px-spacing">
                <div class="row" id="cancel-row">
                
                    <div class="col-xl-12 col-lg-12 col-sm-12 layout-top-spacing layout-spacing">
                        <div class="widget-content widget-content-area br-6">
                            <div id="invoice-list_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4 no-footer">
                               
                          <div class="table-responsive">
                             <table id="invoice-list" class="table table-hover dataTable no-footer" style="width: 100%;" role="grid" aria-describedby="invoice-list_info">
                                <thead>
                                    <tr role="row">
                                        <th class="checkbox-column sorting_disabled" rowspan="1" colspan="1" aria-label=" Record no. " style="width: 32px;">
                                        <label class="new-control new-checkbox checkbox-primary m-auto">
                                        <input type="checkbox" class="new-control-input chk-parent select-customers-info" id="customer-all-info">
                                        <span class="new-control-indicator"></span><span style="visibility:hidden">c</span>
                                        </label>
                                    </th>
                                    <th class="sorting_asc" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-sort="ascending" aria-label="Invoice Id: activate to sort column descending" style="width: 70px;">TXN ID</th>
                                    <th class="sorting" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending" style="width: 153px;">Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-label="Email: activate to sort column ascending" style="width: 201px;">Email</th>
                                    <th class="sorting" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending" style="width: 87px;">Date</th>
                                    <th class="sorting" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending" style="width: 58px;">Amount</th>
                                    <th class="sorting" tabindex="0" aria-controls="invoice-list" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending" style="width: 53px;">Status</th></tr>
                                </thead>
                                <tbody>
                                @if(count($listtxn) > 0)
                                @foreach($listtxn as $recdata)  
                                <tr role="row">
                                        <td class="checkbox-column"><label class="new-control new-checkbox checkbox-primary  m-auto">
                                        <input type="checkbox" class="new-control-input child-chk select-customers-info" id="customer-all-info">
                                        <span class="new-control-indicator"></span><span style="visibility:hidden">c</span>
                                        </label></td>
                                        <td class="sorting_1"><a href="apps_invoice-preview.html"><span class="inv-number">#{{$recdata->TXNlog->transaction_reference}}</span></a></td>
                                        <td>
                                            <div class="d-flex">
                                                <div class="usr-img-frame mr-2 rounded-circle"> 
                                                    <img alt="avatar" class="img-fluid rounded-circle" src="{{asset(str_replace('public','storage',$recdata->TXNlog->author->profile_pic))}}">
                                                </div>
                                                <p class="align-self-center mb-0 user-name">{{$recdata->TXNlog->author->first_name.' '.$recdata->TXNlog->author->last_name}}</p>
                                            </div>
                                        </td>
                                        <td><span class="inv-email"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-mail"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg> {{$recdata->TXNlog->author->email }}</span></td>
                                        <td><span class="inv-date"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> {{$recdata->TXNlog->created_at }}</span></td>
                                        <td><span class="inv-amount">${{$recdata->TXNlog->amount }}</span></td>

                                        <td><span class="badge badge-{{$recdata->TXNlog->remark->remark_class }} inv-status">{{$recdata->TXNlog->remark->transaction_remark }}</span></td>
                                      
                                    </tr>
                                    @endforeach
                                    @endif
                               
                                    
                                    </tbody>
                            </table>
                        </div>
                        
                        </div>
                    </div>

                </div>
            </div>
            @endsection