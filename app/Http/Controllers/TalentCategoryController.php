<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TalentCategory;
use App\Models\talents;
use App\Models\vote;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
class TalentCategoryController extends Controller
{
    function __construct()
    {
        $this->middleware("custom_permission:root");
        // $this->is_root = cando('root');
    }
    public function index()
    {
        if (!cando('root')) {
            abort(403);
            return 0;
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'competition_category',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'talent_category'=>TalentCategory::where(['company_id'=>auth()->user()->company_id])->get(),
            'talent'=>talents::where(['company_id'=>auth()->user()->company_id])->count(),
            'vote'=>talents::where(['company_id'=>auth()->user()->company_id,'status'=>'active'])->sum('votes'),
        ];
        return view('company.competition.category')->with($data);
    }
    public function store(Request $request){
        try{
        // TalentShowSettings
        $validation = Validator::make($request->all(), [
            "name" => 'required',
        ]);
        if ($validation->fails()) {
            Session::flash('error', "All fields are required");
        return redirect('/company/competition/category');
        }
        $data = ['company_id'=>auth()->user()->company_id,'name'=>$request->name];
        if(TalentCategory::where($data)->count()){
        Session::flash('error', 'Category already exist');
        return redirect('/company/competition/category');

        }
        TalentCategory::Create($data);

        Session::flash('success', 'Category Saved');
        return redirect('/company/competition/category');
        }catch(Exception $error){
            Session::flash('error', 'Error creating request');
        }
    }
    public function update($id,Request $request){
        try{
        // TalentShowSettings
        $validation = Validator::make($request->all(), [
            "name" => 'required',
        ]);
        if ($validation->fails()) {
            Session::flash('error', "All fields are required");
        return redirect('/company/competition/category');
        }
        $data = ['name'=>$request->name];
        TalentCategory::where('id',$id)->update($data);

        Session::flash('success', 'Category Updated');
        return redirect('/company/competition/category');
        }catch(Exception $error){
            Session::flash('error', 'Error creating request');
        }
    }
}
