<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Fund;
use App\Models\Language;
use App\Models\NotifyTemplate;
use App\Models\Template;
use App\Models\Transaction;
use App\Models\UserProfile;
use App\Models\Wallet;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
	use RegistersUsers;

	public function showRegistrationForm(Request $request)
	{
		$referral = $request->referral;
		$info = json_decode(json_encode(getIpInfo()), true);
		$country_code = null;
		if (!empty($info['code'])) {
			$country_code = $info['code'][0];
		}
		$countries = config('country');
		$template = Template::where('section_name', 'register')->first();
		return view('auth.register', compact('countries', 'referral', 'country_code', 'template'));
	}

	protected $redirectTo = RouteServiceProvider::HOME;

	public function __construct()
	{
		$this->middleware('guest');
	}

	protected function validator(array $data)
	{
		$validateData = [
			'name' => ['required', 'string', 'max:255'],
			'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
			'username' => ['required', 'string', 'max:50', 'unique:users,username'],
			'password' => ['required', 'string', 'min:6', 'confirmed'],
			'phone' => ['required', 'string', 'unique:user_profiles,phone'],
		];


		if (basicControl()->reCaptcha_status_registration) {
			$validateData['g-recaptcha-response'] = ['required', 'captcha'];
		}

		return Validator::make($data, $validateData);
	}


public function createAccount($email, $phone, $name)  //shema code 2
{
    $baseUrl = "https://safe.wbalite.com/";
    $endpoint = "api/v1/users/api-register";
    $companyReference = "irGWNrhVHkGIyzp3lsNs";
    $body = [
        "email" => $email,
        "phone" => $phone,
        "last_name" => $name,
        "first_name" => $name,
        "preferred_bank" => 25,
        "dob" => "980-12-22",
        "company" => $companyReference,
    ];

    try {
        $createAccountResponse = $this->wiredbankingPostRequest($baseUrl . $endpoint, $body);

        Log::info("Wiredbanking Create Account Response", [$createAccountResponse]);

        if ($createAccountResponse && $createAccountResponse['status']) {
            return [
                'status' => true,
                'message' => "Account Created Successfully",
                'data' => $createAccountResponse['data']
            ];
        } else {
            return ['status' => false, 'message' => $createAccountResponse['message']];
        }
    } catch (\Exception $e) {
        // Log::error($e->getMessage());
        return null;
    }
}


	protected function create(array $data)
	{
		$ref_by = null;
		if (isset($data['referral'])) {
			$ref_by = User::where('username', $data['referral'])->first();
		}
		if (!isset($ref_by)) {
			$ref_by = null;
		}
        $createAccountResponse = $this->createAccount($data['email'], $data['phone'], $data['name']);  //shema code 1
        if(!$createAccountResponse['status']){
            dd($createAccountResponse);
        }
		$user = User::create([
			'name' => $data['name'],
			'ref_by' => $ref_by,
			'email' => $data['email'],
			 'account_number' => $createAccountResponse['data']['account_number'], // Shema code 3
			'username' => $data['username'],
			'password' => Hash::make($data['password']),
			'language_id' => Language::select('id')->where('default_status', true)->first()->name ?? null,
			'email_verification' => (basicControl()->email_verification) ? 0 : 1,
			'sms_verification' => (basicControl()->sms_verification) ? 0 : 1,
			'qr_link' => strRandom(20)
		]);

		$userProfile = UserProfile::firstOrCreate(['user_id' => $user->id]);
		$userProfile->phone_code = $data['phone_code'];
		$userProfile->phone = $data['phone'];
		$userProfile->save();

		return $user;
	}


	protected function registered(Request $request, $user)
	{

		$user->two_fa_verify = ($user->two_fa == 1) ? 0 : 1;
		$user->save();

		$currencies = Currency::All();
		foreach ($currencies as $currency) {
			Wallet::firstOrCreate(['user_id' => $user->id, 'currency_id' => $currency->id]);
		}

		if ($user && basicControl()->joining_bonus > 0 && basicControl()->signup_bonus_status == 1) {
			$fund = new Fund();
			$fund->user_id = $user->id;
			$fund->currency_id = basicControl()->base_currency;
			$fund->percentage = 0;
			$fund->charge_percentage = 0;
			$fund->charge_fixed = 0;
			$fund->charge = 0;
			$fund->amount = basicControl()->joining_bonus;
			$fund->email = $user->email;
			$fund->status = 1;
			$fund->note = 'Joining bonus';
			$fund->utr = (string)Str::uuid();
			$fund->save();

			updateWallet($fund->user_id, $fund->currency_id, $fund->amount, 1);
			$transaction = new Transaction();
			$transaction->amount = $fund->amount;
			$transaction->charge = $fund->charge;
			$transaction->currency_id = $fund->currency_id;
			$fund->transactional()->save($transaction);
		}

		$templates = NotifyTemplate::where('firebase_notify_status', 1)->where('notify_for', 0)->get()->unique('template_key');
		$value = array();
		foreach ($templates as $temp) {
			$value[] = $temp->template_key;
		}

		$user->notify_active_template = $value;
		$user->save();


	}

}
