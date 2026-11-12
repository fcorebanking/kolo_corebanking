<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    public function showLoginForm(Request $request)
    {
        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => '',
        ];
        return view('auth.login')->with($data);
    }

public function login(Request $request)
{
    $request->validate([
        'username'    => 'required|string',
        'company_code' => 'required|string',
        'password'    => 'required|string',
    ]);

    // Resolve the KoloPay company using the code entered on the login form.
    $company = Company::where('loginCode', $request->company_code)->first();

    if (!$company) {
        throw ValidationException::withMessages([
            'company_code' => ['Invalid company code.'],
        ]);
    }

    if ((int) $company->status_id !== 1) {
        throw ValidationException::withMessages([
            'company_code' => ['This company is currently unavailable.'],
        ]);
    }

    try {
        $baseUrl = rtrim(config('services.finova.base_url'), '/');

        // Authenticate against Finova. The local company code is NOT sent
        // as Finova's company ID. All KoloPay customers use company 111.
        $response = \Illuminate\Support\Facades\Http::acceptJson()
            ->asJson()
            ->connectTimeout(15)
            ->timeout(90)
            ->post($baseUrl . '/users/login', [
                'username' => trim($request->username),
                'password' => $request->password,
                'company_id' => 111,
            ]);

        $responseData = $response->json();

        // Finova returns HTTP 203 when the account requires phone verification.
        if ($response->status() === 203) {
            if ($request->wantsJson()) {
                return response()->json(
                    is_array($responseData) ? $responseData : [
                        'status' => true,
                        'message' => 'Account Pending Verification',
                        'data' => [],
                    ],
                    203
                );
            }

            return back()
                ->withInput($request->only('username', 'company_code'))
                ->with('error', $responseData['message'] ?? 'Your account requires phone verification.');
        }

        if (
            !$response->successful() ||
            !is_array($responseData) ||
            ($responseData['status'] ?? false) !== true
        ) {
            $message = $responseData['message'] ?? 'Invalid login credentials.';

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => false,
                    'message' => $message,
                    'data' => null,
                ], $response->status() >= 400 ? $response->status() : 401);
            }

            throw ValidationException::withMessages([
                'username' => [$message],
            ]);
        }

        $finovaData = $responseData['data'] ?? [];
        $finovaUser = $finovaData['user'] ?? [];
        $finovaToken = $finovaData['access_token'] ?? null;

        if (!is_array($finovaUser) || empty($finovaToken)) {
            throw new \RuntimeException(
                'Finova login response did not contain the customer profile and access token.'
            );
        }

        // Confirm that the returned Finova account belongs to KoloPay.
        if ((int) ($finovaUser['company_id'] ?? 0) !== 111) {
            throw ValidationException::withMessages([
                'username' => ['This account is not registered under KoloPay.'],
            ]);
        }

        // Synchronize by username and the LOCAL KoloPay company ID.
        // Never use Finova's company_id (111) as the local company_id.
        $user = User::where('username', $finovaUser['username'] ?? $request->username)
            ->where('company_id', $company->id)
            ->first();

        if (!$user) {
            $user = new User();

            $user->company_id = $company->id;
            $user->username = $finovaUser['username'] ?? trim($request->username);
            $user->uuid = (string) \Illuminate\Support\Str::uuid();

            // These are the local KoloPay mappings used for proxy customers.
            $user->bank_id = 126;
            $user->branch_id = 182;
            $user->account_officer_id = 1199;

            $user->user_type_id = 1;
            $user->status_id = $finovaUser['status_id'] ?? 1;
            $user->password = \Illuminate\Support\Facades\Hash::make(
                $request->password
            );
        }

        // Synchronize profile fields returned by Finova.
        $user->first_name = $finovaUser['first_name'] ?? $user->first_name;
        $user->last_name = $finovaUser['last_name'] ?? $user->last_name;
        $user->email = $finovaUser['email'] ?? $user->email;
        $user->phone = $finovaUser['phone'] ?? $user->phone;
        $user->dob = $finovaUser['dob'] ?? $user->dob;
        $user->gender = $finovaUser['sex'] ?? $finovaUser['gender'] ?? $user->gender;
        $user->address = $finovaUser['address'] ?? $user->address;
        $user->bvn = $finovaUser['bvn'] ?? $user->bvn;
        $user->nin = $finovaUser['nin'] ?? $user->nin;

        $user->virtual_account = $finovaUser['virtual_account'] ?? $user->virtual_account;
        $user->account_reference = $finovaUser['account_reference'] ?? $user->account_reference;

        $user->profile_pic = $finovaUser['profile_pic'] ?? $user->profile_pic;
        $user->bvn_status = $finovaUser['bvn_status_id'] ?? $user->bvn_status;
        $user->phone_verification_status_id =
            $finovaUser['phone_verification_status_id'] ?? $user->phone_verification_status_id;

        // Preserve the fixed local mappings if this is a newly synchronized user.
        // Existing local mappings are not overwritten here.

        // Save the Finova-issued token for subsequent KoloPay proxy requests.
        $user->access_token = $finovaToken;

        $user->save();

        // Authenticate the synchronized local user for the web session.
        Auth::login($user, $request->filled('remember'));

        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'message' => 'Login Successful',
                'data' => [
                    'name' => trim($user->first_name . ' ' . $user->last_name),
                    'token' => $finovaToken,
                ],
            ]);
        }

        return redirect()->intended($this->redirectPath());

    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        report($e);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to connect to Finova. Please try again later.',
                'data' => null,
            ], 503);
        }

        return back()
            ->withInput($request->only('username', 'company_code'))
            ->with('error', 'Unable to connect to Finova. Please try again later.');

    } catch (ValidationException $e) {
        throw $e;

    } catch (\Throwable $e) {
        report($e);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while synchronizing your account.',
                'data' => null,
            ], 500);
        }

        return back()
            ->withInput($request->only('username', 'company_code'))
            ->with('error', 'An error occurred while signing you in.');
    }
}

    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'company_code'    => 'required|string',
            'password'        => 'required|string',
        ]);
    }

    protected function attemptLogin(Request $request)
    {
        $attempt = $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );

        logInfo("{$attempt}", "Attempt Login Password Guard");

        if ($attempt != null) {
            return $attempt;
        } else {
            $attemptHashPassword = $this->hashCredentials($request);
            return $attemptHashPassword;
        }

    }

    protected function credentials(Request $request)
    {
        $test = $request->all();
        $test = json_encode($test);
        logInfo("{$test}", "Company Request login");
        $credentials              = $request->only($this->username(), 'password');
        $credentials['status_id'] = 1;
        $company                  = Company::where('loginCode', $request->company_code)->first();
        logInfo("$company", "Company login");
        if ($company) {
            $credentials['company_id'] = $company->id;
        } else {
            $credentials['company_id'] = 'hello';
        }

        $password = json_encode($credentials);

        //    logInfo("{$password}", "Company login credential");

        return $credentials;
    }

    public function hashCredentials(Request $request)
    {
        $credentials = $request->only($this->username(), 'password');

        $user = User::where('username', $credentials['username'])->first();

        if ($user->password == null) {
            return back()->with('error', "There is a problem with your login details");
        }

        $checkpassword = Hash::check($credentials['password'], $user->password);

        logInfo("{$checkpassword}", "Attempt Login Password Hash");

        return $checkpassword;
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        $user      = json_encode(User::where('username', $request->username)->first());
        $login     = ['username' => $request->username, 'password' => $request->password];
        $authLogin = Auth::attempt($login);

        //  logInfo("{$user}", "User Details");

        logInfo("{$authLogin}", "User Details Auth Attempt");

        if ($this->guard()->user() != null) {
            $auth = $this->guard()->user();
        } else {
            $auth = $user;
        }

        if ($response = $this->authenticated($request, $auth)) {
            return $response;
        }

        $first_name = $auth->first_name ?? $user->first_name;
        $last_name  = $auth->last_name ?? $user->last_name;

        // User::where('id',$auth->id)->update(['login_sms_otp_status'=>0]);
        //   $msg = "<p>Hi {$first_name} $last_name, we noticed a new login on your account around " . now() . " and we thought it wise to notify you.</p>
        //       <p>If this is not You ... please quickly reset your password and enable 2factor SMS or Google 2factor to protect your account.</p>
        //       ";
        // sendSMS($auth->id, $msg, true, false, 'WBA Login Security','',0,false,3);

        return $request->wantsJson()
        ? new JsonResponse([], 204)
        : redirect()->intended($this->redirectPath());
    }

    protected function authenticated(Request $request, $user)
    {
        //
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        $company = Company::where('loginCode', $request->company_code)->first();

        if (! $company) {
            $this->incrementLoginAttempts($request);
            throw ValidationException::withMessages([
                'company_code' => ['Invalid company code.'],
            ]);
        }

        //   throw ValidationException::withMessages([
        //         $this->username() => [trans('auth.failed')],
        //     ]);
    }

    public function username()
    {
        return 'username';
    }

    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($response = $this->loggedOut($request)) {
            return $response;
        }

        return $request->wantsJson()
        ? new JsonResponse([], 204)
        : redirect('/');
    }

    protected function loggedOut(Request $request)
    {
        //
    }

    protected function guard()
    {
        return Auth::guard();
    }
}
