<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduleEsusuProduct;
use Illuminate\Support\Facades\Validator;

class ScheduleEsusuProductController extends Controller
{
    public function index()
    {
        if (!cando('root|foodstash-product')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'schedule_esusu_product',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'esusuproduct' =>  ScheduleEsusuProduct::where(['company_id'=>auth()->user()->company_id])->get(),

        ];
        // $pageName = 'html5';
        return view('company.esusu.schedule.product')->with($data);
    }
    public function store(Request $request)
    {
        if (!cando('root|foodstash-product')) {
            abort(403);
            return 0;
        }


        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'start_date' => 'required',
            'end_date' => 'required',
            'status' => 'required',
            'minimun' => 'required',
            'maximal' => 'required',
            'circle_out_charge'=> 'required',
            'withdraw_out_charge'=> 'required',
            'agent_commission'=> 'required',

        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            return redirect()->back()->with('error', $errors);
        }
        $scheduleEsusuProductCheck = ScheduleEsusuProduct::where(['name'=>$request->name,'start_date'=>$request->start_date,'end_date'=>$request->end_date])->first();
        if($scheduleEsusuProductCheck){
            if(!$request->editid){
                return redirect()->back()->with('error', 'Product exist!');
            }else{
                if($request->editid != $scheduleEsusuProductCheck->id){
                    return redirect()->back()->with('error', 'Product exist!');
                }
            }
        }
        if($request->editid){
            ScheduleEsusuProduct::where('id',$request->editid)->update(['name'=>$request->name,'start_date'=>$request->start_date,'end_date'=>$request->end_date,'status'=>$request->status,'minimun'=>$request->minimun,'maximal'=>$request->maximal,'circle_out_charge'=>$request->circle_out_charge,'withdraw_out_charge'=>$request->withdraw_out_charge,'agent_commission'=>$request->agent_commission,'t_c'=>$request->t_c,'description'=>$request->description]);
        }else{
            ScheduleEsusuProduct::create(['name'=>$request->name,'start_date'=>$request->start_date,'end_date'=>$request->end_date,'status'=>$request->status,'minimun'=>$request->minimun,'maximal'=>$request->maximal,'company_id'=>auth()->user()->company_id,'circle_out_charge'=>$request->circle_out_charge,'withdraw_out_charge'=>$request->withdraw_out_charge,'agent_commission'=>$request->agent_commission,'t_c'=>$request->t_c,'description'=>$request->description]);
        }
        return redirect()->back()->with('success', 'Product added!');

    }
    public function Esusu_Productlist(Request $request)
    {
        if (!cando('root|foodstash-product|manage-foodstash')) {
            abort(403);
            return 0;
        }
        $search = $request->searchTerm;
        if ($search != null) {
            $user = ScheduleEsusuProduct::where(['company_id' => auth()->user()->company_id])//->whereDate('start_date','>=',date('Y-m-d'))

            ->where('name', 'like', '%' . $search . '%')

                ->paginate(60);
        } else {
            $user = ScheduleEsusuProduct::where(['company_id' => auth()->user()->company_id]) //->whereDate('start_date','>=',date('Y-m-d'))
            ->paginate(10);
        }

        $userinfo = [];
        if ($user) {
            foreach ($user as $userdata) {
                $userinfo[] = ['id' => $userdata->id, 'text' => $userdata->name . ' (' . $userdata->minimun . ' - ' . $userdata->maximal . ') '];
            }
            return (json_encode($userinfo));
        } else {
            return (json_encode(['id' => 0, 'text' => 'no data']));
        }
    }
    public function show(Request $request){

    }

}
