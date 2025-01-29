<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\LoanGuarantor;
use App\Models\guarantor;
use App\Models\User;
use DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;

class LoanGuarantorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!cando('loan-guarantor|root')) return abort(403);
        $data = [
            'category_name' => 'loan',
            'page_name' => 'loan_guarantor',
            'page_menu' => 'loan_guarantor',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
        ];
        return view('company.loan.guarantor')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function guarantorSource(Request $request)
    {
        if (!cando('loan-guarantor|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        $search = $request->query('search', array('value' => '', 'regex' => false));
        $draw = $request->query('draw', 0);
        $start = $request->query('start', 0);
        $length = $request->query('length', 100);
        $order = $request->query('order', array(1, 'asc'));
        $filter = $search['value'];
        $sortColumns = array(
            0 => 'guarantors.id',
            1=>'users.first_name',
            2=>'users.virtual_account',
            3=>'users.phone',
            4 => 'guarantors.first_name',
            5 => 'guarantors.salary',
            6 => 'guarantors.occupation',
            7 => 'guarantors.bvn',
            8 => 'guarantors.status',
            9 => 'guarantors.phone',
            10 => 'guarantors.address',
            11 => 'guarantors.other_remarks',
            12=> 'guarantors.id',
        );
        $company_id = auth()->user()->company_id;
        $query = guarantor::join('users','guarantors.user_id','=','users.id')->where([ 'users.company_id' => $company_id])
        ->select('guarantors.*','users.first_name as fname','users.last_name as lname','users.virtual_account','users.phone as contact');
        if (!empty($filter)) {
            $query->where(function ($query) use ($filter) {
                $query->orwhere('guarantors.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('guarantors.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.first_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.last_name', 'like', '%' . $filter . '%');
                $query->orWhere('users.virtual_account', 'like', '%' . $filter . '%');
            });
        }
        $recordsTotal = $query->count();
        $sortColumnName = $sortColumns[$order[0]['column']];
        $query->orderBy($sortColumnName, $order[0]['dir'])
            ->take($length)
            ->skip($start);
        $json = array(
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => [],
        );
        $info = $query->get();
        $loop = 0;
        foreach ($info as $infoData) {
             $btnlink =Crypt::encryptString($infoData->id);
            $edit ="editModal('$btnlink')";
            $delete ="onclick=deleteG('$btnlink')";
            $btn = '<div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <a class="dropdown-item" href="#" onclick='.$edit.'>Edit</a>
                    <a class="dropdown-item" '.$delete.' href="#" >Delete Permanently</a>
                </div>
            </div>';     
            $status = $infoData->status_id;
            if($status == 1){
                $status ="<span class='badge badge-success'>Verified</span>";
            }else if($status == 2){
                $status ="<span class='badge badge-warning'>Pending</span>";
            }else if($status == 3){
                $status ="<span class='badge badge-warning'> Enquiry</span>";
            } else{
                $status ="<span class='badge badge-danger'>Declined</span>";

            }
                $loop++;
                $json['data'][] = [
                    $loop,
                    $infoData->fname.' '.$infoData->lname,
                    $infoData->virtual_account,
                    $infoData->contact,
                    $infoData->first_name.' '.$infoData->last_name,
                    $infoData->salary,
                    $infoData->occupation,
                    $infoData->bvn,
                    $status,
                    $infoData->phone,
                    $infoData->address,
                    $infoData->other_remarks,
                    $btn
                ];
            }
        return $json;
    }
 
    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreLoanGuarantorRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!cando('loan-add-guarantor|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name' => 'required|string|max:50|min:2',
            'salary' => 'required',
            'email' => 'required',
            'phone' => 'required|max:13|min:9',
            'relationship' => 'required',
            'address' => 'required',
            'occupation' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            return redirect()->back()->with('fail',$errors);
        }
        //  
        $messages ='';
            if(!restrict_to_just_my_company(['user_uuid'=>$request->customer])) return redirect()->back()->with('fail','Access Denied!'); // json_encode(array('status' => 0, 'message' => "Access Denied!"));
        try {
            $d_user= User::where('uuid',$request->customer)->first();
            
        $guarantor = new guarantor;
        $guarantor->first_name = $request->first_name;
        $guarantor->last_name = $request->last_name;
        $guarantor->salary = $request->salary;
        $guarantor->status_id = 3;
        $guarantor->phone = $request->phone;
        $guarantor->email = $request->email;
        $guarantor->relationship = $request->relationship;
        $guarantor->address = $request->address;
        $guarantor->occupation = $request->occupation;
        $guarantor->other_remarks = $request->other_remarks;
        $guarantor->bvn = $request->bvn;
        $guarantor->user_id =$d_user->id;
        $guarantor->verifyToken = 'yyy';
        $guarantor->save();
        $guarantor->verifyToken = encrypt_decrypt('encrypt', $guarantor->id) ;
        $guarantor->save();
            //code...
            if($request->file('passport')){
                $doc = $request->file('passport');
                $new_name = rand() . "." . $doc->getClientOriginalExtension();
                $file1 = $doc->storeAs("public/company/customer/guarantor_passport/$guarantor->id", $new_name);
                $guarantor->update([
                    'passport' => $file1
                ]);
            }
            $messages ="Guarantor Saved!";
            $url = url("company/loan/verify-guarantor/$guarantor->verifyToken");
            $link = "Hi, ".$guarantor->user->first_name." with phone number (".$guarantor->user->phone."), request to add you as a Guarantor click the link to acknowledge $url";
            $smsstate = sendSMS(['fname'=>$request->first_name,'lname'=>$request->last_name,'email'=>$request->email,'phone'=>$request->phone,'isExternal'=>true,'userid'=>$d_user->id], $link, true, true, 'Loan Guarantor Notification');
        } catch (\Throwable $th) {
            //throw $th;
            $messages = 'Message: ' . $th->getMessage();
        }
        if($request->file('signature')){
            try {
                //code...

                $doc = $request->file('signature');

                $new_name = rand() . "." . $doc->getClientOriginalExtension();

                $file1 = $doc->storeAs("public/company/customer/guarantor_signature/$guarantor->id", $new_name);
                $guarantor->update([
                    'signature' => $file1
                ]);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        $datax = [
            'message' => $messages,
            'status' => 1,
        ];
        return redirect()->back()->with('success',$messages);
    }
    public function scanGuarantor(Request $request){
        $checkname= guarantor::where(['first_name'=>$request->first_name,'last_name'=>$request->last_name])->orWhere('phone',$request->phone)->count();
        if($checkname > 0){
            return json_encode(['status'=>0,'message'=>'Guarantor with matching name or Phone Number already exist, please comfirm you want to proceed with this quarantor by clicking on the button below!']);
        }
        return json_encode(['status'=>1,'message'=>'proceed']);
    }
    public function verifyGuarantor($id){
        $checkname= guarantor::where(['verifyToken'=>$id])->first();
        if($checkname){
            $logo = str_replace('public', 'storage', ($checkname->user->company->logo));
            $data = [
                'category_name' => 'auth',
                'page_name' => 'auth_default',
                'has_scrollspy' => 0,
                'scrollspy_offset' => '',
                'page_menu' => ($checkname->status_id == 3)?'Verify Loan Information':'Thank You for your Response!',
                'alt_menu' => '',
                'img'=>url($logo),
                'status'=>$checkname->status_id,
                'id'=>$id,
                'guarantorData'=>($checkname->status_id == 3)?$checkname->user->first_name." ".$checkname->user->last_name." with phone number (".$checkname->user->phone."), request to add you as his guarantor for a loan he wish to apply under ".$checkname->user->company->company_name." kindly acknowledge or decline":"",
                'isReferenceValid' => false
            ];
            return view('company.loan.verifyGuarantor')->with($data);
        }else{
            return abort(404);
        }
    }
    public function quarantorAction($action,$id){
        $checkname= guarantor::where(['verifyToken'=>$id,'status_id'=>3])->first();
        if($checkname){
            if($action == 'accept'){
                $checkname->status_id = 2;
            }else if($action == 'decline'){
                $checkname->status_id = 4;
            }else{
                return redirect()->back()->with('fail',"Invalid Input action $action ");
            }
            $checkname->save();
            return redirect()->back()->with('success',"Thank you for your response!");
        }else{
            return redirect()->back()->with('fail',"Action already active!");
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\LoanGuarantor  $loanGuarantor
     * @return \Illuminate\Http\Response
     */
    public function guarantor_list(Request $request){
        $search = $request->searchTerm;
        $company_id = auth()->user()->company_id;
        $searchCustomer = $request->moredata;
        $query = guarantor::join('users','guarantors.user_id','=','users.id')->where(['guarantors.status_id' => 1, 'users.company_id' => $company_id])
        ->select('guarantors.*',DB::raw('users.first_name as fname'),DB::raw('users.last_name as lname'),);
        if($searchCustomer){
            $query = $query->where(['users.company_id'=>auth()->user()->company_id,'guarantors.status_id'=>1]);
            $query->where(function ($query) use ($searchCustomer) {
                $searchData = $searchCustomer;//single customer delete line if it multiple cutsomer
                //foreach($searchCustomer as $searchData){// for multiple customer
                    $cur_user = User::where([
                        'company_id'=>auth()->user()->company_id,
                        'uuid'=>$searchData])->first();
                    $query->orwhere('guarantors.user_id', $cur_user->id);
                // }
            });
        }else{
            $query = $query->where(['users.company_id'=>auth()->user()->company_id,'guarantors.status_id'=>7]);
        }
        if ($search != null) {
            
            if (!empty($search)) {
                $query->where(function ($query) use ($search) {
                    $query->orwhere('guarantors.first_name', 'like', '%' . $search . '%');
                    $query->orWhere('guarantors.last_name', 'like', '%' . $search . '%');
                    $query->orWhere('guarantors.salary', 'like', '%' . $search . '%');
                    $query->orWhere('guarantors.address', 'like', '%' . $search . '%');
                });
            }
        }
        $query = $query->paginate(10);
        $datainfo = [];
        if ($query) {
            foreach ($query as $querydata) {
        $id = Crypt::encryptString($querydata->id);
                $datainfo[] = ['id' =>$id, 'text' => $querydata->first_name . ' - ' . $querydata->last_name ." ($querydata->fname $querydata->lname)"];
            }
            return (json_encode($datainfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function show(LoanGuarantor $loanGuarantor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\LoanGuarantor  $loanGuarantor
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $btnlink =Crypt::decryptString($id);
        $guarantor = guarantor::find($btnlink);
        $guarantor->editid =Crypt::encryptString($guarantor->id);// Crypt::encryptString();
        $guarantor->user_id = Crypt::encryptString($guarantor->user_id);
        return $guarantor;
    }

    public function update(Request $request)
    {
        if (!cando('loan-update-guarantor|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50|min:2',
            'last_name' => 'required|string|max:50|min:2',
            'salary' => 'required',
            'status_id' => 'required|numeric|max:3',
            'phone' => 'required|max:13|min:9',
            'relationship' => 'required',
            'address' => 'required',
            'email' => 'required',
            'customer' => 'required',
            'editID' => 'required',
            'occupation' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            return redirect()->back()->with('fail',$errors);
        }
        $messages ='';
        $editID=Crypt::decryptString($request->editID);
        $request->customer=Crypt::decryptString($request->customer);
            if(!restrict_to_just_my_company(['user_id'=>$request->customer])) return redirect()->back()->with('fail','Access Denied!'); // json_encode(array('status' => 0, 'message' => "Access Denied!"));
        try {
            
            $d_user= User::where('id',$request->customer)->first();
        $guarantor = guarantor::find($editID);
        $guarantor->first_name = $request->first_name;
        $guarantor->last_name = $request->last_name;
        $guarantor->salary = $request->salary;
        $guarantor->status_id = $request->status_id;
        $guarantor->phone = $request->phone;
        $guarantor->email = $request->email;
        $guarantor->relationship = $request->relationship;
        $guarantor->address = $request->address;
        $guarantor->occupation = $request->occupation;
        $guarantor->other_remarks = $request->other_remarks;
        $guarantor->bvn = $request->bvn;
        // $guarantor->verifyToken = encrypt_decrypt('encrypt', $guarantor->id) ;
        $guarantor->user_id =$d_user->id;
        $guarantor->save();
            //code...
            if($request->file('passport')){

                $doc = $request->file('passport');

                $new_name = rand() . "." . $doc->getClientOriginalExtension();

                $file1 = $doc->storeAs("public/company/customer/guarantor_passport/$guarantor->id", $new_name);
                $guarantor->update([
                    'passport' => $file1
                ]);
            }
            $messages ="Guarantor Updated!";
        } catch (\Throwable $th) {
            //throw $th;
            $messages = 'Message: ' . $th->getMessage();
        }
        if($request->file('signature')){
            try {
                //code...

                $doc = $request->file('signature');

                $new_name = rand() . "." . $doc->getClientOriginalExtension();

                $file1 = $doc->storeAs("public/company/customer/guarantor_signature/$guarantor->id", $new_name);
                $guarantor->update([
                    'signature' => $file1
                ]);
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
        $datax = [
            'message' => $messages,
            'status' => 1,
        ];
        return redirect()->back()->with('success',$messages);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\LoanGuarantor  $loanGuarantor
     * @return \Illuminate\Http\Response
     */

    public function destroy($id)
    {
        if (!cando('loan-delete-guarantor|root')) return json_encode(['status'=>0,'message'=>'Access Denied!']);

        $btnlink =Crypt::decryptString($id);
        $guarantor = guarantor::find($btnlink);
        if(!restrict_to_just_my_company(['user_id'=>$guarantor->user_id])) return json_encode(['message' => "Access Denied!",'status' => 0]);// json_encode(array('status' => 0, 'message' => "Access Denied!"));
        $LoanGuarantor = LoanGuarantor::where('guarantor_id',$btnlink)->count();
        if($LoanGuarantor > 0){
            return json_encode(['message' => "Guarantor is currently standing for a loan!",'status' => 0]);
        }
        $guarantor->delete();
        return json_encode(['message' => "Guarantor removed!",'status' => 1]);

    }
}
