<?php

namespace App\Http\Controllers;

use App\Models\companyOpeningclosingtime;
use App\Models\company_status;
use Illuminate\Http\Request;
use App\Http\Requests\UpdatecompanyOpeningclosingtimeRequest;

class CompanyOpeningclosingtimeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware("custom_permission:timekeeper|root");
    }
    public function index()
    {
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu'=>'openingclose',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'days'=>companyOpeningclosingtime::where('company_id',auth()->user()->company_id)->get(),
            'status'=>company_status::all(),
            ];
            // $pageName = 'html5';
       
            return view('company.openingclose.main')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorecompanyOpeningclosingtimeRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $token = csrf_token();
        if( $request->id == 0){
        $companyOpeningclosingtime = new companyOpeningclosingtime;
        $companyOpeningclosingtime->company_id = auth()->user()->company_id;
        $companyOpeningclosingtime->status_id =1;
        $companyOpeningclosingtime->created_by = auth()->user()->id;
        }else{
            $companyOpeningclosingtime =companyOpeningclosingtime::findorfail($request->id);
            $companyOpeningclosingtime->modified_by = auth()->user()->id;
        }
         $byday_date = $request->byday_date;
       
         if( $byday_date == 'day'){
            if( $request->id == 0){
                if(companyOpeningclosingtime::where(['company_id'=>auth()->user()->company_id,'nday'=>$request->snday])->count() > 0) {
                    return response()->json(["status"=>0,"token"=>$token,'message' =>"Duplicate Entry!"]);  
                }
            }
            $companyOpeningclosingtime->holiday =null;
            $companyOpeningclosingtime->nday = $request->snday;
             $companyOpeningclosingtime->starttime =  $request->start;
            $companyOpeningclosingtime->endtime =  $request->end;
            
           
         
         }else{
            if( $request->id == 0){
                if(companyOpeningclosingtime::where(['company_id'=>auth()->user()->company_id,'holiday'=>$request->holiday])->count() > 0) {
                    return response()->json(["status"=>0,"token"=>$token,'message' =>"Duplicate Entry!"]);     

                }
            }
            $companyOpeningclosingtime->holiday =  $request->holiday;
            $companyOpeningclosingtime->nday = null;
            $companyOpeningclosingtime->starttime =  null;
           $companyOpeningclosingtime->endtime =  null;
         }
        //  $companyOpeningclosingtime->modified_by = auth()->user()->id;
        
        if($companyOpeningclosingtime->save()){
        return response()->json(["status"=>1,"token"=>$token,'message' =>"Operation Successful!"]);
          }else{
            return response()->json(["status"=>0,"token"=>$token,'message' =>"Operation Failed!"]);     
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\companyOpeningclosingtime  $companyOpeningclosingtime
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        $companyOpeningclosingtime = companyOpeningclosingtime::where('company_id',auth()->user()->company_id)->get();
       
       $data = [];
        if(count($companyOpeningclosingtime) > 0){
            $i=0;
            foreach($companyOpeningclosingtime as $product_data){
                $i++;        
                $days = ($product_data->nday !=null)?checkdate_day($product_data->nday):'Holiday';
                $status = $product_data->status_id?$product_data->status->name:'none';
                $modified = ($product_data->modified_by!=null)?$product_data->editor->first_name : 'none';
                $appender = '';      
                $appender .="<tr>
                                 <td>$i</td>

                                                <td>";
                                                $appender .= $days;
                                                $appender .= "</td>
                                                <td>$product_data->holiday</td>

                                                <td>$product_data->starttime</td>
                                                <td>$product_data->endtime</td>";
                                              
                                               
                                                $appender .= "<td><span class='shadow-none badge badge-primary'>".$status."</span></td>
                                                <td>".$product_data->author->first_name."</td>
                                                <td>".$modified."</td>
                                                <td>$product_data->updated_at</td>
                                                <td>$product_data->created_at </td>
                                                <td>
                                                    <div class='btn-group'>
                                                    <button class='btn btn-secondary' ><svg xmlns='http://www.w3.org/2000/svg' onclick='edit_item($product_data->id)'  width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-edit-2 edit'><path d='M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z'></path></svg></button>
    
                                                  <form action='opening/delete/$product_data->id' method='post'>  <input type='hidden' class='token' name='_token' value='".csrf_token()."'>
                                                  <button type='submit' class='btn btn-secondary'><svg xmlns='http://www.w3.org/2000/svg'  width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' stroke-linejoin='round' class='feather feather-x-circle table-cancel'><circle cx='12' cy='12' r='10'></circle><line x1='15' y1='9' x2='9' y2='15'></line><line x1='9' y1='9' x2=15 y2=15></line></svg></button></form></td>

                                                       
                                                    </div>
                                                </td>
                                            </tr>";
                                            $data[] = $appender;
                                                  }
                                                  }
        return json_encode($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\companyOpeningclosingtime  $companyOpeningclosingtime
     * @return \Illuminate\Http\Response
     */
    public function edit($company_name,$id)
    {
        $companyOpeningclosingtime = companyOpeningclosingtime::where('id',$id)->first();
        return json_encode($companyOpeningclosingtime);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatecompanyOpeningclosingtimeRequest  $request
     * @param  \App\Models\companyOpeningclosingtime  $companyOpeningclosingtime
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatecompanyOpeningclosingtimeRequest $request, companyOpeningclosingtime $companyOpeningclosingtime)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\companyOpeningclosingtime  $companyOpeningclosingtime
     * @return \Illuminate\Http\Response
     */
    public function destroy($company_name,$id)
    {
        companyOpeningclosingtime::findorfail($id)->delete();
        return redirect()->back()->with('success','Record Deleted!');
    }
}
