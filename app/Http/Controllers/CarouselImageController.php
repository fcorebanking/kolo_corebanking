<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\company_status;
use App\Models\CarouselImage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
class CarouselImageController extends Controller
{
    public function main()
    {
        if (!cando('manage-caousel|root')) return abort(403);
        $data = [
            'category_name' => 'datatable',
            'page_name' => 'html5',
            'page_menu' => 'manage_caousel',
            'has_scrollspy' => 0,
            'scrollspy_offset' => '',
            'alt_menu' => 0,
            'status' => company_status::all(),
            'carousel' => CarouselImage::where(['company_id'=>auth()->user()->company_id])->get(),

        ];
        // $pageName = 'html5';

        return view('company.carousel')->with($data);
    }
    public function save(Request $request){
        $validation = Validator::make($request->all(), [
            "cover_pic" => 'required',
            "img_link" => 'required',
            "status" => 'required',
        ]);

        $state = 'error';
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            return redirect()->back()->with('fail', $errors);

        }


        $company_product = $request->file('cover_pic');
        $CarouselImage =new CarouselImage;
            if ($company_product) {
                $productName = time().str_replace(' ','_',$company_product->getClientOriginalName());

                $imgpath = $company_product->move("carousel/" . auth()->user()->company_id, $productName);

                $CarouselImage->image_url =url($imgpath);
                $CarouselImage->image_path =$imgpath;
            }
            $CarouselImage->company_id =auth()->user()->company_id;
            $CarouselImage->link_url =$request->img_link;
            $CarouselImage->status_id =$request->status;
            $CarouselImage->created_by =auth()->user()->id;
            $CarouselImage->modified_by =auth()->user()->id;
            $CarouselImage->save();
            return redirect()->back()->with('success', 'Image Uploaded!');

    }
    public function update(Request $request){
        $validation = Validator::make($request->all(), [
            "img_link" => 'required',
            "status" => 'required',
        ]);

        $state = 'error';
        if ($validation->fails()) {
            $errors = '';
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                // Go through each message for this field.
                foreach ($messages as $message) {
                    $errors .= $message . ' ';
                }
            }
            return redirect()->back()->with('fail', $errors);
        }
        $company_product = $request->file('cover_pic');
        $CarouselImage = CarouselImage::where(['id'=>$request->id,'company_id'=>auth()->user()->company_id])->first();
            if ($company_product) {
                $productName = time().str_replace(' ','_',$company_product->getClientOriginalName());
                $imgpath = $company_product->move("carousel/" . auth()->user()->company_id, $productName);
                if($imgpath){
                    $file_path = public_path($CarouselImage->image_path);
                    File::delete($file_path);
                }
                $CarouselImage->image_url =url($imgpath);
                $CarouselImage->image_path =$imgpath;
            }
            $CarouselImage->link_url =$request->img_link;
            $CarouselImage->status_id =$request->status;
            $CarouselImage->modified_by =auth()->user()->id;
            $CarouselImage->save();
            return redirect()->back()->with('success', 'Image Updated!');

    }
    public function destroy($id){
        $CarouselImage = CarouselImage::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->first();
        $file_path = public_path($CarouselImage->image_path);
        File::delete($file_path);
        CarouselImage::where(['id'=>$id,'company_id'=>auth()->user()->company_id])->delete();
        return redirect()->back()->with('success', 'Slide Deleted!');

    }
}
