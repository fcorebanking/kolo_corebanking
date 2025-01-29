@extends('layouts.app')

@section('content')
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area text-center">

        <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                    <h4>Talents</h4>
                </div>
            </div>
        </div>
        <button class='btn btn-primary' onclick="talentShow()" id='add-talent'>Add Talent</button>
        <div class="row">
            <div class="col-sm-12 col-md-12 col-12 order-sm-0 order-1">
                <div class="tab-content" id="v-right-pills-tabContent">
                <div class="tab-pane fade show active" id="v-right-pills-home" role="tabpanel" aria-labelledby="v-right-pills-home-tab">
                    <div class="layout-px-spacing">

                        <div class="row layout-top-spacing" id='talentTable'>

                            <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                                <div class="table-responsive mb-4 mt-4">
                                    <span id='infobait'></span>
                                    <table id="html5-extension" class="table table-hover non-hover" style="width:100%">

                                        <thead>

                                        <th>Talent</th>
                                        <th>votes</th>
                                        <th>status</th>
                                        <th></th>
                                        </thead>
                                        <tbody>
                                            @if ($talent)
                                                @foreach ($talent as $talentdata)
                                                    <tr>
                                                        <td>{{$talentdata->user->first_name.' '.$talentdata->user->last_name}}</td>
                                                        <td>{{($talentdata->votes)}}</td>
                                                        <td>{{($talentdata->status)}}</td>
                                                        <td>
                                                            <div class="dropdown">
                                                                <button class="btn btn-warning dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    Actions
                                                                </button>
                                                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#active" onclick="remove('{{$talentdata->id}}','active')">Approve</a>
                                                                    <a class="dropdown-item" href="#"  data-toggle="modal" data-target="#remove" onclick="remove('{{$talentdata->id}}','removed')">Removed</a>
                                                                    <a class="dropdown-item" href="#"  data-toggle="modal" data-target="#clear" onclick="voteUpdate('{{$talentdata->id}}')">Clear Vote</a>

                                                                </div>
                                                                </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                            <tr></tr>
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        </div>
                        {{-- <form method='post' action="/company/esusu/schedule/product" > --}}
                            <div id='talentForm'>
                            {{ csrf_field() }}
                            <input type="hidden" class="form-control" name='user_id' id='user_id' >
                            <div class="modal-body">
                                <input type="hidden" class="form-control" name='avarter' id='avarter' aria-describedby="basic-addon3" placeholder="...">
                                <img src="" alt="Base64 Image" id='avat'>


                                <div class="col-sm-6 col-md-6">
                                    <label>Talent</label>
                                    <select name='customer' id='customerx'  onchange="document.getElementById('user_id').value = this.value" class='form-control form-small' style='margin:5px'>

                                    </select>
                                </div>
                                <label for="basic-url">Picture</label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Name</span>
                                    </div>
                                    <input type="file" class="form-control" name='user_avarter' id='user_avarter' aria-describedby="basic-addon3" placeholder="...">
                                </div>
                                <label for="basic-url">Name</label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Name</span>
                                    </div>
                                    <input type="text" class="form-control" name='name' id='name' aria-describedby="basic-addon3" placeholder="...">
                                </div>
                                <label for="basic-url">Audio Link</label>

                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Audio Link</span>
                                    </div>
                                    <input type="text" class="form-control" name='audio_link' id='audio_link' aria-describedby="basic-addon3" placeholder="...">
                                </div>
                                <label for="basic-url">Video Link</label>

                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Video Link</span>
                                    </div>
                                    <input type="text" class="form-control" name='video_link' id='video_link' aria-describedby="basic-addon3" placeholder="...">
                                </div>

                                <label for="basic-url">Category</label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">category</span>
                                    </div>
                                    <select class="form-control" id='category' name='category'>
                                        @if ($category)
                                            @foreach ($category as $categoryData)
                                                <option value="{{$categoryData->id}}">{{$categoryData->name}}</option>
                                            @endforeach

                                        @endif
                                    </select>
                                </div>
                                <label for="basic-url">Bio Info</label>
                                <div class="input-group mb-4">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon7">Bio Info</span>
                                    </div>
                                    <textarea class="form-control" name='bio_info' id='bio_info' aria-describedby="basic-addon3" placeholder="..."></textarea>
                                </div>
                                {{--    --}}
                            </div>
                            <div class="modal-footer">
                                <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                                <button type="submit" onclick="postTalent()" class="btn btn-primary" >Submit</button>
                            </div>
                        </div>
                        {{-- </form> --}}
                    </div>
                </div>


                </div>
            </div>


        </div>
        </div>



        {{-- <div class="modal fade" id="modalShow" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Esusu Product</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">

                        </button>
                    </div>

                </div>
            </div>
        </div> --}}
    </div>
    <script>
let showtable =0;
// $('#talentForm').hide()


        function talentShow(){
            // $('#modalShow').modal('show')
            if(showtable == 1){
                showtable=0
            document.getElementById('talentForm').style.display = 'block';
document.getElementById('talentTable').style.display = 'none';

            }else{
                showtable=1
            document.getElementById('talentForm').style.display = 'none';
document.getElementById('talentTable').style.display = 'block';
            }
        }talentShow()

function postTalent() {
    const name = $('#name').val();
    const avarter = $('#avarter').val();
    const bio_info = $('#bio_info').val();
    const audio_link = $('#audio_link').val();
    const video_link = $('#video_link').val();
    const user_id = $('#user_id').val();
    const category = $('#category').val();

    const data_info = { name, avarter, bio_info, audio_link, video_link, user_id, category };

    ajax_operation('post', '/company/competition/talent', data_info, 'add-talent', 'Add Talent');
}
        function remove(val,status){
            if (confirm("Are you sure, you want to remove talent?") == true) {
                ajax_operation('put', '/company/competition/talents/' + val+'/'+status, { });
                // window.location = '/company/competition/talents?status='+status
            }
            return false;
        }
        function voteUpdate(val,status){
            if (confirm("Are you sure, you want to clear vote?") == true) {
                ajax_operation('put', '/company/competition/talents/' + val, { });
                // window.location = '/company/competition/talents?status='+status
            }
            return false;
        }
        // /talents/{id} competition/talent

        document.getElementById('user_avarter').addEventListener('change', function (e) {
    const fileInput = e.target;
    const file = fileInput.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function (event) {
            const base64String = event.target.result;

            // Assign the base64 string to the avarter input text
            document.getElementById('avarter').value = base64String;

            // Set the background image of the avat element
            // const avatElement = document.getElementById("avat");
            $('#avat').attr('src',base64String)
        };

        reader.readAsDataURL(file);
    }
});


    </script>
@endsection






