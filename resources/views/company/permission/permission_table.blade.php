@include('inc.styles')  
<div class="layout-px-spacing">
           
    <div class="row layout-top-spacing">

        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Created By</th>
                        <th width="280px">Action</th>
                    </tr><?php $i=0 ?>
                        @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ ++$i }}</td>
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->author->first_name }}</td>
                            <td>
                                <a class="btn btn-info" href="#" onclick="edit_role('{{$role->id}}','{{ $role->name }}')">View</a>
                             
                                <a class="btn btn-danger" href="#"  @if($role->name != 'Company Owner') onclick="delete_role('{{$role->id}}')" @else disabled @endif>Trash</a>
                             
                            </td>
                        </tr>
                        @endforeach
                    </table>
                 </div>                           
                </div>                           
            </div>                           
        </div>                           
    </div>                           
    @include('inc.scripts')