@include('inc.styles')  
<div class="layout-px-spacing">
                
                <div class="row layout-top-spacing">
                
                    <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                        <div class="widget-content widget-content-area br-6">
                            <div class="table-responsive mb-4 mt-4">
                                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                                    <thead>
                                        <tr>
                                        <th>S/N</th>
                                            <th>IMG</th>
                                            <th>Product</th>
                                            
                                            <th>Interest</th>
                                            <th>Min Withdraw</th>
                                            <th>Max Deposit</th>
                                            <th>Maintenance Charge</th>
                                            <th>Frequency</th>
                                            <th>Created By</th>
                                            <th>Modified By</th>
                                            <th>Updated At</th>
                                            <th>Created At</th>
                                             <th>Action</th>
                                        </tr> 
                                    </thead>
                                    <tbody><?php $i =1; ?>
                                        @if(count($product) > 0)
                                            @foreach($product as $product_data)
                                            <tr>
                                            <td>{{$i++}}</td>
                                               
                                            
                                                <td>{{$product_data->savings_product}}</td>
                                                <td>{{$product_data->interest_rate}}%</td>

                                                <td>{{$product_data->minimum_withdraw}}</td>
                                                <td>{{$product_data->maximum_deposit}}</td>
                                                <td>{{$product_data->maintenance_charges}}</td>
                                               
                                                <td><span class="shadow-none badge badge-primary">{{$product_data->frequency_id?$product_data->frequency->frequency:'none'}}</span></td>
                                                <td>{{$product_data->author->first_name}}</td>
                                                <td>{{$product_data->modified_by?$product_data->editor->first_name : 'none'}}</td>
                                                <td>{{$product_data->updated_at}}</td>
                                                <td>
                                                {{$product_data->created_at}}
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                    <button class='btn btn-secondary' ><svg xmlns="http://www.w3.org/2000/svg" onclick="edit_product('{{$product_data->id}}','{{$product_data->savings_product}}','{{$product_data->interest_rate}}','{{$product_data->frequency_id}}','{{$product_data->minimum_withdraw}}','{{$product_data->maximum_deposit}}','{{$product_data->maintenance_charges}}')" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 edit"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg></button>
    
                                                  <button  class='btn btn-secondary'><svg xmlns="http://www.w3.org/2000/svg" onclick="delete_record('{{$product_data->id}}')" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle table-cancel"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button></td>

                                                       
                                                    </div>
                                                </td>
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
        @include('inc.scripts')


       