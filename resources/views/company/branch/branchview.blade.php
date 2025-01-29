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
                                <th>Branch Manager</th>
                                <th>Branch Name</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Modified By</th>
                                <th>Updated At</th>
                                <th>Created At</th>
                                    <th>Action</th>
                            </tr>
                        </thead>
                        <tbody><?php $i =1; ?>
                            @if(count($branch) > 0)
                                @foreach($branch as $branch_data)
                                <tr>
                                <td>{{$i++}}</td>
                                    <td>{{$branch_data->branch_manager?$branch_data->manager->first_name : 'none'}}</td>
                                    
                                
                                    <td>{{$branch_data->branch_name}}</td>
                                    <td>{{$branch_data->address}}</td>
                                    <td><span class="shadow-none badge badge-primary">{{$branch_data->status->name}}</span></td>
                                    <td>{{$branch_data->author->first_name}}</td>
                                    <td>{{$branch_data->modified_by?$branch_data->editor->first_name : 'none'}}</td>
                                    <td>{{$branch_data->updated_at}}</td>
                                    <td>
                                    {{$branch_data->created_at}}
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                        <button class='btn btn-secondary' ><svg xmlns="http://www.w3.org/2000/svg" onclick="edit_branch('{{$branch_data->id}}','{{$branch_data->status}}','{{$branch_data->branch_name}}','{{$branch_data->address}}','{{$branch_data->branch_manager}}')" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-edit-2 edit"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg></button>
                                        <button  class='btn btn-secondary'><svg xmlns="http://www.w3.org/2000/svg" onclick="delete_branch('{{$branch_data->id}}')" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle table-cancel"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg></button></td>
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