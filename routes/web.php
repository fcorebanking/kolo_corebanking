<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ClosingBalanceController;
use App\Http\Controllers\ExternalloanController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Livewire\BulkTransactionComponent;
use App\Http\Livewire\CashFlowComponent;
use App\Http\Livewire\CustomerSavingComponent;
use App\Http\Livewire\KYCForm;
use App\Http\Livewire\LedgerAccount;
use App\Http\Livewire\LedgerAccountEdit;
use App\Http\Livewire\LedgerAccountList;
use App\Http\Livewire\LedgerDetails;
use App\Http\Livewire\LedgerGeneral;
use App\Http\Livewire\LedgerSettingsComponent;
use App\Http\Livewire\ProfitAndLossComponent;
use App\Http\Livewire\TrialBalance;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FALaravel\Support\Authenticator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/view-reversable', [App\Http\Controllers\UtilityController::class, 'reverseCron']);
// Route::get('/importLedger', [App\Http\Controllers\TestController::class, 'calculatePrincipalPayment']);
// Route::get('/importLedgerSetting', [App\Http\Controllers\TestController::class, 'settingsImport']);
// Route::get('/view-ledger-setting', [App\Http\Controllers\TestController::class, 'index']);
Route::get('/customer/delete-customer-account', [App\Http\Controllers\CustomerController::class, 'delete_account_request'])->name('delete-account-request');
Route::post('/customer/delete-customer-account', [App\Http\Controllers\CustomerController::class, 'delete_account_request'])->name('submit-form');

Route::get('customer-saving-product-webhook', [App\Http\Controllers\CustomersavingsController::class, 'webhook']);
Route::get('customer-saving-product-callback', [App\Http\Controllers\CustomersavingsController::class, 'callBack']);
Route::get('/kyc-form', KYCForm::class)->name('kyc-form');

Route::get('stripe-payment', [App\Http\Controllers\StripeController::class, 'index']);
Route::post('stripe-payment', [App\Http\Controllers\StripeController::class, 'stripePost'])->name('stripe.post'); //action="{{ route('stripe.post') }}"
Route::get('stripe-success', [App\Http\Controllers\StripeController::class, 'success']);
Route::get('stripe-fail', [App\Http\Controllers\StripeController::class, 'fail']);

Route::get('admin-stripe-payment', [App\Http\Controllers\adminstripeController::class, 'index']);
Route::post('admin-stripe-payment', [App\Http\Controllers\adminstripeController::class, 'stripePost'])->name('stripeadmin.post'); //action="{{ route('stripe.post') }}"
Route::get('admin-stripe-success', [App\Http\Controllers\adminstripeController::class, 'success']);
Route::get('admin-stripe-fail', [App\Http\Controllers\adminstripeController::class, 'fail']);

Route::get('admin-company-wallet-webhook', [App\Http\Controllers\CompanyWalletTXNController::class, 'viewResponse']);
Route::post('admin-company-wallet-topup', [App\Http\Controllers\CompanyWalletTXNController::class, 'topup']);
Route::post('admin-company-wallet-add', [App\Http\Controllers\CompanyWalletTXNController::class, 'addWallet']);

