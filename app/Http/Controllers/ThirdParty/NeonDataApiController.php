<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Image;

class NeonDataApiController extends WebhookController
{
    private $token;
    private $email;
    private $password;

    public function __construct()
    {

    }

    public function getGiftCards()
    {
        $response = DB::connection('neon')->table('gift_card')->select('id', 'name', 'ImageUrl')->get();

        return response()->json($response, 200);
    }

    public function getGiftCardSubCategories()
    {
        $response = DB::connection('neon')->table('gift_card_sub_categories')->select('id', 'label', 'rate', 'minValue', 'maxValue', 'giftCardId')->get();

        return response()->json($response, 200);
    }

    public function giftCardTransaction(Request $request)
    {
        $userNeonID      = $this->getUserInfo($request);
        $user            = $this->getFinova4T5UserInfo($request->user_id);
        $gift_card_image = $this->uploadGiftCard($request);
        $bankDetails     = [
            "account_name" => "{$user->first_name} {$user->last_name}",
            "account_number" => $user->accounts->first()->virtual_accnum,

        ];
        $response = DB::table('gift_card_transactions')->insert([
            'userId'                => $userID,
            'AdminTransactionTypes' => 'GIFT_CARD_SALE',
            'giftCardImages'        => $gift_card_image,
            'amount'                => (float) $request->amount, // Manual timestamps required
            'bankDetails'           => json_encode($bankDetails),
            'giftCardType'          => "",
        ]);

        return $response;

    }

    private function getUserInfo(Request $request)
    {

        try {
            $user       = $this->getFinova4T5UserInfo($request->user_id);
            $attributes = ['email' => $user->email];

            logInfo(json_encode($user), "User Finova");

            $userNeonDB = DB::table('users')->where($attributes)->first();

            logInfo(json_encode($userNeonDB), "User Neon DB");

            if ($userNeonDB == null) {
                $this->updateUserInfo($user);
                $userNeonDB = DB::table('users')->where($attributes)->first();
                logInfo(json_encode($userNeonDB), "User Neon DB");
            }
            return $userNeonDB;
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "User Neon DB error log");

            return ['status' => false, 'message' => 'Server Error...'];
        }
    }

    private function getFinova4T5UserInfo($id)
    {
        return User::where('id', $id)->first();
    }

    private function updateUserInfo($user)
    {
        DB::table('users')->updateOrInsert(
            ['email' => $user->email],
            // Unique check
            [

                'firstName'    => $user->first_name,
                'lastName'     => $user->last_name,
                'mobileNumber' => $user->phone,
                'password'     => $user->password,
                'avatar'       => 'finova',

            ]// Data to update/create
        );

    }

    private function uploadGiftCard(Request $request)
    {
        try {

            $image = $request->file('giftCardImage');
            if ($image) {
                // create a new filename for the resized image
                $filename   = time() . str_replace(' ', '_', $image->getClientOriginalExtension());
                $uploadPath = "public/company/customer/gift_card/$user->uuid";

                // resize the image
                $resizedImage = Image::make($image)
                    ->resize(200, 200, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    })
                    ->encode();

                // upload the resized image to storage
                $upload = Storage::put($uploadPath . $filename, $resizedImage);
                // $file1 = $doc->storeAs("$uploadPath/$user->id/$filename", $resizedImage);

                $gift_card = $uploadPath . $filename;

                logInfo(json_encode($gift_card), "User Finova Gift Card");

                return $gift_card;
            }
        } catch (Exception $e) {
            //die($e);
        }
    }

    private function NeonDataApiHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Origin'        => "https://finova.ng",
            'Authorization' => 'Bearer ' . $this->token,
        ];

        return $header;
    }

}
