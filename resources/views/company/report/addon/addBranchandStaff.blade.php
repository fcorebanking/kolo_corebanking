@if(cando('select-cutomer-branch|root'))
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label for="">Branch</label>
                        <select name="branch" id='branch' onchange="renderStaff()" class="form-control">
                            <option value="">Select Branch</option>
                            @if(count(getbranchlist()))
                            @foreach(getbranchlist() as $branchData)
                                <option value="{{Crypt::encryptString($branchData->id)}}">
                                {{$branchData->branch_name}}</option>
                            @endforeach
                            @endif
                        </select>
                        <span id="branch_error" class="text-danger font-weight-bold"></span>
                    </div>
                </div>
                @else
                <input type='hidden' value='{{Crypt::encryptString(auth()->user()->branch_id)}}'
                    name="branch" id='branch'>
                @endif

                @if(cando('select-cutomer-branch|root'))
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label for="">Account Officer</label>
                        <select name="officer" id='officer' class="form-control">

                        </select>
                    </div>
                    <script>
                        function renderStaff() {
                            $branch = $('#branch').val();
                            $.ajax({
                                url: '/company/{{auth()->user()->company->short_name}}/branch/branch_staff/' + $branch,
                                type: 'GET',
                                dataType: 'json',
                                success: function (data) {
                                    $('select[name="officer"]').empty();
                                    $('select[name="officer"]').append('<option value="">All</option>')
                                    data.forEach(function (staff) {
                                        $('select[name="officer"]').append('<option value="' + staff.uuid + '">' + staff.first_name + ' ' + staff.last_name + '</option>')
                                    });
                                }
                            });
                        }
                    </script>
                </div>
                @else
                <input type='hidden' value='{{auth()->user()->uuid}}' name="officer" id='officer'>
                @endif