Route::get('/logout', function () {
    Auth::logout();
    Session::flush();

    (new Authenticator(request()))->logout();

    return Redirect::to('/login');
});
Route::get('storage/{filename}', function ($filename) {
    $path = storage_path('app/public/' . $filename);

    if (! file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return response($file)->header('Content-Type', $type);
})->where('filename', '.*');
Route::get('create-plan', [App\Http\Controllers\CustomersavingsController::class, 'paystack_Test']);
Route::get('create-plan-sub', [App\Http\Controllers\CustomersavingsController::class, 'paystack_Test']);
Route::post('create-target-saving', [App\Http\Controllers\CustomersavingsController::class, 'storeTargetSaving']);
Route::post('change-target-saving-agent', [App\Http\Controllers\CustomersavingsController::class, 'changeAgent']);

// Route::get('/resetbalance/{id}', [HomeController::class, 'resetbalance']);

Route::get('/apply/{ref}', [ExternalloanController::class, 'index']);
Route::post('/company/book-external-loan', [ExternalloanController::class, 'externalLoan']);
Route::post('remitta/mandate/ipn-wba-xvh-2222', [App\Http\Controllers\RemitasalaryhistoryController::class, 'remittapayment']);
Route::get('testingshit', [App\Http\Controllers\TestController::class, 'calculatePrincipalPayment']);
//
Route::get('/company/change-currency-to-company-default', [App\Http\Controllers\CustomerController::class, 'resetWBCurrency']);

Route::get('/resetStatement', [App\Http\Controllers\UserReportController::class, 'resetStatement']);
Route::get('/sms-API', [App\Http\Controllers\CompanyController::class, 'smsAPI']);
Route::post('/sms-API', [App\Http\Controllers\CompanyController::class, 'smsAPI']);
Route::get('/company/loan/verify-guarantor/{id}', [App\Http\Controllers\LoanGuarantorController::class, 'verifyGuarantor']);
Route::get('/company/loan/guarantor/action/{action}/{id}', [App\Http\Controllers\LoanGuarantorController::class, 'quarantorAction']);
Route::get('/cron', [App\Http\Controllers\CronJobController::class, 'index']);
Route::get('/account/verify-bank-account', [App\Http\Controllers\TransactionController::class, 'verifyBankAccount']);

//coinremitter
Route::get('/coinremitter/btc_webhook', [App\Http\Controllers\CoinremitterController::class, 'webhook']);
Route::get('/coinremitter/success', [App\Http\Controllers\CoinremitterController::class, 'success']);
Route::get('/coinremitter/fail', [App\Http\Controllers\CoinremitterController::class, 'fail']);
Route::get('/coinremitter/notify', [App\Http\Controllers\CoinremitterController::class, 'notify']);
// Route::get('/loan/repaymentSwap', [App\Http\Controllers\PendingRepaymentController::class, 'swapall']);
//add to auth
//coinremitter
Route::get('/coinremitter', [App\Http\Controllers\CoinremitterController::class, 'balancecheck']);
Route::get('/coinremitter/balance-check', [App\Http\Controllers\CoinremitterController::class, 'balancecheck']);
Route::get('/coinremitter/get_coin_rate', [App\Http\Controllers\CoinremitterController::class, 'get_coin_rate']);
Route::get('/coinremitter/get_new_address', [App\Http\Controllers\CoinremitterController::class, 'get_new_address']);
Route::get('/coinremitter/validate_address', [App\Http\Controllers\CoinremitterController::class, 'validateAddress']);
Route::get('/coinremitter/withdraw', [App\Http\Controllers\CoinremitterController::class, 'withdraw']);
Route::get('/coinremitter/txncheck', [App\Http\Controllers\CoinremitterController::class, 'transaction']);
Route::get('/coinremitter/createInvoice', [App\Http\Controllers\CoinremitterController::class, 'createInvoice']);
Route::get('/coinremitter/getInvoice', [App\Http\Controllers\CoinremitterController::class, 'getInvoiceData']);
Route::get('/coinremitter/getCryptoRate', [App\Http\Controllers\CoinremitterController::class, 'getCryptoRate']);
Route::get('/coinremitter/get_transaction_by_address', [App\Http\Controllers\CoinremitterController::class, 'get_transaction_by_address']);
Route::get('/get-token', [App\Http\Controllers\VideosdkModelController::class, 'tokengenerator']);
Route::post('/create-meeting', [App\Http\Controllers\VideosdkModelController::class, 'tokengenerator'])->name('create-meeting');
Route::get('/validate-meeting/{id}', [App\Http\Controllers\VideosdkModelController::class, 'tokengenerator'])->name('validate-meeting');
Route::post('/start-recording', [App\Http\Controllers\VideosdkModelController::class, 'tokengenerator'])->name('start-recording');
Route::post('/stop-recording', [App\Http\Controllers\VideosdkModelController::class, 'tokengenerator'])->name('stop-recording');
Route::get('/meeting/savemeetinginfo', [App\Http\Controllers\VideosdkModelController::class, 'saveMeetingInfo'])->name('saveMeetingInfo');
Auth::routes();

Route::get('/email/verify', function () {

    $userAccount      = \auth()->user();
    $verificationHash = \generateAPIKey(12);

    $userAccount->updated_at        = Carbon::now();
    $userAccount->verification_hash = $verificationHash;
    $userAccount->save();

    logInfo($verificationHash, "My Verification Hash");

    $url = url("verify-account/" . $verificationHash);

    $msg = 'A Verification email has been sent to your email account. Click on the link in the email to verify your account<br><br>';
    $msg .= '<a href="' . $url . '">Verify Account</a>';

    if (\auth()->user()->email_verified_at != null) {

        $otpCode = \generateOtp();

        $userAccount = auth()->user();

        $userAccount->otp = $otpCode;
        $date             = Carbon::now()->addMinutes(10)->format('Y-m-d H:i:s');

        $userAccount->otp_expiration_date = $date;
        $userAccount->save();

        $msg = "You request to reset your Password/Pin.. Do not share with anyone. kindly find OTP.. $otpCode";

        sendVansoSMS(auth()->user()->phone, $msg);
        return redirect('/phone-verification');
    }

    $sendSms = sendEmail("Account Activation Email", $msg, $userAccount);

    $data = [
        'category_name'    => 'auth',
        'page_name'        => 'auth_default',
        'has_scrollspy'    => 0,
        'scrollspy_offset' => '',
        'page_menu'        => 'Verify Email',
        'alt_menu'         => '',
        'isReferenceValid' => false,
    ];

    return view('auth.verify')->with($data);
})->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/home');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::get('/security/2factor', [App\Http\Controllers\LoginController::class, 'sendOTP'])->middleware('auth');
Route::post('/security/2factor', [App\Http\Controllers\LoginController::class, 'verifyOTP'])->middleware('auth');
Route::post('/security/2factor-resend', [App\Http\Controllers\LoginController::class, 'resendOTP'])->middleware('auth');
Route::get('/', function () {
    return redirect('/dashboard');
});
Route::group(['middleware' => ['auth_factor', '2fa']], function () {
    Route::post('/2fa', function () {
        return redirect(route('dashboard'));
    })->name('2fa');
    Route::middleware('auth')->get('/transaction/verify-flutterwave-setup-fee-payment', [App\Http\Controllers\TransactionController::class, 'verifyFlutterwaveSetupFee'])->name('verify-flutterwave-setup-fee')->middleware('log.route');

    Route::post('/company/transaction/get-current-exchange-rates', [App\Http\Controllers\TransactionController::class, 'getExchangeRates'])->name('get-current-exchange-rates');
    Route::post('/transaction/verify-bank-account', [App\Http\Controllers\TransactionController::class, 'verifyBankAccount'])->name('verify-bank-account');
    Route::post('/transaction/verify-wallet', [App\Http\Controllers\TransactionController::class, 'verifyWallet'])->name('verify-wallet');
    Route::get('/verify-account/{verification_hash}', [App\Http\Controllers\Auth\RegisterController::class, 'verifyAccount'])->name('verify-account');
    Route::get('/resend-email/{verification_hash}', [App\Http\Controllers\Auth\RegisterController::class, 'resendEmil'])->name('resend-email');
    Route::get('/ma-resend-email', [App\Http\Controllers\Auth\RegisterController::class, 'mainResendEmil'])->middleware('auth')->name('main-resend');

    Route::middleware('auth')->get('/phone-verification', [App\Http\Controllers\HomeController::class, 'phoneVerification'])->name('phone-verification');
    Route::middleware('auth')->get('/terms-of-service-agree', [App\Http\Controllers\HomeController::class, 'termsOfServiceAgree'])->name('terms-of-service-agree');
    Route::middleware('auth')->get('/terms-of-service-disagree', [App\Http\Controllers\HomeController::class, 'termsOfServiceDisagree'])->name('terms-of-service-disagree');
    Route::middleware('auth')->get('/initiate-setup-fee-transaction', [App\Http\Controllers\TransactionController::class, 'initiateSetupFeeTransaction'])->name('initiate-setup-fee');
    Route::middleware('auth')->post('/phone-verification-page', [App\Http\Controllers\HomeController::class, 'phoneVerificationPage'])->name('phone-verification-page');
    Route::middleware('auth')->get('/resend-otp', [App\Http\Controllers\HomeController::class, 'resendOtp'])->name('resend-otp');
    Route::middleware('auth')->post('/verify-phone-number', [App\Http\Controllers\HomeController::class, 'verifyPhoneNumber'])->name('verify-phone-number');

    Route::middleware('auth')->post('/transaction/verify-setup-fee-payment', [App\Http\Controllers\TransactionController::class, 'verifySetupFeePayment'])->middleware('log.route');

    Route::group(['middleware' => ['auth', 'check_email_verify']], function () {

        //firbase messanger
        Route::get('/fcm-messanger', [HomeController::class, 'messanger'])->name('fcmmessanger');
        Route::patch('/fcm-token', [HomeController::class, 'updateToken'])->name('fcmToken');
        Route::post('/send-notification', [HomeController::class, 'notification'])->name('notification');

        //meeting
        Route::get('/meeting', [App\Http\Controllers\VideosdkModelController::class, 'index']);

        Route::group(['middleware' => 'check_closing_time'], function () {
            Route::group(['middleware' => 'checksubscription'], function () {

                // Route::controller('cron', 'CronJobController ');

                Route::get('/smstest', [App\Http\Controllers\CompanyController::class, 'smstest']);

                Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'dashboard'])->name('dashboard');
                Route::get('/company-dashboard', [App\Http\Controllers\HomeController::class, 'dashboard'])->name('home-index');

                Route::post('/dashboard/fund-company-wallet', [App\Http\Controllers\HomeController::class, 'fundwallet']);

                Route::post('/dashboard/generate-ussd-string', [App\Http\Controllers\HomeController::class, 'generate_ussd_string']);
                Route::post('/dashboard/veriy-phone-number', [App\Http\Controllers\RegisterController::class, 'verifyPhoneNumber']);

                Route::get('/dashboarddata', [App\Http\Controllers\HomeController::class, 'dashboard_json']);
                Route::get('/dashboarddatafull', [App\Http\Controllers\HomeController::class, 'dashboard_json_full']);

                //Route::group(['middleware' => 'role:Company'], function () {

                Route::group(['middleware' => ['checkcompany']], function () {

                    Route::prefix('company')->name('company.')->group(function () {
                        //balancesheet accounting report
                        Route::prefix('accounting')->name('accounting.')->group(function () {

                            Route::get('/closing-balance', [ClosingBalanceController::class, 'index'])->name('closingBalancesheet');
                            Route::get('/verify_closing_balance', [ClosingBalanceController::class, 'verify'])->name('verify_closingBalancesheet');
                            Route::post('/close_balance', [ClosingBalanceController::class, 'closeAccount'])->name('closeAccountBalance');
                            Route::post('/open_balance', [ClosingBalanceController::class, 'openAccount'])->name('openAccountBalance');

                            Route::get('/closing-branch', [ClosingBalanceController::class, 'BranchPage'])->name('openAccountBranchBalance');
                            Route::get('/closing-company', [ClosingBalanceController::class, 'CompanyPage'])->name('openAccountCompanyBalance');

                            Route::get('/balance-sheet-page', [ReportController::class, 'balancesheetpage'])->name('balancesheetpage');
                            Route::get('/balance-sheet', [ReportController::class, 'balancesheet'])->name('balancesheet');
                            Route::get('/view-cash-flow', CashFlowComponent::class);
                            Route::get('/view-profit-and-loss', ProfitAndLossComponent::class);
                            Route::get('/add-ledger-account', LedgerAccount::class)->name('ledger-account');
                            Route::get('/edit-ledger-account/{id}', LedgerAccountEdit::class)->name('edit-ledger-account');
                            Route::get('/view-ledger-account', LedgerAccountList::class)->name('ledger-list');
                            Route::get('/edit-ledger-account/{id}', LedgerAccountEdit::class)->name('ledgerAccountRecord.edit');
                            Route::get('/general-ledger', LedgerGeneral::class);
                            Route::get('/trial-balance', TrialBalance::class);
                            Route::get('/ledger-setting', LedgerSettingsComponent::class)->name('ledger-setting');
                            Route::get('/ledger-details/{id}', LedgerDetails::class)->name('ledger-details');
                            Route::get('/ledger-details', LedgerDetails::class)->name('ledger-details-single');
                        });
                        Route::prefix('customer-saving')->name('accounting.')->group(function () {
                            Route::get('/', CustomerSavingComponent::class);
                        });
                        Route::prefix('competition')->name('competition.')->group(function () {
                            Route::get('/', [App\Http\Controllers\TalentShowSettingsController::class, 'index']);
                            Route::post('/', [App\Http\Controllers\TalentShowSettingsController::class, 'store']);
                            Route::get('/category', [App\Http\Controllers\TalentCategoryController::class, 'index']);
                            Route::post('/category', [App\Http\Controllers\TalentCategoryController::class, 'store']);
                            Route::post('/category/{id}', [App\Http\Controllers\TalentCategoryController::class, 'update']);
                            Route::get('/talents', [App\Http\Controllers\TalentsController::class, 'index'])->name('talents_page');
                            Route::put('/talents/{id}/{status}', [App\Http\Controllers\TalentsController::class, 'setStatus'])->name('talents_status');
                            Route::put('/talents/{id}', [App\Http\Controllers\TalentsController::class, 'resetVote']);

                            Route::get('/votes', [App\Http\Controllers\TalentsController::class, 'votePage'])->name('votes_page');
                            Route::post('/talent', [App\Http\Controllers\TalentsController::class, 'addTalent']);

                        });

                        Route::get('/advert-banner', [App\Http\Controllers\CarouselImageController::class, 'main']);
                        Route::post('/advert-banner', [App\Http\Controllers\CarouselImageController::class, 'save']);
                        Route::post('/update-advert-banner', [App\Http\Controllers\CarouselImageController::class, 'update']);
                        Route::post('/delete-slide/{id}', [App\Http\Controllers\CarouselImageController::class, 'destroy']);

                        Route::post('/', [App\Http\Controllers\ExpensesController::class, 'reset']);
                        Route::get('/{company_name}/dashboard', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::get('/{company_name}/transfer-voucher', [App\Http\Controllers\CompanyValutController::class, 'transferVoucher']);
                        Route::get('/{company_name}/vault', [App\Http\Controllers\CompanyValutController::class, 'index']);
                        Route::get('/vault-ajax', [App\Http\Controllers\CompanyValutController::class, 'history']);
                        Route::post('/vault/send-transfer', [App\Http\Controllers\CompanyValutController::class, 'transfer_wallet'])->middleware('log.route');
                        Route::post('/vault/send-wallet', [App\Http\Controllers\CompanyValutController::class, 'deposit_wallet'])->middleware('log.route');
                        Route::post('/vault/send-staff-wallet', [App\Http\Controllers\CompanyValutController::class, 'staff_wallet'])->middleware('log.route');
                        Route::post('/vault/send-staff-comission', [App\Http\Controllers\CompanyValutController::class, 'staff_comission'])->middleware('log.route');
                        Route::post('/vault/send-branch-wallet', [App\Http\Controllers\CompanyValutController::class, 'branch_wallet']);
                        Route::post('/vault/create-vault', [App\Http\Controllers\CompanyValutController::class, 'create_vault']);
                        Route::post('/vault/send-voucher', [App\Http\Controllers\CompanyValutController::class, 'send_voucher']);
                        Route::post('/vault/approve-voucher/{id}', [App\Http\Controllers\CompanyValutController::class, 'approve_voucher']);
                        Route::post('/vault/reject-voucher/{id}', [App\Http\Controllers\CompanyValutController::class, 'reject_voucher']);
                        Route::post('/vault/view-vault', [App\Http\Controllers\CompanyValutController::class, 'show_vaults']);
                        //Coinremitter storecoin
                        Route::get('/{company_name}/coinremitter/coin', [App\Http\Controllers\CoinremitterCoinController::class, 'index']);
                        Route::post('/coinremitter/storecoin', [App\Http\Controllers\CoinremitterCoinController::class, 'store']);
                        Route::post('/coinremitter/storerate', [App\Http\Controllers\CoinremitterCoinController::class, 'storeRate']);
                        Route::post('/coinremitter/updatecoin', [App\Http\Controllers\CoinremitterCoinController::class, 'storeUpdate']);
                        Route::post('/coinremitter/edit/{id}', [App\Http\Controllers\CoinremitterCoinController::class, 'edit']);
                        Route::delete('/coinremitter/deletecoin/{id}', [App\Http\Controllers\CoinremitterCoinController::class, 'destroy']);
                        Route::get('/{company_name}/coinremitter/settings', [App\Http\Controllers\CoinremitterCoinController::class, 'settings']);
                        Route::post('/coinremitter/save-settings', [App\Http\Controllers\CoinremitterCoinController::class, 'saveSetting']);
                        Route::post('/coinremitter/update-settings', [App\Http\Controllers\CoinremitterCoinController::class, 'updateSetting']);
                        Route::post('/coinremitter/edit-setting/{id}', [App\Http\Controllers\CoinremitterCoinController::class, 'editSetting']);
                        Route::delete('/coinremitter/delete-setting/{id}', [App\Http\Controllers\CoinremitterCoinController::class, 'destroySetting']);

                                                                                                                                   // expenses
                        Route::get('/{company_name}/expenses/type', [App\Http\Controllers\ExpenseTypeController::class, 'index']); //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::get('/{company_name}/expenses/main', [App\Http\Controllers\ExpensesController::class, 'index']);    //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');

                        Route::get('/expenses/table', [App\Http\Controllers\ExpenseTypeController::class, 'ajax_view']);  //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::post('/expenses/store', [App\Http\Controllers\ExpenseTypeController::class, 'storeType']); //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::post('/expenses/update', [App\Http\Controllers\ExpenseTypeController::class, 'update']);   //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');

                        Route::post('/expenses-type/edit/{id}', [App\Http\Controllers\ExpenseTypeController::class, 'edit']);   //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::post('/expenses/store-expenses', [App\Http\Controllers\ExpensesController::class, 'storeType']); //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::post('/expenses/update-expenses', [App\Http\Controllers\ExpensesController::class, 'update']);   //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::delete('/expenses/destroy/{id}', [App\Http\Controllers\ExpensesController::class, 'destroy']);   //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::get('/expenses/expenses-table', [App\Http\Controllers\ExpensesController::class, 'ajax_view']);  //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::post('/expenses/edit/{id}', [App\Http\Controllers\ExpensesController::class, 'edit']);           //='hhhhh'                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   ', [App\Http\Controllers\CompanyController::class, 'index'])->name('home');
                        Route::delete('/expenses/delete/{id}', [App\Http\Controllers\ExpensesController::class, 'destroy']);    //='hhhhh'

                        Route::get('/{company_name}/expenses/pending', [App\Http\Controllers\ExpensesController::class, 'viewpending']); //='hhhhh'
                        Route::get('/{company_name}/expenses/reject', [App\Http\Controllers\ExpensesController::class, 'viewreject']);
                        Route::post('/expenses/approve/{id}', [App\Http\Controllers\ExpensesController::class, 'approve']); //='hhhhh'
                        Route::post('/expenses/reject/{id}', [App\Http\Controllers\ExpensesController::class, 'reject']);   //='hhhhh'

                        //Income

                        Route::get('/{company_name}/income/type', [App\Http\Controllers\OtherIncomeTypeController::class, 'index']);      //='hhhhh'
                        Route::get('/{company_name}/income/main', [App\Http\Controllers\OtherIncomeController::class, 'index']);          //='hhhhh'
                        Route::get('/{company_name}/income/pending', [App\Http\Controllers\OtherIncomeController::class, 'viewpending']); //='hhhhh'
                        Route::get('/{company_name}/income/reject', [App\Http\Controllers\OtherIncomeController::class, 'viewreject']);   //='hhhhh'

                        Route::get('/income-type/table', [App\Http\Controllers\OtherIncomeTypeController::class, 'ajax_view']); //='hhhhh'
                        Route::post('/income/store', [App\Http\Controllers\OtherIncomeTypeController::class, 'storeType']);     //='hhhhh'
                        Route::post('/income/update', [App\Http\Controllers\OtherIncomeTypeController::class, 'update']);       //='hhhhh'
                        Route::post('/income-type/edit/{id}', [App\Http\Controllers\OtherIncomeTypeController::class, 'edit']);

                                                                                                                               //='hhhhh'
                        Route::post('/income/store-income', [App\Http\Controllers\OtherIncomeController::class, 'storeType']); //='hhhhh'
                        Route::post('/income/update-income', [App\Http\Controllers\OtherIncomeController::class, 'update']);   //='hhhhh'
                        Route::delete('/income/destroy/{id}', [App\Http\Controllers\OtherIncomeController::class, 'destroy']); //='hhhhh'
                        Route::get('/income/income-table', [App\Http\Controllers\OtherIncomeController::class, 'ajax_view']);  //='hhhhh'
                        Route::post('/income/edit/{id}', [App\Http\Controllers\OtherIncomeController::class, 'edit']);         //='hhhhh'
                        Route::delete('/income/delete/{id}', [App\Http\Controllers\OtherIncomeController::class, 'destroy']);  //='hhhhh'
                        Route::post('/income/approve/{id}', [App\Http\Controllers\OtherIncomeController::class, 'approve']);   //='hhhhh'
                        Route::post('/income/reject/{id}', [App\Http\Controllers\OtherIncomeController::class, 'reject']);     //='hhhhh'

                        //loan
                        Route::get('/{company_name}/loan/product', [App\Http\Controllers\LoanController::class, 'index']);
                        Route::get('/{company_name}/loan/offer/{id}', [App\Http\Controllers\LoanController::class, 'LoanPrintOut']);
                        Route::get('/{company_name}/loan/bulk-upload', [App\Http\Controllers\LoanController::class, 'bulk_upload']);
                        Route::post('/loan/bulk-upload', [App\Http\Controllers\LoanController::class, 'PostBooklLoanbulk']);

                        Route::post('/loan/extend-loan', [App\Http\Controllers\LoanController::class, 'extendLoanManually']);

                        Route::get('/loan/reverse-loan', [App\Http\Controllers\LoanRepaymentController::class, 'resetLoanPayment']);
                        Route::get('/loan/resetLoanData', [App\Http\Controllers\LoanRepaymentController::class, 'resetLoanData']);
                        Route::get('/loan/reset-repayment-page', [App\Http\Controllers\LoanRepaymentController::class, 'resetLoanPage']);

                        Route::get('/loan', [App\Http\Controllers\AddedLoanLogController::class, 'extendLoan']);
                        //group laon
                        Route::get('/{company_name}/loan/group', [App\Http\Controllers\GrouploanController::class, 'index']);
                        Route::get('/{company_name}/loan/assign-group', [App\Http\Controllers\GrouploanController::class, 'assignCustomer']);
                        Route::get('/view-user-with-group-loan', [App\Http\Controllers\GrouploanController::class, 'viewUsersWithGroup'])->name('viewUsersWithGroup');
                        Route::get('/view-with-group-loan-select', [App\Http\Controllers\GrouploanController::class, 'groupDatalist'])->name('groupDatalist');
                        Route::post('/assign-group-loan', [App\Http\Controllers\GrouploanController::class, 'PostAssignCustomer'])->name('PostAssignCustomer');
                        Route::get('/loan/group-destails/{id}', [App\Http\Controllers\GrouploanController::class, 'viewGroupLoanDetails'])->name('viewGroupLoanDetails');

                        Route::post('/loan/group', [App\Http\Controllers\GrouploanController::class, 'store'])->name('store-grouploan');
                        Route::get('/loan/group/{id}/edit', [App\Http\Controllers\GrouploanController::class, 'edit'])->name('store-grouploan');
                        Route::get('/loan/group/show', [App\Http\Controllers\GrouploanController::class, 'show'])->name('fetch-grouploan');
                        Route::delete('/loan/group/{id}/delete', [App\Http\Controllers\GrouploanController::class, 'destroy']);
                        Route::get('/loan/group/view', [App\Http\Controllers\GrouploanController::class, 'view'])->name('view-grouploan');

                        Route::post('/loan/comments/{id}', [App\Http\Controllers\LoanCommentController::class, 'store']);
                        // Route::put('/loan/comments/{id}', [App\Http\Controllers\LoanCommentController::class, 'update']);
                        Route::get('/loan/comments/{id}', [App\Http\Controllers\LoanCommentController::class, 'show']);
                        Route::post('/loan/comments-edit/{id}', [App\Http\Controllers\LoanCommentController::class, 'edit']);
                        Route::delete('/loan/comments/{id}', [App\Http\Controllers\LoanCommentController::class, 'destroy']);

                        Route::put('/loan/save-product', [App\Http\Controllers\LoanController::class, 'save_loanproduct']);
                        Route::put('/loan/update-product', [App\Http\Controllers\LoanController::class, 'update_loanproduct']);
                        Route::get('/loan/product-table', [App\Http\Controllers\LoanController::class, 'view_product']);
                        Route::post('/loan/edit/{id}', [App\Http\Controllers\LoanController::class, 'edit_product']);
                        Route::get('/{company_name}/loan/book', [App\Http\Controllers\LoanController::class, 'borrower']);
                        Route::get('/loan/product-list', [App\Http\Controllers\LoanController::class, 'loanprpoducts_list']);
                        Route::get('/loan/guarantor-list', [App\Http\Controllers\LoanGuarantorController::class, 'guarantor_list']);
                        Route::get('/{company_name}/loan/guarantor', [App\Http\Controllers\LoanGuarantorController::class, 'index']);
                        Route::get('/loan/view-guarantor', [App\Http\Controllers\LoanGuarantorController::class, 'guarantorSource']);
                        Route::post('/loan/save-guarantor', [App\Http\Controllers\LoanGuarantorController::class, 'store']);
                        Route::post('/loan/process-loan', [App\Http\Controllers\LoanController::class, 'processLoan']);
                        Route::post('/loan/getpoducts_list/{id}', [App\Http\Controllers\LoanController::class, 'getpoducts_list']);
                        Route::post('/loan/PostBookLoan/', [App\Http\Controllers\LoanController::class, 'PostBooklLoan']);
                        Route::get('/{company_name}/loan/pending', [App\Http\Controllers\LoanController::class, 'PendingLoan']);
                        Route::get('/loan/pending-data', [App\Http\Controllers\LoanController::class, 'viewPendingLoan']);
                        Route::post('/loan/view-loan-details/{id}', [App\Http\Controllers\LoanController::class, 'LoanDetailsData']);
                        Route::get('/{company_name}/loan/disbursment', [App\Http\Controllers\LoanController::class, 'LoanDisbursment']);
                        Route::get('/loan/disburse-data', [App\Http\Controllers\LoanController::class, 'viewPendingLoan']);
                        Route::post('/loan/disburseLoan', [App\Http\Controllers\LoanController::class, 'DisburseLoan']);
                        Route::get('/{company_name}/loan/rejected', [App\Http\Controllers\LoanController::class, 'LoanRejected']);
                        Route::get('/loan/pending-data?rejected=1', [App\Http\Controllers\LoanController::class, 'viewPendingLoan']);
                        Route::get('/loan/all-data', [App\Http\Controllers\LoanController::class, 'LoanRecord']);
                        Route::post('/loan/rejectedAction', [App\Http\Controllers\LoanController::class, 'RejectedLoanAction']);
                        Route::get('/{company_name}/loan/Ongoing', [App\Http\Controllers\LoanController::class, 'OngoingLoan']);
                        Route::get('/loan/ongoing-data', [App\Http\Controllers\LoanController::class, 'LoanRecord']);
                        Route::post('/loan/view-Loan-Details-data/{id}', [App\Http\Controllers\LoanController::class, 'viewLoanDetails']);
                        Route::post('/loan/load-user-loan/{user}/{loanid}', [App\Http\Controllers\LoanController::class, 'loadUserLoan']);
                        Route::post('/loan/roll-loan-over', [App\Http\Controllers\LoanController::class, 'rollLoan']);
                        Route::post('/loan/writeoff-loan-over', [App\Http\Controllers\LoanController::class, 'writeLoan']);
                        Route::post('/loan/liquidate-loan', [App\Http\Controllers\LoanController::class, 'liquidate']);
                        Route::get('/{company_name}/loan/pay-loan/{loanid}', [App\Http\Controllers\LoanController::class, 'PayLoan']);
                        Route::post('/loan/pay-loan', [App\Http\Controllers\LoanController::class, 'DoPayLoan']);
                        Route::get('/loan/json-pay-loan/{id}', [App\Http\Controllers\LoanController::class, 'loanRepaymentBreakDown']);
                        Route::get('/loan/view-extended-loan/{id}', [App\Http\Controllers\LoanController::class, 'viewExtendedLoan']);
                        Route::get('/loan/delete-payment/{id}', [App\Http\Controllers\LoanController::class, 'DeleteRepayment']);
                        Route::post('/loan/Swapping-repayment', [App\Http\Controllers\PendingRepaymentController::class, 'transferRepaymentAuto']);
                        Route::get('/loan/loan-receipt/{id}', [App\Http\Controllers\LoanRepaymentController::class, 'txnReceipt'])->name('loantxnReceipt');

                        Route::get('/report/officer-saving-client/{id}', [App\Http\Controllers\LoanReportController::class, 'generateStaffClient']);
                        Route::get('/report/loan-officer-client-loan/{id}', [App\Http\Controllers\LoanReportController::class, 'generateStaffClientWithLoan']);

                        //remitta
                        Route::get('remitta/salary-history', [App\Http\Controllers\RemitasalaryhistoryController::class, 'index']);
                        Route::post('remitta/salaryajaxGet', [App\Http\Controllers\RemitasalaryhistoryController::class, 'ajaxGet']);
                        Route::get('remitta/salaryhistory', [App\Http\Controllers\RemitasalaryhistoryController::class, 'getSalaryHistory']);
                        Route::post('remitta/post-notification', [App\Http\Controllers\RemitasalaryhistoryController::class, 'postNotification']);
                        Route::get('remitta/mandate/{id}', [App\Http\Controllers\RemitasalaryhistoryController::class, 'RemitaMandate']);
                        Route::get('remitta/mandate/stop/reference', [App\Http\Controllers\RemitasalaryhistoryController::class, 'StopMandate']);
                        Route::get('remitta/mandate/payment/history', [App\Http\Controllers\RemitasalaryhistoryController::class, 'RemitaMandatePayment']);

                        Route::get('/{company_name}/loan/complete-loan', [App\Http\Controllers\LoanController::class, 'CompleteLoan'])->name('complete-loan');
                        Route::get('/loan/loan-invoice-pdf/{id}', [App\Http\Controllers\LoanController::class, 'loanPDF']);
                        Route::get('/loan/loan-invoice-print/{id}', [App\Http\Controllers\LoanController::class, 'loanInvoicePrint']);
                        Route::get('/{company_name}/loan/due-loan', [App\Http\Controllers\LoanController::class, 'DueLoanView']);
                        Route::get('/{company_name}/loan/collection-sheet', [App\Http\Controllers\LoanController::class, 'CollectionView']);
                        Route::post('/loan/due-loan', [App\Http\Controllers\LoanReportController::class, 'dueLoan']);
                        Route::get('/loan/due-loan', [App\Http\Controllers\LoanReportController::class, 'dueLoan']);
                        Route::get('/loan/due-loan-collection', [App\Http\Controllers\LoanReportController::class, 'dueLoanCollection']);

                        Route::post('/loan/guarantor-edit/{id}', [App\Http\Controllers\LoanGuarantorController::class, 'edit']);
                        Route::post('/loan/update-guarantor', [App\Http\Controllers\LoanGuarantorController::class, 'update']);
                        Route::delete('/loan/delete-guarantor/{id}', [App\Http\Controllers\LoanGuarantorController::class, 'destroy']);
                        Route::post('/loan/check-guarantor', [App\Http\Controllers\LoanGuarantorController::class, 'scanGuarantor']);
                        Route::get('/{company_name}/loan/all-loan', [App\Http\Controllers\LoanController::class, 'AllLoan']);
                        Route::get('/{company_name}/loan/edit-loan', [App\Http\Controllers\LoanController::class, 'EditLoan']);
                        Route::delete('/loan/delete-loan/{id}', [App\Http\Controllers\LoanController::class, 'delete_loan']);
                        Route::get('/{company_name}/loan/pending-repayment', [App\Http\Controllers\PendingRepaymentController::class, 'index']);
                        Route::get('/loan/pending-repayment-data', [App\Http\Controllers\PendingRepaymentController::class, 'display_pending']);
                        Route::post('/loan/post-pending-repayment', [App\Http\Controllers\PendingRepaymentController::class, 'batch_approval_rejected']);
                        Route::get('/{company_name}/loan/loan-bvn-check', [App\Http\Controllers\CustomerCreditController::class, 'bvnLoanVerification']);
                        Route::get('/loan/loan-bvn-check-verify/{id}/{bvn}', [App\Http\Controllers\CustomerCreditController::class, 'verify'])->middleware('log.route');
                        Route::get('/{company_name}/loan/app-setting', [App\Http\Controllers\ApploanSettingsController::class, 'Appsetting']);
                        Route::post('/loan/app-setting', [App\Http\Controllers\ApploanSettingsController::class, 'PostAppsetting']);
                        Route::post('/loan/update-app-setting', [App\Http\Controllers\ApploanSettingsController::class, 'UpdatePostAppsetting']);
                        Route::get('/loan/view-app-setting', [App\Http\Controllers\ApploanSettingsController::class, 'view_setting']);
                        Route::delete('/loan/delete-app-setting/{id}', [App\Http\Controllers\ApploanSettingsController::class, 'destroy']);
                        Route::post('/loan/edit-app-setting/{id}', [App\Http\Controllers\ApploanSettingsController::class, 'selectSettings']);
                        // loan report
                        Route::get('/{company_name}/loan/missed-loan-report', [App\Http\Controllers\LoanReportController::class, 'MissedLoan']);
                        Route::get('/{company_name}/loan/disbursed-loan-report', [App\Http\Controllers\LoanReportController::class, 'DisbursedLoan']);
                        Route::post('/loan/missed-loan-data', [App\Http\Controllers\LoanReportController::class, 'missedLoan_report']);
                        Route::post('/loan/disburse-loan-data', [App\Http\Controllers\LoanReportController::class, 'disbursedLoan_report']);
                        Route::get('/{company_name}/loan/loan-repayment-report', [App\Http\Controllers\LoanReportController::class, 'repaymentReport']);
                        Route::get('/loan/loan-repayment-report', [App\Http\Controllers\LoanReportController::class, 'repaymentReportData']);
                        Route::get('/{company_name}/loan/loan-arrears-report', [App\Http\Controllers\LoanReportController::class, 'areaReport']);
                        Route::post('/loan/loan-arrears-report', [App\Http\Controllers\LoanReportController::class, 'generateArrearReport']);

                        Route::get('/{company_name}/loan/loan-officer-performance', [App\Http\Controllers\LoanReportController::class, 'officerPerformance']);
                        Route::get('/loan/loan-officer-performance', [App\Http\Controllers\LoanReportController::class, 'generateOfficerPerformanceReport']);
                        Route::post('/loan/reverse-all', [App\Http\Controllers\LoanController::class, 'rollbackFunction']);

                        //Esusu
                        Route::get('/{company_name}/esusu/product', [App\Http\Controllers\EsusuController::class, 'index']);
                        Route::put('/esusu/product', [App\Http\Controllers\EsusuController::class, 'store']);
                        Route::get('/esusu/product', [App\Http\Controllers\EsusuController::class, 'display_product']);
                        Route::post('/esusu/edit/{id}', [App\Http\Controllers\EsusuController::class, 'edit_product'])->middleware('log.route');
                        Route::delete('/esusu/delete-product/{id}', [App\Http\Controllers\EsusuController::class, 'delete_product']);
                        Route::get('/{company_name}/esusu/main', [App\Http\Controllers\EsusuController::class, 'esusu']);
                        Route::get('/{company_name}/esusu/pending', [App\Http\Controllers\EsusuController::class, 'pending']);
                        Route::get('/{company_name}/esusu/circle-out-history', [App\Http\Controllers\EsusuController::class, 'circle_out_history']);
                        Route::get('/{company_name}/esusu/statement/{id}', [App\Http\Controllers\EsusuController::class, 'esusu_statement'])->name('esusu-statement');
                        Route::get('/view-esusu/statement', [App\Http\Controllers\UserReportController::class, 'ajax_generate_callover']);

                        Route::get('/esusu/schedule/product', [App\Http\Controllers\ScheduleEsusuProductController::class, 'index'])->name('esusu_schedule_product');
                        Route::post('/esusu/schedule/product', [App\Http\Controllers\ScheduleEsusuProductController::class, 'store'])->name('esusu_schedule_product_store');
                        Route::get('/esusu/schedule/operation', [App\Http\Controllers\ScheduleEsusuController::class, 'index'])->name('esusu_schedule_operation');
                        Route::get('/esusu/schedule/product-list', [App\Http\Controllers\ScheduleEsusuProductController::class, 'Esusu_Productlist'])->name('esusu_schedule_productlist');
                        Route::post('/esusu/schedule/operation/store', [App\Http\Controllers\ScheduleEsusuController::class, 'store'])->name('esusu_schedule_operation_store');
                        Route::get('/esusu/schedule/operation/table', [App\Http\Controllers\ScheduleEsusuController::class, 'show'])->name('esusu_schedule_operation_show');
                        Route::post('/esusu/schedule/operation/topup', [App\Http\Controllers\ScheduleEsusuController::class, 'topup'])->name('esusu_schedule_operation_topup');
                        Route::post('/esusu/schedule/operation/withdraw', [App\Http\Controllers\ScheduleEsusuController::class, 'withdraw'])->name('esusu_schedule_operation_withdraw');
                        Route::post('/esusu/schedule/operation/reset', [App\Http\Controllers\ScheduleEsusuController::class, 'reset'])->name('esusu_schedule_operation_reset');
                        Route::post('/esusu/schedule/operation/agent', [App\Http\Controllers\ScheduleEsusuController::class, 'agent'])->name('esusu_schedule_operation_agent');

                        Route::get('/{company_name}/mobile-esusu', [App\Http\Controllers\EsusuMobileAppController::class, 'viewAgent']);
                        Route::get('/{company_name}/mobile-customer-esusu', [App\Http\Controllers\EsusuMobileAppController::class, 'esusu']);
                        Route::get('/{company_name}/mobile-customer-withdrawal', [App\Http\Controllers\EsusuMobileAppController::class, 'withdraw']);
                        Route::get('/mobile-customer-transaction', [App\Http\Controllers\EsusuMobileAppController::class, 'display_esusu']);
                        Route::post('/mobile/esusu/approve-agent/{id}', [App\Http\Controllers\EsusuMobileAppController::class, 'approveAgent']);
                        Route::post('/mobile/esusu/disabled-agent/{id}', [App\Http\Controllers\EsusuMobileAppController::class, 'disableAgent']);
                        Route::post('/mobile/esusu/approve-agent-withdrawal/{id}', [App\Http\Controllers\EsusuMobileAppController::class, 'approveAgentWithdrawal']);

                        //Ecommerce
                        Route::get('/{company_name}/ecommerce/product', [App\Http\Controllers\EcomerceProductModelController::class, 'index']);
                        Route::get('/{company_name}/ecommerce/category', [App\Http\Controllers\EcomerceProductModelController::class, 'category']);
                        Route::post('/ecommerce/view/{id}', [App\Http\Controllers\EcomerceProductModelController::class, 'viewDetaild']);
                        Route::post('/ecommerce/save-category', [App\Http\Controllers\EcomerceProductModelController::class, 'saveCategory']);
                        Route::post('/ecommerce/update-category', [App\Http\Controllers\EcomerceProductModelController::class, 'updateCategory']);

                        Route::get('/{company_name}/ecommerce/pending-orders', [App\Http\Controllers\OrderModelController::class, 'index']);
                        Route::get('/{company_name}/ecommerce/orders', [App\Http\Controllers\OrderModelController::class, 'orders']);
                        Route::get('/ecommerce/view-order', [App\Http\Controllers\OrderModelController::class, 'display_product']);
                        Route::delete('/ecommerce/order/delete/{id}', [App\Http\Controllers\OrderModelController::class, 'destroy']);
                        Route::post('/ecommerce/order/cancil', [App\Http\Controllers\OrderModelController::class, 'cancelOrders']);
                        Route::post('/ecommerce/order/show/{id}', [App\Http\Controllers\OrderModelController::class, 'show']);
                        Route::post('/ecommerce/order/complete/{id}', [App\Http\Controllers\OrderModelController::class, 'complete']);
                        // /company/ecommerce/order/delete/
                        Route::put('/ecommerce/product', [App\Http\Controllers\EcomerceProductModelController::class, 'store'])->name('store_ecommerce_product');
                        Route::post('/ecommerce/product/edit/{id}', [App\Http\Controllers\EcomerceProductModelController::class, 'show'])->name('show_ecommerce_product');
                        Route::post('/ecommerce/product/update/', [App\Http\Controllers\EcomerceProductModelController::class, 'UpdateEdit'])->name('update_ecommerce_product');
                        Route::delete('/ecommerce/product/{id}', [App\Http\Controllers\EcomerceProductModelController::class, 'destroy'])->name('destroy_ecommerce_product');
                        //UpdateEdit

                        Route::get('/esusu/display-pending-esusu', [App\Http\Controllers\EsusuController::class, 'display_pending']);
                        Route::post('/esusu/post-pending-esusu', [App\Http\Controllers\EsusuController::class, 'post_pending_esusu']);

                        Route::get('/esusu/product-list', [App\Http\Controllers\EsusuController::class, 'Esusu_Productlist']);

                        Route::post('/esusu/create-esusu-wallet', [App\Http\Controllers\EsusuController::class, 'create_esusu_wallet']);
                        Route::get('/esusu/view-data', [App\Http\Controllers\EsusuController::class, 'display_esusu']);

                        Route::post('/esusu/deposit-esusu-wallet', [App\Http\Controllers\EsusuController::class, 'topup']);
                        Route::post('/esusu/withdraw-esusu-wallet', [App\Http\Controllers\EsusuController::class, 'withdraw']);
                        Route::post('/esusu/overdraft-esusu-wallet', [App\Http\Controllers\EsusuController::class, 'overdraft']);
                        Route::post('/esusu/reset-esusu-wallet', [App\Http\Controllers\EsusuController::class, 'reset']);

                        //Savings
                        Route::get('/{company_name}/product/savings', [App\Http\Controllers\SavingsController::class, 'index'])->name('savings_indexs');
                        Route::get('/{company_name}/product/fetch_savings', [App\Http\Controllers\SavingsController::class, 'fetch_ajax_product'])->name('fetch_ajax_product');
                        Route::put('/{company_name}/product/savings', [App\Http\Controllers\SavingsController::class, 'ajax_add_product'])->name('ajax_add_product');
                        Route::post('/{company_name}/product/savings', [App\Http\Controllers\SavingsController::class, 'ajax_update_product'])->name('ajax_update_product');
                        Route::delete('/{company_name}/product/savings/{id}', [App\Http\Controllers\SavingsController::class, 'destroy_product'])->name('destroy_product');

                        //DepositWithdraw
                        Route::get('/{company_name}/saving', [App\Http\Controllers\SavingsController::class, 'DepositWithdraw'])->name('DepositWithdraw');
                        Route::get('/{company_name}/bulk-transaction', BulkTransactionComponent::class)->name('BulkDepositWithdraw');
                        // Route::get('/{company_name}/bulk-transaction',BulkTransactionComponent::class)->name('BulkDepositWithdraw.import');

                        Route::get('/{company_name}/saving/pending', [App\Http\Controllers\SavingsController::class, 'pending']);
                        Route::get('/saving/view-pending', [App\Http\Controllers\SavingsController::class, 'display_pending']);
                        Route::post('/saving/post-pending-saving', [App\Http\Controllers\SavingsController::class, 'post_pending_saving']);
                        Route::post('/saving/view-pending-saving-details/{id}', [App\Http\Controllers\SavingsController::class, 'viewTXNDetails']); //
                        Route::post('/saving/single-approval', [App\Http\Controllers\SavingsController::class, 'singleApproval']);                  //

                        //Investment product
                        Route::get('/{company_name}/product/investment', [App\Http\Controllers\InvestmentController::class, 'index'])->name('investment');
                        Route::put('/{company_name}/product/save-product', [App\Http\Controllers\InvestmentController::class, 'store'])->name('store');
                        Route::post('/{company_name}/product/update-product', [App\Http\Controllers\InvestmentController::class, 'update'])->name('update');
                        Route::delete('/{company_name}/product/investment/{id}', [App\Http\Controllers\InvestmentController::class, 'destroy'])->name('delete_investment');
                        //customer investment
                        Route::get('/{company_name}/investment-booking', [App\Http\Controllers\InvestmentController::class, 'booking'])->name('booking');
                        Route::delete('/investment-booking/{id}', [App\Http\Controllers\InvestmentController::class, 'removeInvestment'])->name('delete_investment');

                        Route::get('/investmentDataProduct', [App\Http\Controllers\InvestmentController::class, 'fetchInvestmentProduct'])->name('fetchInvestmentProduct');

                        Route::put('/makeinvestment', [App\Http\Controllers\InvestmentController::class, 'makeinvestment'])->name('makeinvestment');
                        Route::put('{company_name}/update-investment', [App\Http\Controllers\InvestmentController::class, 'updateInvestment'])->name('updateinvestment');

                        Route::get('/cal_investment_plan', [App\Http\Controllers\InvestmentController::class, 'cal_investment_plan'])->name('cal_investment_plan');

                        Route::get('/investment-calculation', [App\Http\Controllers\InvestmentController::class, 'calculateinvestment'])->name('calculateinvestment');

                        Route::get('/{company_name}/view-investment', [App\Http\Controllers\InvestmentController::class, 'viewinvestment'])->name('viewinvestment');
                        Route::get('/{company_name}/view-user-investment/{id}', [App\Http\Controllers\InvestmentController::class, 'viewUserInvestment']);
                        Route::get('/view-investment/{page}', [App\Http\Controllers\InvestmentController::class, 'ajax_viewInvestment'])->name('ajax_viewInvestment');

                        Route::get('/view-investment/{page}/{id}', [App\Http\Controllers\InvestmentController::class, 'ajax_userviewInvestment']);

                        Route::post('/investmentstatus/{id}', [App\Http\Controllers\InvestmentController::class, 'ajax_investmentstatus'])->name('ajax_investmentstatus');

                        Route::post('/terminate-investment/{id}', [App\Http\Controllers\InvestmentController::class, 'ajax_terminate_investment']);

                        //Customer Restriction
                        Route::get('/{company_name}/restriction', [App\Http\Controllers\CustomerRestrictionController::class, 'index'])->name('restriction');

                        Route::prefix('/{company_name}/pos')->name('company.')->group(function () {
                            Route::get('/index', [App\Http\Controllers\POSController::class, 'index'])->name('pos-management');
                            Route::post('/add', [App\Http\Controllers\POSController::class, 'addPOS'])->name('add-pos')->middleware('log.route');
                            Route::get('/all-pos', [App\Http\Controllers\POSController::class, 'ajax_view']);
                            Route::post('/credit', [App\Http\Controllers\POSController::class, 'creditPOS'])->middleware('log.route');
                            Route::post('/debit', [App\Http\Controllers\POSController::class, 'debitPOS'])->middleware('log.route');
                            Route::post('/reassign', [App\Http\Controllers\POSController::class, 'reassignPOS'])->middleware('log.route');
                            Route::post('/deactivate-activate', [App\Http\Controllers\POSController::class, 'deactivateActivate'])->middleware('log.route');
                        });

                        Route::get('/all-restriction', [App\Http\Controllers\CustomerRestrictionController::class, 'ajax_view']);

                        Route::get('/{company_name}/pending-entry', [App\Http\Controllers\CustomerController::class, 'pending_entry']);
                        Route::post('/pending-entry/{id}', [App\Http\Controllers\CustomerController::class, 'activate_entry']);
                        Route::post('/pending-entry-reject/{id}', [App\Http\Controllers\CustomerController::class, 'reject_entry']);
                        Route::get('/{company_name}/all-customer', [App\Http\Controllers\CustomerController::class, 'allCustomer']);
                        Route::get('/{company_name}/export-all-customer', [App\Http\Controllers\CustomerController::class, 'exportallCustomer']);
                        Route::get('/{company_name}/transfer-customer', [App\Http\Controllers\CustomerController::class, 'CustomerTransfer']);
                        Route::post('/transfer-customer', [App\Http\Controllers\CustomerController::class, 'transferCustomer']);
                        Route::POST('/final-transfer-customer', [App\Http\Controllers\CustomerController::class, 'doTransfer']);

                        //hardreset dont use
                        Route::get('/hard-reset', [App\Http\Controllers\CustomerController::class, 'HardResetresetSystem']);
                        Route::get('/branch-wallet-reset', [App\Http\Controllers\BranchWalletController::class, 'branchwallet_Reset']);

                        //timeout
                        Route::get('/{company_name}/opening', [App\Http\Controllers\CompanyOpeningclosingtimeController::class, 'index'])->name('opening');
                        Route::get('/{company_name}/opening/edit/{id}', [App\Http\Controllers\CompanyOpeningclosingtimeController::class, 'edit'])->name('edit');
                        Route::get('/{company_name}/opening/get-table', [App\Http\Controllers\CompanyOpeningclosingtimeController::class, 'show'])->name('show');
                        Route::put('/{company_name}/opening/save', [App\Http\Controllers\CompanyOpeningclosingtimeController::class, 'store'])->name('save');
                        Route::post('/{company_name}/opening/delete/{id}', [App\Http\Controllers\CompanyOpeningclosingtimeController::class, 'destroy'])->name('destroy');

                        //Staff Permission
                        Route::get('/{company_name}/role', [App\Http\Controllers\StaffpermissionController::class, 'index'])->name('index');
                        Route::get('/{company_name}/role/ajax_view_role', [App\Http\Controllers\StaffpermissionController::class, 'view_roles'])->name('view_roles');
                        Route::get('/{company_name}/role/get-permission/{id}', [App\Http\Controllers\StaffpermissionController::class, 'get_permission'])->name('get_permission');
                        Route::put('/{company_name}/role/add', [App\Http\Controllers\StaffpermissionController::class, 'add_role'])->name('add_role');
                        Route::post('/{company_name}/role/update', [App\Http\Controllers\StaffpermissionController::class, 'update_role'])->name('update_role');
                        Route::delete('/{company_name}/role/delete/{id}', [App\Http\Controllers\StaffpermissionController::class, 'destroy'])->name('delete_role');

                        //Company settings
                        Route::get('/{company_name}/settings/main', [App\Http\Controllers\SettingsController::class, 'index'])->name('company_settings');
                        Route::post('/settings/transaction', [App\Http\Controllers\TransactionSettingsController::class, 'store'])->name('transaction.store');

                        //Transactions
                        Route::get('/{company_name}/transaction/fund-wallet', [App\Http\Controllers\TransactionController::class, 'fundWallet'])->name('fund-wallet');
                        Route::post('/{company_name}/transaction/fund-wallet-page', [App\Http\Controllers\TransactionController::class, 'fundWalletPage'])->name('fund-wallet-page');
                        Route::get('/{company_name}/transaction/bvn-verification', [App\Http\Controllers\TransactionController::class, 'bvnVerification'])->name('bvn-verification');
                        Route::get('/{company_name}/transaction/verify-bvn', [App\Http\Controllers\TransactionController::class, 'verifyBVN'])->name('verifyBVN')->middleware('log.route');
                        Route::post('/{company_name}/transaction/bvn-verification-page', [App\Http\Controllers\TransactionController::class, 'bvnVerificationPage'])->name('bvn-verification-page');

                        Route::get('/transaction/ledger-setting', [App\Http\Controllers\LedgerController::class, 'index']);

                        //pendingTransaction
                        Route::middleware('checkAccountStatus')->post('/{company_name}/transaction/initiate-transaction', [App\Http\Controllers\TransactionController::class, 'initiateTransaction'])->name('initiate-transaction')->middleware('log.route');
                        Route::get('/{company_name}/transaction/pending-transaction', [App\Http\Controllers\TransactionController::class, 'pendingTransaction'])->name('pendingTransaction');
                        Route::get('/transaction/pending-transaction', [App\Http\Controllers\TransactionController::class, 'display_pending'])->name('pendingTransactionData');
                        Route::get('/transaction/more-details/{id}', [App\Http\Controllers\TransactionController::class, 'viewTXNDetails'])->name('transaction_viewTXNDetails')->middleware('log.route');
                        Route::post('/transaction/approve-reject-pending-txn', [App\Http\Controllers\TransactionController::class, 'approve_pending_transaction'])->name('approve_pending_transaction')->middleware('log.route');

                        Route::get('/{company_name}/transaction/verify-fund-wallet', [App\Http\Controllers\TransactionController::class, 'verifyFundWallet'])->name('verify-fund-wallet')->middleware('log.route');
                        Route::post('/{company_name}/transaction/verify-wallet-account', [App\Http\Controllers\TransactionController::class, 'verifyWalletAccount'])->name('verifyWalletAccount');
                        Route::post('/{company_name}/transaction/company-wallet-transfer', [App\Http\Controllers\TransactionController::class, 'companyWalletTransfer'])->name('my-company-wallet-transfer')->middleware('log.route');
                        Route::post('/{company_name}/transaction/verify-bank-account', [App\Http\Controllers\TransactionController::class, 'verifyBankAccount'])->name('verify-bank-account')->middleware('log.route');
                        Route::get('/{company_name}/transaction/verify-bank-account', [App\Http\Controllers\TransactionController::class, 'verifyBankAccount'])->name('verify-bank-account-get')->middleware('log.route');
                        Route::post('/{company_name}/transaction/transfer-to-bank-account', [App\Http\Controllers\TransactionController::class, 'transferToBankAccount'])->name('transfer-to-bank-account')->middleware('log.route');
                        Route::post('/{company_name}/transaction/mobile-money-transfer', [App\Http\Controllers\TransactionController::class, 'mobileMoneyTransfer'])->name('international-transfer')->middleware('log.route');
                        Route::get('/{company_name}/transaction/wallet-to-wallet-transfer', [App\Http\Controllers\TransactionController::class, 'wallet_to_wallet_transfer'])->name('wallet-to-wallet-transfer');
                        Route::post('/transaction/make-wallet-to-wallet-transfer', [App\Http\Controllers\TransactionController::class, 'make_wallet_to_wallet_transfer'])->name('make_wallet_to_wallet_transfer')->middleware('log.route');

                        Route::get('/{company_name}/role/get-permission/{id}', [App\Http\Controllers\StaffpermissionController::class, 'get_permission'])->name('get_permission');
                        Route::put('/{company_name}/role/add', [App\Http\Controllers\StaffpermissionController::class, 'add_role'])->name('add_role');
                        Route::post('/{company_name}/role/update', [App\Http\Controllers\StaffpermissionController::class, 'update_role'])->name('update_role');
                        Route::delete('/{company_name}/role/delete/{id}', [App\Http\Controllers\StaffpermissionController::class, 'destroy'])->name('destroy_role');
                        //Report
                        Route::get('/{company_name}/user-statement', [App\Http\Controllers\UserReportController::class, 'main'])->name('main');
                        Route::get('/{company_name}/user-dom', [App\Http\Controllers\UserReportController::class, 'dom'])->name('dom');
                        Route::get('/{company_name}/transaction-reversal', [App\Http\Controllers\UserReportController::class, 'reversal_statement']); //reversal
                        Route::get('/{company_name}/callover-report', [App\Http\Controllers\UserReportController::class, 'callover_report']);         //callover
                        Route::get('/callover-report-data', [App\Http\Controllers\UserReportController::class, 'ajax_generate_callover']);            //callover
                        Route::get('/staff-transaction', [App\Http\Controllers\UserReportController::class, 'staff_txn_table']);                      //callover
                        Route::get('/reset-teller-Balance', [App\Http\Controllers\UserReportController::class, 'resetTellerwallet']);                 //callover

                        Route::get('/{company_name}/end-of-day', [App\Http\Controllers\UserReportController::class, 'end_of_day']);          //end of day report
                        Route::get('/end-of-day-datatable', [App\Http\Controllers\UserReportController::class, 'ajax_generate_end_of_day']); //end of day report

                        Route::get('/{company_name}/user-statement/{id}', [App\Http\Controllers\UserReportController::class, 'statement'])->name('statement');
                        Route::post('/{company_name}/user-statement/all-balance-info/{id}', [App\Http\Controllers\UserReportController::class, 'all_balance_info']);
                        Route::get('/fetch-statement/{id}', [App\Http\Controllers\UserReportController::class, 'ajax_account_statement']);
                        Route::get('/fetch-reversal-statement', [App\Http\Controllers\UserReportController::class, 'ajax_general_statement']);

                        Route::get('/reverse-statement/{id}', [App\Http\Controllers\UserReportController::class, 'reverseTXN']);

                        Route::get('/{company_name}/transaction/list', [App\Http\Controllers\TransactionController::class, 'transactionList'])->name('transaction-list');
                        Route::get('/{company_name}/transaction/verify-and-reverse-transaction', [App\Http\Controllers\TransactionController::class, 'verifyAndReverseTransaction'])->name('verify-and-reverse-transaction');
                        Route::post('/{company_name}/transaction/process-verify-and-reverse-transaction', [App\Http\Controllers\TransactionController::class, 'processVerifyAndReverseTransaction'])->name('process-verify-and-reverse-transaction');
                        Route::get('/{company_name}/transaction/other-list', [App\Http\Controllers\TransactionController::class, 'otherTransactionList'])->name('other-transaction-list');
                        Route::get('/{company_name}/transaction/tier-settlement', [App\Http\Controllers\TransactionController::class, 'tierSettlement'])->name('tier-settlement');
                        Route::get('/transaction/list', [App\Http\Controllers\TransactionController::class, 'companyTransactionList'])->name('company-transaction-list');
                        Route::get('/transaction/other-list', [App\Http\Controllers\TransactionController::class, 'companyOtherTransactionList'])->name('company-other-transaction-list');
                        Route::get('/transaction/tier-settlement', [App\Http\Controllers\TransactionController::class, 'tierSettlementsList'])->name('company-other-transaction-list');
                        Route::get('/transaction/receipt/{id}', [App\Http\Controllers\TransactionController::class, 'txnReceipt']);

                        // Funds Transfer
                        Route::get('/{company_name}/transaction/company-wallet-transfer', [App\Http\Controllers\TransactionController::class, 'companyWalletTransferView'])->name('company-wallet-transfer');
                        Route::post('/{company_name}/transaction/company-wallet-transfer-page', [App\Http\Controllers\TransactionController::class, 'companyWalletTransferPage'])->name('company-wallet-transfer-page');

                        Route::get('/{company_name}/transaction/nigerian-bank-transfer', [App\Http\Controllers\TransactionController::class, 'nigerianBankTransferView'])->name('nigerian-bank-transfer');
                        Route::post('/{company_name}/transaction/nigerian-bank-transfer-page', [App\Http\Controllers\TransactionController::class, 'nigerianBankTransferPage'])->name('nigerian-bank-transfer-page');

                        Route::get('/{company_name}/transaction/mobile-money-transfer', [App\Http\Controllers\TransactionController::class, 'mobileMoneyTransferView'])->name('mobile-money-transfer');
                        Route::post('/{company_name}/transaction/mobile-money-transfer-page', [App\Http\Controllers\TransactionController::class, 'mobileMoneyTransferPage'])->name('mobile-money-transfer-page');

                        Route::get('/{company_name}/transaction/funds-transfer', [App\Http\Controllers\TransactionController::class, 'fundsTransfer'])->name('funds-transfer');
                        Route::post('/{company_name}/transaction/funds-transfer-page', [App\Http\Controllers\TransactionController::class, 'fundsTransferPage'])->name('funds-transfer-page');

                        //Buy Airtime
                        Route::get('/{company_name}/transaction/buy-airtime', [App\Http\Controllers\TransactionController::class, 'buyAirtime'])->name('buy-airtime');
                        Route::post('/{company_name}/transaction/buy-airtime-page', [App\Http\Controllers\TransactionController::class, 'buyAirtimePage'])->name('buy-airtime-page');
                        Route::post('/{company_name}/transaction/buy-airtime-transaction', [App\Http\Controllers\TransactionController::class, 'buyAirtimeTransaction'])->name('buy-airtime-transaction')->middleware('log.route');
                        Route::get('/{company_name}/transaction/get-network-operators', [App\Http\Controllers\TransactionController::class, 'getNetworkOperators'])->name('get-operators');

                        //Buy Data
                        Route::get('/{company_name}/transaction/buy-data', [App\Http\Controllers\TransactionController::class, 'buyData'])->name('buy-data');
                        Route::post('/{company_name}/transaction/buy-data-page', [App\Http\Controllers\TransactionController::class, 'buyDataPage'])->name('buy-data-page');
                        Route::post('/{company_name}/transaction/buy-data-transaction', [App\Http\Controllers\TransactionController::class, 'buyDataTransaction'])->name('buy-data-transaction');
                        Route::get('/{company_name}/transaction/get-data-network-operators', [App\Http\Controllers\TransactionController::class, 'getDataNetworkOperators'])->name('get-data-operators');
                        Route::get('/{company_name}/transaction/get-data-bundles', [App\Http\Controllers\TransactionController::class, 'getDataBundles'])->name('get-data-bundles');

                        Route::get('/{company_name}/transaction/get-customer-balance/{customer_id}', [UserController::class, 'getCustomerBalance']);
                        Route::get('/{company_name}/transaction/get-customer-profile/{customer_id}', [UserController::class, 'getCustomerProfile']);
                        Route::post('/{company_name}/transaction/create-overdraft', [UserController::class, 'createOverdraft'])->middleware('log.route');
                        Route::get('/{company_name}/transaction/customer-overdraft-list', [UserController::class, 'customerOverdraftList']);
                        Route::get('/{company_name}/transaction/all-overdrafts', [App\Http\Controllers\UserController::class, 'ajax_view']);

                        //Transactions
                        Route::get('/{company_name}/transaction/customer-overdraft', [App\Http\Controllers\TransactionController::class, 'customerOverdraft'])->name('customer-overdraft');
                        Route::post('/{company_name}/transaction/customer-overdraft-page', [App\Http\Controllers\TransactionController::class, 'customerOverdraftPage'])->name('customer-overdraft-page');

                        //Mandate
                        Route::get('/{company_name}/transaction/all-mandates', [App\Http\Controllers\TransactionController::class, 'allMandates'])->name('all-mandates');
                        Route::get('/{company_name}/transaction/mandates', [App\Http\Controllers\TransactionController::class, 'mandates'])->name('mandates');
                        Route::post('/{company_name}/transaction/mandates-page', [App\Http\Controllers\TransactionController::class, 'mandatesPage'])->name('mandates-page');
                        Route::post('/{company_name}/transaction/create-mandate', [App\Http\Controllers\TransactionController::class, 'createMandate'])->name('create-mandate')->middleware('log.route');

                        Route::get('/download-mandate-form', function () {
                            return Storage::download('public/mandate_form/WBL_Direct_Debit_mandate_form.docx');
                        })->name('download-mandate-form');

                        //Card Management
                        Route::get('/{company_name}/card-management/company-card-management', [App\Http\Controllers\CardController::class, 'companyCardManagement'])->name('company-card-management');
                        Route::post('/{company_name}/card-management/company-card-management-page', [App\Http\Controllers\CardController::class, 'companyCardManagementPage'])->name('company-card-management-page');
                        Route::post('/{company_name}/card-management/create-company-card', [App\Http\Controllers\CardController::class, 'createCompanyCard'])->name('create-company-card')->middleware('log.route');

                        Route::post('/{company_name}/card-management/fund-withdraw-virtual-card', [App\Http\Controllers\CardController::class, 'fundWithdrawVirtualCard'])->name('fund-withdraw-virtual-card')->middleware('log.route');

                        Route::get('/{company_name}/customer/create_account', [App\Http\Controllers\CustomerController::class, 'create_account_page'])->name('create_accountx');
                        // Route::post('/reset_psw/{id}', [App\Http\Controllers\CustomerController::class, 'resetUserPSW'])->name('resetUserPSW');
                        Route::post('/verify-customer-info/bvn-info', [App\Http\Controllers\CustomerController::class, 'verify_customer_profile_bvn'])->name('verify_customer_profile_bvn');

                        //import
                        Route::get('/{company_name}/customer/import-account', [App\Http\Controllers\CustomerController::class, 'import_account_page'])->name('import_accountx');

                        Route::post('/customer/import', [App\Http\Controllers\CustomerController::class, 'import_OLD_wba_users'])->name('import_user');
                        //end import
                        Route::post('/customer/customer-psw-reset/{id}', [App\Http\Controllers\CustomerController::class, 'sendresetPSW']);
                        Route::post('/customer/customer-pin-reset/{id}', [App\Http\Controllers\CustomerController::class, 'sendresetPIN']);
                        Route::get('/{company_name}/customer/customer_profile/{id}', [App\Http\Controllers\CustomerController::class, 'customer_profile'])->name('customer_profile');

                        Route::get('/{company_name}/customer/view-profile/{id}', [App\Http\Controllers\CustomerController::class, 'view_viewprofile'])->name('view_viewprofile');

                        Route::get('/{company_name}/customer/customer_list/{id}', [App\Http\Controllers\CustomerController::class, 'get_customer_list'])->name('get_customer_list');
                        Route::get('/{company_name}/customer/customer_accounts/{uuid}', [App\Http\Controllers\CustomerController::class, 'getCustomerAccounts'])->name('get_customer_accounts');
                        Route::put('/customer/saving-product/{id}', [App\Http\Controllers\CustomerController::class, 'changeproduct'])->name('changeproduct');

                        //Physical Card APIs
                        Route::post('/{company_name}/customer/create-physical-card', [App\Http\Controllers\CustomerController::class, 'createPhysicalCard'])->name('create-physical-card')->middleware("log.route");
                        Route::post('/{company_name}/customer/add-physical-card', [App\Http\Controllers\CustomerController::class, 'addPhysicalCard'])->name('add-physical-card');
                        Route::post('/{company_name}/customer/fund-physical-card', [App\Http\Controllers\CustomerController::class, 'fundPhysicalCard'])->name('fund-physical-card');
                        Route::post('/{company_name}/customer/get-physical-card-balance', [App\Http\Controllers\CustomerController::class, 'getPhysicalCardBalance'])->name('get-physical-card-balance');

                        Route::get('/{company_name}/customer/customer-delete', [App\Http\Controllers\CustomerController::class, 'customer_delete'])->name('customer_delete');

                        Route::post('/customer/delete-restore-account-batch', [App\Http\Controllers\CustomerController::class, 'send_batch_delete_restore_account']);
                        Route::post('/customer/trash-single-account/{id}', [App\Http\Controllers\CustomerController::class, 'trash']);
                        Route::post('/customer/restore-single-account/{id}', [App\Http\Controllers\CustomerController::class, 'restore']);
                        Route::post('/customer/delete-single-account/{id}', [App\Http\Controllers\CustomerController::class, 'permanent_delete']);

                        Route::get('/{company_name}/customer/customer_profiles', [App\Http\Controllers\CustomerController::class, 'customer_profiles'])->name('customer_profiles');
                        Route::get('/customer/get_customer_profile/{id}', [App\Http\Controllers\CustomerController::class, 'get_specific_user_by_uuid']);

                        Route::get('/{company_name}/customer/physical-card-management', [App\Http\Controllers\CustomerController::class, 'physicalCardManagement'])->name('physical_card_management');
                        Route::get('/{company_name}/customer/card_requests', [App\Http\Controllers\CustomerController::class, 'physicalCardRequests'])->name('card_requests');

                        Route::get('/{company_name}/customer/view_account', [App\Http\Controllers\CustomerController::class, 'view_account_page'])->name('view_account');

                        Route::post('/{company_name}/customer/create_customer_account', [App\Http\Controllers\CustomerController::class, 'create_customer_account'])->name('create_customer_account-main')->middleware("log.route");

                        Route::post('/{company_name}/customer/customer_profile/updateimgprofile', [App\Http\Controllers\CustomerController::class, 'updateprofile']);

                        Route::get('/{company_name}/settings/main', [App\Http\Controllers\SettingsController::class, 'index'])->name('company_settings');
                        Route::get('/{company_name}/broadcast', [App\Http\Controllers\NotificationController::class, 'index']);

                        Route::get('/{company_name}/notification-log', [App\Http\Controllers\NotificationController::class, 'notificationLog']);

                        Route::post('/send-emailverification/{id}', [App\Http\Controllers\NotificationController::class, 'verifyCustomerEmail']);
                        Route::post('/send-broadcast', [App\Http\Controllers\NotificationController::class, 'send_broadcast']);
                    });

                    Route::post('/create_customer_account', [App\Http\Controllers\CustomerController::class, 'create_customer_account'])->name('create_customer_accountx')->middleware('log.route');

                    Route::post('/validate_details', [App\Http\Controllers\CustomerController::class, 'validate_details'])->name('validate_detailsx')->middleware('log.route');

                    Route::post('/update_profilex', [App\Http\Controllers\CustomerController::class, 'update_profilex'])->name('update_profilex');

                    Route::post('/check_cac_data', [App\Http\Controllers\CustomerController::class, 'check_cac'])->name('check_cacx')->middleware('log.route');

                    Route::get('/account_creation_success', [App\Http\Controllers\CustomerController::class, 'account_creation_success'])->name('account_creation_success');

                    Route::get('/userDataSource/{page}', [App\Http\Controllers\CustomerController::class, 'userDataSource'])->name('userDataSource');

                    Route::get('/userDataSelect', [App\Http\Controllers\CustomerController::class, 'userDatalist'])->name('userDatalist');
                    Route::get('/userDataSelect/{id}', [App\Http\Controllers\CustomerController::class, 'selectuserDatalist']); //select user wb_account_details //account type
                    Route::get('/staffDataSelect', [App\Http\Controllers\CustomerController::class, 'staffDatalist']);
                    Route::get('/fetch_txn_type', [App\Http\Controllers\UserReportController::class, 'txn_type_Datalist']);
                    Route::get('/userDataSelectStaff', [App\Http\Controllers\CompanyController::class, 'select2Staff']); //select user wb_account_details //account type

                    //Restrict User
                    Route::put('/restrictUser', [App\Http\Controllers\CustomerController::class, 'RestrictCustomer'])->name('RestrictCustomer');
                    Route::post('/removerestriction', [App\Http\Controllers\CustomerController::class, 'RemoveRestrictCustomer'])->name('RemoveRestrictCustomer');

                    Route::get('/userDataProduct/{id}', [App\Http\Controllers\CustomerController::class, 'fetchUserProduct'])->name('fetchUserProduct');

                    Route::post('/user-restriction-status/{id}', [App\Http\Controllers\CustomerRestrictionController::class, 'UserRestrictionInfo'])->name('UserRestrictionInfo');

                    Route::post('/wbaccountdetails/{id}', [App\Http\Controllers\CustomerController::class, 'getwbaccounttype'])->name('getwbaccounttype');

                    Route::post('/userDataSMS/{id}', [App\Http\Controllers\CustomerController::class, 'updateSMS'])->name('updateSMS');
                    Route::post('/userDataWhatsApp/{id}', [App\Http\Controllers\CustomerController::class, 'updateWhatsApp'])->name('updateWhatsApp');

                    Route::post('/GetUserDataSMS/{id}', [App\Http\Controllers\CustomerController::class, 'getSMSstatus'])->name('getSMSstatus');
                    Route::post('/company/{company_name}/user-statement/account-balance-info/{id}', [App\Http\Controllers\UserReportController::class, 'account_balance_info']);
                    Route::get('/company/fetch-statement-single/{id}', [App\Http\Controllers\UserReportController::class, 'ajax_account_single_statement']);

                    Route::post('/{company_name}/settings/ajax-change-sms', [App\Http\Controllers\CompanySettingsController::class, 'sms_swap']);
                    Route::post('/settings/settlement', [App\Http\Controllers\CompanySettingsController::class, 'saveSettlement'])->name('saveSettlement');
                    Route::post('/settings/security', [App\Http\Controllers\CompanySettingsController::class, 'saveTwoFactor'])->name('saveTwoFactor');
                    Route::post('/settings/remitta', [App\Http\Controllers\RemitaCredentialController::class, 'store']);
                    Route::get('/settings/remitta', [App\Http\Controllers\RemitaCredentialController::class, 'show']);
                    Route::post('/settings/update-settings-mail', [App\Http\Controllers\CompanySettingsController::class, 'updateSettingsMail']);
                    Route::post('/settings/update-settings-referral', [App\Http\Controllers\CompanySettingsController::class, 'updateSettingsReferral']);
                    Route::post('/settings/email_test', [App\Http\Controllers\SMSController::class, 'testEmail'])->name('test.Email');
                    Route::post('/company/settings/change-custom-email-state', [App\Http\Controllers\SMSController::class, 'change_custom_email_state'])->name('email.change_custom_email_state');

                    Route::post('/settings/transfer-staff-branch/{page}', [App\Http\Controllers\CompanySettingsController::class, 'Transfer']);

                    Route::post('/company/setting/save-domain', [App\Http\Controllers\CompanySettingsController::class, 'setDomain']);
                    Route::post('/company/save-savings-type', [App\Http\Controllers\CustomerController::class, 'add_account'])->middleware('log.route');
                    Route::get('/company/replace-bank/{accountId}', [App\Http\Controllers\CustomerController::class, 'replace_account'])->middleware('log.route');
                    Route::get('/company/view-customer-by-branch', [App\Http\Controllers\CustomerController::class, 'view_accounts']);
                    Route::get('/company/view-accounts-data', [App\Http\Controllers\CustomerController::class, 'view_accounts_data'])->middleware('log.route');

                    Route::post('/company/sendCreditTXN', [App\Http\Controllers\SavingsController::class, 'CreditCustomer'])->name('CreditCustomer');
                    Route::post('/company/sendDebitTXN', [App\Http\Controllers\SavingsController::class, 'DebitCustomer'])->name('DebitCustomer');
                    //generate TXN Receipt
                    Route::get('/transaction-receipt/{id}', [App\Http\Controllers\SavingsController::class, 'txnReceipt'])->name('txnReceipt');

                    Route::get('/company/get-savings-type', [App\Http\Controllers\UserReportController::class, 'get_company_savings_products']);

                    Route::get('/company/get-my-savings-products/{uuid}', [App\Http\Controllers\UserReportController::class, 'get_my_savings_products']);

                    Route::get('/userDataSource-dom', [App\Http\Controllers\DomAccountController::class, 'userDataSource']);

                    Route::post('/company/{shortname}/customer/unblock-dom/{uuid}', [App\Http\Controllers\DomAccountController::class, 'unblock']);
                    Route::post('/company/customer/unblock-all-dom', [App\Http\Controllers\DomAccountController::class, 'unblockAll']);
                });

                Route::prefix('company')->name('company.')->group(function () {
                    Route::get('/{company_name}/settings/show-payment-link', [App\Http\Controllers\CompanySettingsController::class, 'showPaymentLink'])->name('show-payment-link');
                    Route::post('/{company_name}/settings/show-payment-link-page', [App\Http\Controllers\CompanySettingsController::class, 'showPaymentLinkPage'])->name('show-payment-link-page');
                    Route::get('/{company_name}/settings/setup', [App\Http\Controllers\CompanySettingsController::class, 'index'])->name('company_setup');
                    Route::get('/settings/allowed/sms', [App\Http\Controllers\CompanySettingsController::class, 'smsAPI_list']);
                    Route::put('/{company_name}/settings/save', [App\Http\Controllers\CompanySettingsController::class, 'store'])->name('store-settings')->middleware('log.route');

                    Route::post('/settings/ajax-update-sms', [App\Http\Controllers\CompanySettingsController::class, 'ajax_update_sms'])->name('ajax_update_sms');
                    Route::post('/settings/generate-api-key', [App\Http\Controllers\CompanySettingsController::class, 'generateKey'])->name('generate-api-key');
                    Route::post('/settings/save-developer-settings', [App\Http\Controllers\CompanySettingsController::class, 'saveDeveloperSettings'])->name('save-developer-settings');

                    Route::post('/{company_name}/settings/ajax-change-sms', [App\Http\Controllers\CompanySettingsController::class, 'sms_swap']);

                    Route::post('/{company_name}/settings/update_all-sms-state/{id}', [App\Http\Controllers\CompanySettingsController::class, 'sms_allSMS']);

                    Route::group(['middleware' => 'check_plan'], function () {
                        Route::get('/{company_name}/staff-profile', [App\Http\Controllers\ProfileController::class, 'index'])->name('staff-profile');
                        Route::post('/staff-profile-post', [App\Http\Controllers\ProfileController::class, 'SaveProfile'])->name('UpdatestaffProfile');
                        Route::post('/staff-settlement-withdraw', [App\Http\Controllers\ProfileController::class, 'withdrawStaffSettlement'])->name('withdrawStaffSettlement');

                        //Branch operation
                        Route::get('/{company_name}/branch', [App\Http\Controllers\CompanyController::class, 'branch'])->name('branch');
                        Route::get('/branch-record/{id}', [App\Http\Controllers\CompanyController::class, 'branch_data']);
                        Route::get('/{company_name}/branch/branch_staff/{id}', [App\Http\Controllers\CompanyController::class, 'get_branch_staff'])->name('get_branch_staff');
                        Route::get('/branch/ajax_view', [App\Http\Controllers\CompanyController::class, 'fetch_branch_ajax'])->name('fetch_branch_ajax');
                        Route::put('/{company_name}/branch/ajax_addbranch', [App\Http\Controllers\CompanyController::class, 'ajax_add_branch'])->name('addbranch');
                        Route::post('/{company_name}/branch/ajax_updatebranch', [App\Http\Controllers\CompanyController::class, 'ajax_update_branch'])->name('updatebranch');
                        Route::delete('/{company_name}/branch/ajax_deletebranch/{id}', [App\Http\Controllers\CompanyController::class, 'destroy_branch'])->name('destroy_branch');

                        //Staff operation
                        Route::get('/{company_name}/staff', [App\Http\Controllers\CompanyController::class, 'staff'])->name('staff');
                        Route::get('/staff/ajax_view', [App\Http\Controllers\CompanyController::class, 'fetch_staff_ajax'])->name('fetch_staff_ajax');
                        Route::get('/{company_name}/staff-payroll', [App\Http\Controllers\CompanyController::class, 'staffPayroll'])->name('staff-payroll');
                        Route::get('/{company_name}/staff-payroll-report', [App\Http\Controllers\CompanyController::class, 'staffPayrollReport'])->name('staff-payroll-report');
                        Route::get('/payroll-report-data', [App\Http\Controllers\CompanyController::class, 'ajax_generate_payroll_report']); //callover
                        Route::get('/{company_name}/staff-payroll/ajax_payroll_view', [App\Http\Controllers\CompanyController::class, 'fetch_staff_payroll_ajax'])->name('ajax_payroll_view');
                        Route::post('/{company_name}/staff-payroll/setup-payroll', [App\Http\Controllers\CompanyController::class, 'ajax_setup_payroll'])->name('setup_payroll');
                        Route::get('/{company_name}/staff-payroll/get-staff-payroll-details/{id}', [App\Http\Controllers\CompanyController::class, 'getStaffPayrolDetails'])->name('get_staff_payroll_details');
                        Route::put('/{company_name}/staff/ajax_addstaff', [App\Http\Controllers\CompanyController::class, 'ajax_add_staff'])->name('addstaff');
                        Route::delete('/{company_name}/staff/ajax_deletestaff/{id}', [App\Http\Controllers\CompanyController::class, 'ajax_deletestaff'])->name('deletestaff');
                        Route::delete('/{company_name}/staff/ajax_recyclestaff', [App\Http\Controllers\CompanyController::class, 'ajax_recycle_staff'])->name('ajax_recycle_staff');
                        Route::get('/{company_name}/staff/staff_list/{id}', [App\Http\Controllers\CompanyController::class, 'get_staff_list'])->name('get_staff_list');
                        Route::get('/staff/staff_list', [App\Http\Controllers\CompanyController::class, 'list_all_staff'])->name('list_all_staff');

                        Route::post('/{company_name}/staff/ajax_updatestaff', [App\Http\Controllers\CompanyController::class, 'ajax_update_staff'])->name('ajax_update_staff');
                        Route::get('/returnAllBranchAndStaff', [App\Http\Controllers\CompanyController::class, 'returnAllBranchAndStaff'])->name('returnAllBranchAndStaff');
                    }); //
                });

                //Route::group(['middleware' => 'role:Company'], function () {

                Route::prefix('company')->name('company.')->group(function () {
                    //Plan operation
                    Route::get('/{company_name}/plan', [App\Http\Controllers\PlanController::class, 'plan'])->name('subplan');
                    Route::get('/plan', [App\Http\Controllers\PlanController::class, 'plan'])->name('plan');
                    Route::put('/plan/sub', [App\Http\Controllers\PlanController::class, 'subscribe'])->name('subscribe');
                    Route::get('/{company_name}/plan/invoice/{id}', [App\Http\Controllers\PlanController::class, 'subinvoicereview'])->name('subinvoicereview');
                    Route::get('/{company_name}/plan/list', [App\Http\Controllers\PlanController::class, 'subloglist'])->name('subloglist');

                    Route::get('/{company_name}/plan/transaction', [App\Http\Controllers\PlanController::class, 'viewsubpage']);
                    Route::get('/plan/transactionlist', [App\Http\Controllers\PlanController::class, 'transaction']);
                });
                //});

                // $this->middleware

                // Route::get('/dashboard', function () {
                //     // $category_name = '';
                //     $data = [
                //         'category_name' => 'dashboard',
                //         'page_name' => 'sales',
                //         'has_scrollspy' => 0,
                //         'scrollspy_offset' => '',
                //         'alt_menu' => 0,
                //     ];
                //     // $pageName = 'sales';
                //     return view('dashboard2')->with($data);
                // });

                // APPS
                Route::prefix('apps')->group(function () {
                    Route::get('/calendar', function () {
                        // $category_name = 'calendar';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'calendar',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'calendar';
                        return view('pages.apps.apps_calendar')->with($data);
                    });
                    Route::get('/chat', function () {
                        // $category_name = 'chat';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'chat',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'chat';
                        return view('pages.apps.apps_chat')->with($data);
                    });
                    Route::get('/contacts', function () {
                        // $category_name = 'contacts';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'contacts',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'contacts';
                        return view('pages.apps.apps_contacts')->with($data);
                    });
                    Route::get('/invoice', function () {
                        // $category_name = 'invoice';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'invoice',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'invoice';
                        return view('pages.apps.apps_invoice')->with($data);
                    });
                    Route::get('/mailbox', function () {
                        // $category_name = 'mailbox';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'mailbox',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'mailbox';
                        return view('pages.apps.apps_mailbox')->with($data);
                    });
                    Route::get('/notes', function () {
                        // $category_name = 'notes';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'notes',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'notes';
                        return view('pages.apps.apps_notes')->with($data);
                    });
                    Route::get('/scrumboard', function () {
                        $category_name = 'scrumboard';
                        $data          = [
                            'category_name'    => 'apps',
                            'page_name'        => 'scrumboard',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'scrumboard';
                        return view('pages.apps.apps_scrumboard')->with($data);
                    });
                    Route::get('/todoList', function () {
                        // $category_name = 'todo-list';
                        $data = [
                            'category_name'    => 'apps',
                            'page_name'        => 'todo-list',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,
                        ];
                        // $pageName = 'todo-list';
                        return view('pages.apps.apps_todoList')->with($data);
                    });
                });

                // Authentication
                Route::prefix('authentication')->group(function () {
                    Route::get('/lockscreen_boxed', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_boxed',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_boxed';
                        return view('pages.authentication.auth_lockscreen_boxed')->with($data);
                    });
                    Route::get('/lockscreen', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_default',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_default';
                        return view('pages.authentication.auth_lockscreen')->with($data);
                    });
                    Route::get('/login_boxed', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_boxed',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_boxed';
                        return view('pages.authentication.auth_login_boxed')->with($data);
                    });

                    Route::get('/pass_recovery_boxed', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_boxed',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_boxed';
                        return view('pages.authentication.auth_pass_recovery_boxed')->with($data);
                    });
                    Route::get('/pass_recovery', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_default',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_default';
                        return view('pages.authentication.auth_pass_recovery')->with($data);
                    });
                    Route::get('/register_boxed', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_boxed',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'auth_boxed';
                        return view('pages.authentication.auth_register_boxed')->with($data);
                    });
                    Route::get('/register', function () {
                        // $category_name = 'auth';
                        $data = [
                            'category_name'    => 'auth',
                            'page_name'        => 'auth_default',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                            'custom_page_name' => "Finova Setup Page",
                        ];
                        // $pageName = 'auth_default';
                        return view('pages.authentication.auth_register')->with($data);
                    });
                });

                // Charts
                // Route::prefix('charts')->group(function () {
                Route::get('/charts', function () {
                    // $category_name = 'chart';
                    $data = [
                        'category_name'    => 'charts',
                        'page_name'        => 'charts',
                        'has_scrollspy'    => 1,
                        'scrollspy_offset' => 140,
                        'alt_menu'         => 0,

                    ];
                    // $pageName = 'charts';
                    return view('pages.charts.charts_apex')->with($data);
                });
                // });

                // Components
                Route::prefix('components')->group(function () {
                    Route::get('/accordions', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'accordions',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'accordion';
                        return view('pages.components.component_accordion')->with($data);
                    });
                    Route::get('/blockui', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'blockui',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'blockui';
                        return view('pages.components.component_blockui')->with($data);
                    });
                    Route::get('/carousel', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'bootstrap_carousel',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'bootstrap_carousel';
                        return view('pages.components.component_bootstrap_carousel')->with($data);
                    });
                    Route::get('/cards', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'cards',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'cards';
                        return view('pages.components.component_cards')->with($data);
                    });
                    Route::get('/countdown', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'countdown',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'countdown';
                        return view('pages.components.component_countdown')->with($data);
                    });
                    Route::get('/counter', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'counter',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'counter';
                        return view('pages.components.component_counter')->with($data);
                    });
                    Route::get('/lightbox', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'lightbox',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'lightbox';
                        return view('pages.components.component_lightbox')->with($data);
                    });
                    Route::get('/list_group', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'list_group',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'list_group';
                        return view('pages.components.component_list_group')->with($data);
                    });
                    Route::get('/media_object', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'media_object',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'media_object';
                        return view('pages.components.component_media_object')->with($data);
                    });
                    Route::get('/modals', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'modals',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'modal';
                        return view('pages.components.component_modal')->with($data);
                    });
                    Route::get('/pricing_tables', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'pricing_table',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'pricing_table';
                        return view('pages.components.component_pricing_table')->with($data);
                    });
                    Route::get('/session_timeout', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'session_timeout',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'session_timeout';
                        return view('pages.components.component_session_timeout')->with($data);
                    });
                    Route::get('/notifications', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'notifications',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'snackbar';
                        return view('pages.components.component_snackbar')->with($data);
                    });
                    Route::get('/sweet_alerts', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'sweet_alerts',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'sweetalert';
                        return view('pages.components.component_sweetalert')->with($data);
                    });
                    Route::get('/tabs', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'tabs',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'tabs';
                        return view('pages.components.component_tabs')->with($data);
                    });
                    Route::get('/timeline', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'components',
                            'page_name'        => 'timeline',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'timeline';
                        return view('pages.components.component_timeline')->with($data);
                    });
                });

                // Drag and Drop
                // Route::prefix('drag_n_drop')->group(function () {
                Route::get('/drag_and_drop', function () {
                    // $category_name = '';
                    $data = [
                        'category_name'    => 'drag_n_drop',
                        'page_name'        => 'drag_n_drop',
                        'has_scrollspy'    => 0,
                        'scrollspy_offset' => '',
                        'alt_menu'         => 0,

                    ];
                    // $pageName = 'drag_n_drop';
                    return view('pages.drag_and_drop.dragndrop_dragula')->with($data);
                });
                // });

                // Elements
                Route::prefix('elements')->group(function () {
                    Route::get('/alerts', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'alerts',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'alerts';
                        return view('pages.elements.element_alerts')->with($data);
                    });
                    Route::get('/avatars', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'avatars',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'avatars';
                        return view('pages.elements.element_avatar')->with($data);
                    });
                    Route::get('/badges', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'badges',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'badges';
                        return view('pages.elements.element_badges')->with($data);
                    });
                    Route::get('/breadcrumbs', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'breadcrumbs',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'breadcrumbs';
                        return view('pages.elements.element_breadcrumbs')->with($data);
                    });
                    Route::get('/button_group', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'button_group',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'buttons_group';
                        return view('pages.elements.element_buttons_group')->with($data);
                    });
                    Route::get('/buttons', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'buttons',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'buttons';
                        return view('pages.elements.element_buttons')->with($data);
                    });
                    Route::get('/color_library', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'color_library',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'color_library';
                        return view('pages.elements.element_color_library')->with($data);
                    });
                    Route::get('/dropdown', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'dropdown',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'dropdown';
                        return view('pages.elements.element_dropdown')->with($data);
                    });
                    Route::get('/infobox', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'infobox',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'infobox';
                        return view('pages.elements.element_infobox')->with($data);
                    });
                    Route::get('/jumbotron', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'jumbotron',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'jumbotron';
                        return view('pages.elements.element_jumbotron')->with($data);
                    });
                    Route::get('/loaders', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'loaders',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'loader';
                        return view('pages.elements.element_loader')->with($data);
                    });
                    Route::get('/pagination', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'pagination',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'pagination';
                        return view('pages.elements.element_pagination')->with($data);
                    });
                    Route::get('/popovers', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'popovers',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'popovers';
                        return view('pages.elements.element_popovers')->with($data);
                    });
                    Route::get('/progress_bar', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'progress_bar',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'progress_bar';
                        return view('pages.elements.element_progress_bar')->with($data);
                    });
                    Route::get('/search', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'search',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'search';
                        return view('pages.elements.element_search')->with($data);
                    });
                    Route::get('/tooltips', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'tooltips',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'tooltips';
                        return view('pages.elements.element_tooltips')->with($data);
                    });
                    Route::get('/treeview', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'treeview',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'treeview';
                        return view('pages.elements.element_treeview')->with($data);
                    });
                    Route::get('/typography', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'elements',
                            'page_name'        => 'typography',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'typography';
                        return view('pages.elements.element_typography')->with($data);
                    });
                });

                // Font Icons
                // Route::prefix('fonticons')->group(function () {
                Route::get('/font_icons', function () {
                    // $category_name = 'fonticons';
                    $data = [
                        'category_name'    => 'fonticons',
                        'page_name'        => 'font_icons',
                        'has_scrollspy'    => 1,
                        'scrollspy_offset' => 140,
                        'alt_menu'         => 0,

                    ];
                    // $pageName = 'fonticons';
                    return view('pages.fonticons.fonticons')->with($data);
                });
                // });

                // Forms
                Route::prefix('forms')->group(function () {
                    Route::get('/basic', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'basic',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'bootstrap_basic';
                        return view('pages.forms.form_bootstrap_basic')->with($data);
                    });
                    Route::get('/bootstrap_select', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'bootstrap_select',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'bootstrap_select';
                        return view('pages.forms.form_bootstrap_select')->with($data);
                    });
                    Route::get('/touchspin', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'touchspin',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'bootstrap_touchspin';
                        return view('pages.forms.form_bootstrap_touchspin')->with($data);
                    });
                    Route::get('/checkbox_radio', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'checkbox_radio',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'checkbox_radio';
                        return view('pages.forms.form_checkbox_radio')->with($data);
                    });
                    Route::get('/clipboard', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'clipboard',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'clipboard';
                        return view('pages.forms.form_clipboard')->with($data);
                    });
                    Route::get('/date_range_picker', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'date_range_picker',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'date_range_picker';
                        return view('pages.forms.form_date_range_picker')->with($data);
                    });
                    Route::get('/file_upload', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'file_upload',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'fileupload';
                        return view('pages.forms.form_fileupload')->with($data);
                    });
                    Route::get('/input_group', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'input_group',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'input_group_basic';
                        return view('pages.forms.form_input_group_basic')->with($data);
                    });
                    Route::get('/input_mask', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'input_mask',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'input_mask';
                        return view('pages.forms.form_input_mask')->with($data);
                    });
                    Route::get('/layouts', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'layouts',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'layouts';
                        return view('pages.forms.form_layouts')->with($data);
                    });
                    Route::get('/markdown_editor', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'markdown',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'markdown';
                        return view('pages.forms.form_markdown')->with($data);
                    });
                    Route::get('/maxlength', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'maxlength',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'maxlength';
                        return view('pages.forms.form_maxlength')->with($data);
                    });
                    Route::get('/quill_editor', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'quill',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'quill';
                        return view('pages.forms.form_quill')->with($data);
                    });
                    Route::get('/select2', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'select2',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'select2';
                        return view('pages.forms.form_select2')->with($data);
                    });
                    Route::get('/switches', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'switches',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'switches';
                        return view('pages.forms.form_switches')->with($data);
                    });
                    Route::get('/typeahead', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'typeahead',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'typeahead';
                        return view('pages.forms.form_typeahead')->with($data);
                    });
                    Route::get('/validation', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'validation',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'validation';
                        return view('pages.forms.form_validation')->with($data);
                    });
                    Route::get('/wizards', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'forms',
                            'page_name'        => 'wizards',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 100,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'wizard';
                        return view('pages.forms.form_wizard')->with($data);
                    });
                });

                // Maps
                Route::get('/maps', function () {
                    // $category_name = '';
                    $data = [
                        'category_name'    => 'maps',
                        'page_name'        => 'maps',
                        'has_scrollspy'    => 1,
                        'scrollspy_offset' => 140,
                        'alt_menu'         => 0,

                    ];
                    // $pageName = 'maps';
                    return view('pages.maps.map_jvector')->with($data);
                });

                // Pages
                Route::prefix('pages')->group(function () {
                    Route::get('/coming_soon', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'coming_soon',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'coming_soon';
                        return view('pages.pages.pages_coming_soon')->with($data);
                    });
                    Route::get('/contact_us_form', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'contact_us',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'contact_us';
                        return view('pages.pages.pages_contact_us')->with($data);
                    });
                    Route::get('/error_404', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'error404',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'error404';
                        return view('pages.pages.pages_error404')->with($data);
                    });
                    Route::get('/error_500', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'error500',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'error500';
                        return view('pages.pages.pages_error500')->with($data);
                    });
                    Route::get('/error_503', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'error503',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'error503';
                        return view('pages.pages.pages_error503')->with($data);
                    });
                    Route::get('/faq', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'faq',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'faq';
                        return view('pages.pages.pages_faq')->with($data);
                    });
                    Route::get('/faq2', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'faq2',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'faq2';
                        return view('pages.pages.pages_faq2')->with($data);
                    });
                    Route::get('/helpdesk', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'helpdesk',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'helpdesk';
                        return view('pages.pages.pages_helpdesk')->with($data);
                    });
                    Route::get('/maintenence', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'maintenence',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'maintenence';
                        return view('pages.pages.pages_maintenence')->with($data);
                    });
                    Route::get('/privacy_policy', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'pages',
                            'page_name'        => 'privacy',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'privacy';
                        return view('pages.pages.pages_privacy')->with($data);
                    });
                });

                // Starter Kit
                Route::prefix('starter-kit')->group(function () {
                    Route::get('/collapsible', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'starter_kits',
                            'page_name'        => 'alt_menu',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 1,

                        ];
                        // $pageName = 'alt_menu';
                        return view('pages.starter-kit.starter_kit_alt_menu')->with($data);
                    });
                    Route::get('/blank_page', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'starter_kits',
                            'page_name'        => 'blank_page',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'blank_page';
                        return view('pages.starter-kit.starter_kit_blank_page')->with($data);
                    });
                    Route::get('/boxed', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'starter_kits',
                            'page_name'        => 'boxed',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'boxed';
                        return view('pages.starter-kit.starter_kit_boxed')->with($data);
                    });
                    Route::get('/breadcrumbs', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'starter_kits',
                            'page_name'        => 'breadcrumb',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'breadcrumb';
                        return view('pages.starter-kit.starter_kit_breadcrumbs')->with($data);
                    });
                });

                // Tables
                Route::prefix('tables')->group(function () {
                    Route::get('/bootstrap_basic', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'bootstrap_basic_table',
                            'page_name'        => 'bootstrap_basic_table',
                            'has_scrollspy'    => 1,
                            'scrollspy_offset' => 140,
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'bootstrap_basic_table';
                        return view('pages.tables.table_basic')->with($data);
                    });
                    Route::prefix('datatables')->group(function () {
                        Route::get('/alternative_pagination', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'alternative_pagination',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'alternative_pagination';
                            return view('pages.tables.table_dt_alternative_pagination')->with($data);
                        });
                        Route::get('/basic-light', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'basic-light',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'basic-light';
                            return view('pages.tables.table_dt_basic-light')->with($data);
                        });
                        Route::get('/basic', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'basic',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'basic';
                            return view('pages.tables.table_dt_basic')->with($data);
                        });
                        Route::get('/custom', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'custom',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'custom';
                            return view('pages.tables.table_dt_custom')->with($data);
                        });
                        Route::get('/html5', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'html5',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'html5';
                            return view('pages.tables.table_dt_html5')->with($data);
                        });
                        Route::get('/live_dom_ordering', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'live_dom_ordering',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'live_dom_ordering';
                            return view('pages.tables.table_dt_live_dom_ordering')->with($data);
                        });
                        Route::get('/miscellaneous', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'miscellaneous',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'miscellaneous';
                            return view('pages.tables.table_dt_miscellaneous')->with($data);
                        });
                        Route::get('/multi-column_ordering', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'multi-column_ordering',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'multi-column_ordering';
                            return view('pages.tables.table_dt_multi-column_ordering')->with($data);
                        });
                        Route::get('/multiple_tables', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'multiple_tables',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'multiple_tables';
                            return view('pages.tables.table_dt_multiple_tables')->with($data);
                        });
                        Route::get('/ordering_sorting', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'ordering_sorting',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'ordering_sorting';
                            return view('pages.tables.table_dt_ordering_sorting')->with($data);
                        });
                        Route::get('/range_search', function () {
                            // $category_name = '';
                            $data = [
                                'category_name'    => 'datatable',
                                'page_name'        => 'range_search',
                                'has_scrollspy'    => 0,
                                'scrollspy_offset' => '',
                                'alt_menu'         => 0,

                            ];
                            // $pageName = 'range_search';
                            return view('pages.tables.table_dt_range_search')->with($data);
                        });
                    });
                });

                // Users
                Route::prefix('users')->group(function () {
                    Route::get('/account_settings', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'users',
                            'page_name'        => 'account_settings',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,

                        ];
                        // $pageName = 'account_settings';
                        return view('pages.users.user_account_setting')->with($data);
                    });
                    Route::get('/profile', function () {
                        // $category_name = '';
                        $data = [
                            'category_name'    => 'users',
                            'page_name'        => 'profile',
                            'has_scrollspy'    => 0,
                            'scrollspy_offset' => '',
                            'alt_menu'         => 0,
                        ];
                        // $pageName = 'profile';
                        return view('pages.users.user_profile')->with($data);
                    });
                });

                // Widgets
                Route::get('/widgets', function () {
                    // $category_name = '';
                    $data = [
                        'category_name'    => 'widgets',
                        'page_name'        => 'widgets',
                        'has_scrollspy'    => 0,
                        'scrollspy_offset' => '',
                        'alt_menu'         => 0,

                    ];
                    // $pageName = 'widgets';
                    return view('pages.widgets.widgets')->with($data);
                });
            });
        });
    });

    Route::get('/loging', function () {
        // $category_name = 'auth';
        $data = [
            'category_name'    => 'auth',
            'page_name'        => 'auth_default',
            'page_menu'        => 'login_to_wired_banking_lite',
            'has_scrollspy'    => 0,
            'scrollspy_offset' => '',
            'alt_menu'         => 0,
        ];
        // $pageName = 'auth_default';
        return view('auth.login')->with($data);
    });
});
// Route::post('/resetImport', [App\Http\Controllers\TestController::class, 'resetImport']);

