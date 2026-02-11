<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $gift_card_image = $request->giftCardImage;
        $bankDetails     = [
            "account_name" => "{$user->first_name} {$user->last_name}",
            "account_number" => $user->accounts->first()->virtual_accnum,

        ];
        logInfo(json_encode($userNeonID), "userNeonID");
        logInfo(json_encode($gift_card_image), "gift_card_image");
        logInfo(json_encode($user), "User Finova");
        $response = DB::connection('neon')->table('gift_card_transactions')->insert([
            'id'                    => (string) Str::ulid(),
            'userId'                => $userNeonID->id,
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

            $userNeonDB = DB::connection('neon')->table('users')->where($attributes)->first();

            logInfo(json_encode($userNeonDB), "User Neon DB");

            if ($userNeonDB == null) {
                $this->updateUserInfo($user);
                $userNeonDB = DB::connection('neon')->table('users')->where($attributes)->first();
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
        return User::where('uuid', $id)->first();
    }

    private function updateUserInfo($user)
    {
        DB::connection('neon')->table('users')->updateOrInsert(
            ['email' => $user->email],
            // Unique check
            [
                'id'           => (string) Str::ulid(),
                'firstName'    => $user->first_name,
                'lastName'     => $user->last_name,
                'mobileNumber' => $user->phone,
                'password'     => $user->password,
                'avatar'       => 'finova',

            ]// Data to update/create
        );

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
