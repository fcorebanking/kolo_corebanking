<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\EcomerceProductModel;
use App\Models\company_status;
use App\Models\OrderModel;
use App\Models\EcommerceCategory;


class EcomerceProductModelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'ecommerce_product',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'categories'=>EcommerceCategory::where('company_id',auth()->user()->company_id)->get(),
            'product'=>EcomerceProductModel::where('company_id',auth()->user()->company_id)->get(),
            'status' => company_status::all(),
        ];
        return view('company.product_ecommerce.product')->with($data);
    }
    public function category()
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'ecommerce_category',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'data'=>EcommerceCategory::where('company_id',auth()->user()->company_id)->get(),

        ];
        return view('company.product_ecommerce.category')->with($data);
    }
    public function viewDetaild($id){
        $ecommerceCategory =EcommerceCategory::where(['company_id'=>auth()->user()->company_id,'id'=>$id])->first();
        return $ecommerceCategory;
    }
    public function saveCategory(Request $request){
        $eco =EcommerceCategory::where(['company_id'=>auth()->user()->company_id,'name'=>$request->name])->first();
        if(!$eco){
            $ecommerceCat =new EcommerceCategory;
            $ecommerceCat->company_id =auth()->user()->company_id;
            $ecommerceCat->created_by=auth()->user()->id;
            $ecommerceCat->name = $request->name;

            $ecommerceCat->tag= str_replace(' ','_',strtolower($request->name));
            $ecommerceCat->save();
            return redirect()->back()->with('success', 'Category Set!');
        }else{
            return redirect()->back()->with('fail', 'Category already Set!');
        }
    }
    public function updateCategory(Request $request){
        $ecommerceCat = EcommerceCategory::where(['company_id'=>auth()->user()->company_id,'id'=>$request->catID])->first();
        if($ecommerceCat){
            $ecommerceCat->company_id =auth()->user()->company_id;
            $ecommerceCat->created_by=auth()->user()->id;
            $ecommerceCat->name = $request->edit_name;
            $ecommerceCat->tag= str_replace(' ','_',strtolower($request->edit_name));
            $ecommerceCat->save();
            return redirect()->back()->with('success', 'Category Set!');
        }else{
            return redirect()->back()->with('fail', 'Category already Set!');
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!auth()->user()->company->setting->access_ecommerce){
            return abort(403);
        }
        $validator = Validator::make($request->all(), [
            'actual_amount' => 'required',
            'amount' => 'required',
            'cover_pic' => 'required',
            'name' => 'required',
            'quantity' => 'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            return redirect()->back()->with('fail',$validator->messages());
        }
        try{
            $ecomerce = new EcomerceProductModel;
            $ecomerce->company_id = auth()->user()->company_id;
            $ecomerce->name = $request->name;
            $ecomerce->amount= $request->amount;
            $ecomerce->quantity= $request->quantity;
            $ecomerce->actual_amount= $request->actual_amount;
            $ecomerce->delivery_fee= $request->delivery_fee;

            $destinationPath = 'eproducts';
            if($request->file('cover_pic') != null){
                $file = $request->file('cover_pic');
                $filename = str_replace(' ','_',time().$file->getClientOriginalName());
                $path = $file->move($destinationPath,$filename);
                $ecomerce->cover_img=url($destinationPath.'/'.$filename);
                $ecomerce->cover_path=$destinationPath.'/'.$filename;
            }

            $ecomerce->ecommerce_categorie_id = $request->categories;
            $ecomerce->description = $request->description;
            $ecomerce->status_id=$request->status;
            $ecomerce->created_by=auth()->user()->id;
            $ecomerce->save();
            return redirect()->back()->with('success',"Product Uploaded Successfully!");
        }catch(Exception $e){
            return redirect()->back()->with('fail',$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EcomerceProductModel  $ecomerceProductModel
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return json_encode(EcomerceProductModel::where('id',$id)->first());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EcomerceProductModel  $ecomerceProductModel
     * @return \Illuminate\Http\Response
     */
    public function UpdateEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required',
            // 'cover_pic' => 'required',
            'name' => 'required',
            'editid'=>'required',
            'actual_amount'=>'required',
            'quantity'=>'required',
        ]);
        if ($validator->fails()) {
            $errors = '';
            foreach ($validator->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= "$message ";
                }
            }
            $datax = [
                'message' => $errors,
                'status' => 0,
            ];
            return redirect()->back()->with('fail',$validator->messages());
        }
        try{
            $ecomerce =  EcomerceProductModel::where('id',$request->editid)->first();
            if($ecomerce){
                $ecomerce->company_id = auth()->user()->company_id;
                $ecomerce->name = $request->name;
                $ecomerce->amount= $request->amount;
                $ecomerce->actual_amount= $request->actual_amount;
                $destinationPath = 'eproducts';
                if($request->file('cover_pic') != null){
                    $file = $request->file('cover_pic');
                    $filename = str_replace(' ','_',time().$file->getClientOriginalName());
                    $path = $file->move($destinationPath,$filename);
                    $ecomerce->cover_img=url($destinationPath.'/'.$filename);
                    $ecomerce->cover_path=$destinationPath.'/'.$filename;
                }
            $ecomerce->delivery_fee= $request->delivery_fee;

                $ecomerce->description = $request->description;
                $ecomerce->quantity= $request->quantity;
                $ecomerce->ecommerce_categorie_id = $request->categories;
                $ecomerce->status_id=$request->status;
                $ecomerce->created_by=auth()->user()->id;
                $ecomerce->save();
                return redirect()->back()->with('success',"Product Uploaded Successfully!");
            }else{
                return redirect()->back()->with('fail',"Product Uploaded Fail!");
            }
        }catch(Exception $e){
            return redirect()->back()->with('fail',$e->getMessage());
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Request  $request
     * @param  \App\Models\EcomerceProductModel  $ecomerceProductModel
     * @return \Illuminate\Http\Response
     */
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EcomerceProductModel  $ecomerceProductModel
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // DeliveryDetailModel transactions
        if(OrderModel::where('ecomerce_product_model_id',$id)->count() == 0){
            $com = EcomerceProductModel::where('id',$id)->delete();
            return redirect()->back()->with('success',"Product Deleted Successfully!");
        }else{
            return redirect()->back()->with('fail',"Product with order cannot be deleted!");
        }
    }
}
