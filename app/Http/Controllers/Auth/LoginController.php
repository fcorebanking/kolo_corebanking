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
        $this->validateLogin($request);
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }
        logInfo("Working", "attempt login first");
        if ($this->attemptLogin($request)) {
            logInfo("Session reqest Working", "Session Request");
            if ($request->hasSession()) {
                $request->session()->put('auth.password_confirmed_at', time());
            }
            logInfo("Working", " attempt login");
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
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

        return $this->guard()->attempt(
            $this->credentials($request), $request->filled('remember')
        );
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
        logInfo("{$password}", "Company login credential");
        return $credentials;
    }

    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);
        $auth = $this->guard()->user();
        if ($response = $this->authenticated($request, $auth)) {
            return $response;
        }
        // User::where('id',$auth->id)->update(['login_sms_otp_status'=>0]);
        $msg = "<p>Hi $auth->first_name $auth->last_name, we noticed a new login on your account around " . now() . " and we thought it wise to notify you.</p>
            <p>If this is not You ... please quickly reset your password and enable 2factor SMS or Google 2factor to protect your account.</p>
            ";
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
            throw ValidationException::withMessages([
                'company_code' => ['Invalid company code.'],
            ]);
        }

        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
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
