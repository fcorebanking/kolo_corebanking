<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\loan;
use App\Models\LoanCommentAttachment;
use App\Models\LoanComment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Crypt;
use Image;
use Illuminate\Support\Facades\Storage;

class LoanCommentController extends Controller
{
    public function index(){
        return response()->json(LoanComment::orderBy('id', 'desc')->all());
    }
    public function store($id,Request $request){
        @ini_set('max_execution_time', 0);
		@set_time_limit(0);
        try{
		$validator = Validator::make($request->all(), [
			'message' => 'required',
        ],['attachment.required_if' => 'nullable|size:100|max:100|mimes:jpeg,JPEG,png,PNG,jpg,doc,pdf,docx,zip']);
		if ($validator->fails()) {
			if ($request->ajax()) {
				return response()->json(['status' => false, 'message' => $validator->errors()->all()]);
			} else {
				return redirect()->back()
					->withErrors($validator)
					->withInput();
			}
		}
        $id = Crypt::decryptString($id);
        $LoanComment = new LoanComment;
        $LoanComment->message = $request->message;
        $LoanComment->loan_id = $id;
        $LoanComment->created_by = auth()->user()->id;
        $LoanComment->save();
        if($request->attachment){
            try {
                $image = $request->file('attachment');
                if ($image) {
                // create a new filename for the resized image
                $filename = time()  . str_replace(' ','_',$image->getClientOriginalExtension());
                $uploadPath = "public/company/loanComment/$id";
                    // resize the image
                    // $resizedImage = Image::make($image)
                    //     ->resize(200, 200, function ($constraint) {
                    //         $constraint->aspectRatio();
                    //         $constraint->upsize();
                    //     })->encode();
                    // upload the resized image to storage
                    $upload = Storage::put($uploadPath . $filename, $image);
                    // $file1 = $doc->storeAs("$uploadPath/$user->id/$filename", $resizedImage);
                    if($upload){
                        $LoanCommentAttachment  = new LoanCommentAttachment;
                        $LoanCommentAttachment->attachment =$uploadPath . $filename;
                        $LoanCommentAttachment->loan_comment_id =$LoanComment->id;
                        $LoanCommentAttachment->save();
                    }
                }
            } catch (Exception $e) {
            }
            //
        }
		return response()->json(['status' => true, 'message' => "Message Sent!"]);
    }catch(Exception $e){
		return response()->json(['status' => false, 'message' => $e->getMessage()]);
    }
    }
    public function update(Request $request){

    }
    public function show($id){
        $id = Crypt::decryptString($id);
        return response()->json(LoanComment::with('Attachment')->with('user')->where('loan_id',$id)->orderBy('id', 'desc')->get());
    }
    public function edit($id){
        return response()->json(LoanComment::with('Attachment')->with('user')->find($id));
    }
    public function destroy($id){
        LoanCommentAttachment::where('loan_comment_id',$id)->delete();
        $comment = LoanComment::find($id)->delete();
        return response()->json(['status'=>true,'message'=>'Comment Deleted!']);
    }
}