Route::post('/company/validate_details_public', [App\Http\Controllers\PublicTransactionController::class, 'validate_details'])->name('validate_details_public');
Route::post('/company/transaction/get-current-exchange-rates', [App\Http\Controllers\TransactionController::class, 'getExchangeRates'])->name('get-current-exchange-rates');
Route::post('/transaction/verify-bank-account', [App\Http\Controllers\TransactionController::class, 'verifyBankAccount'])->name('verify-bank-account');
Route::post('/transaction/verify-wallet', [App\Http\Controllers\TransactionController::class, 'verifyWallet'])->name('verify-wallet');
Route::get('/verify-account/{verification_hash}', [App\Http\Controllers\Auth\RegisterController::class, 'verifyAccount'])->name('verify-account');
Route::get('/resend-email/{verification_hash}', [App\Http\Controllers\Auth\RegisterController::class, 'resendEmil'])->name('resend-email');
Route::get('/ma-resend-email', [App\Http\Controllers\Auth\RegisterController::class, 'mainResendEmil'])->middleware('auth')->name('main-resend');
Route::get('/transaction/type', [App\Http\Controllers\TransactionController::class, 'transactionListType']);

Route::get('/company/customer-registration/{company_reference}', [App\Http\Controllers\PublicTransactionController::class, 'customerRegistration'])->name('customer-registration');
Route::post('/company/process-customer-registration', [App\Http\Controllers\PublicTransactionController::class, 'create_customer_account'])->name('customer-registration-public')->middleware("log.route");
Route::get('/transaction/payment-link/{company_reference}', [App\Http\Controllers\PublicTransactionController::class, 'paymentLink'])->name('payment-link');
Route::post('/transaction/initiate-payment-link', [App\Http\Controllers\PublicTransactionController::class, 'initiatePaymentLink'])->name('initiate-payment-link');
Route::get('/transaction/verify-fund-wallet', [App\Http\Controllers\PublicTransactionController::class, 'verifyFundWallet'])->name('verify-fund-wallet');

//Route::post('register/', 'Auth\RegisterController@create')->name('register');
Route::post('companyregister', [RegisterController::class, 'create'])->name('company-register');
Route::post('validate-email-phone', [RegisterController::class, 'validateEmailPhone'])->name('validate-email-phone');
Route::prefix('/password')->group(function () {
    Route::get('/reset', [ResetPasswordController::class, 'resetPasswordPage'])->name('reset-password-page');
    Route::post('/submit-reset-request', [ResetPasswordController::class, 'submitResetRequest'])->name('submit-reset-request');
    Route::get('/reset-password/{password_hash}', [ResetPasswordController::class, 'resetPassword'])->name('reset-password');
    Route::post('/set-new-password', [ResetPasswordController::class, 'setNewPassword'])->name('set-new-password');
});
