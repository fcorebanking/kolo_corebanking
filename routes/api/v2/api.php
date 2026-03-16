<?php

use App\Http\Controllers\api\v2\CardController;
use App\Http\Controllers\api\v2\InvestmentController;
use App\Http\Controllers\api\v2\LoanController;
use App\Http\Controllers\api\v2\LoginController;
use App\Http\Controllers\api\v2\MeetingController;
use App\Http\Controllers\api\v2\NibbsController;
use App\Http\Controllers\api\v2\OrderController;
use App\Http\Controllers\api\v2\RegisterController;
use App\Http\Controllers\api\v2\TransactionController;
use App\Http\Controllers\api\v2\user\UserController;
use App\Http\Controllers\api\v2\UtilityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get-company-setting-with-version/{id}', [UtilityController::class, 'check_app_version']);

Route::prefix('/users')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/api-register', [RegisterController::class, 'apiRegister']);
    Route::post('/verify-otp', [UserController::class, 'verifyOTP']);
    Route::post('/resend-otp', [UserController::class, 'resendOtp']);
    Route::post('/send-otp', [UserController::class, 'sendOtp']);
    Route::post('/forgot-password-send-otp', [UserController::class, 'forgotPasswordSendOTP']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']); //users/change-pin
    Route::post('/confirm-otp', [UserController::class, 'confirmOTP']);
    Route::middleware('auth:api')->post('/change-password', [UserController::class, 'changePassword']);
    Route::middleware('auth:api')->post('/change-pin', [UserController::class, 'resetPin']);
    //resetPin
    Route::post('/upload-file', [UserController::class, 'uploadFile']);
    Route::middleware('auth:api')->get('/get-details', [UserController::class, 'getDetails']);
    Route::middleware('auth:api')->put('/update-profile', [UserController::class, 'updateProfile']);
    Route::middleware('auth:api')->get('/all', [UserController::class, 'index']);

    //New API
    Route::middleware('checkApiKey')->get('/virtual-accounts', [UserController::class, 'getVirtualAccounts']);

});

Route::get('/perform-providus-account-action', [ProvidusController::class, 'providusAccountReassignment']);
Route::post('/create_providus_account_test', [ProvidusController::class, 'createAccount']);

Route::prefix('/ussd-apis')->group(function () {
    Route::post('/bank-transfer', [TransactionController::class, 'transferToBankAccountUSSD']);
});

Route::post('/verify-bank-account', [TransactionController::class, 'verifyBankAccount'])->name('v2-verify-bank-account');
Route::post('/verify-account-full', [TransactionController::class, 'verifyBankAccountFullname'])->name('v2-verify-account-full');
Route::post('/verify-bvn', [TransactionController::class, 'verifyBVN'])->name('v2-verify-bvn');
Route::get('/get-banks', [TransactionController::class, 'getAllBanks'])->name('v2-get-banks');

//saviour modified
Route::get('/get-meter-type', [TransactionController::class, 'getMeterType']);
Route::get('/get-meter-service', [TransactionController::class, 'getMeterService']);
Route::get('/get-meter-service-type', [TransactionController::class, 'getMeterServiceType']);

Route::get('/login-kuda', [KudaController::class, 'login']);
Route::post('/kuda-create-account', [KudaController::class, 'createAccount']);
Route::post('/kuda-webhook', [TransactionController::class, 'kudaWebhook']);

//Route::middleware("restrictIP")->middleware('checkApiKey')->post('/create-account', [SpecialAPIController::class, 'createAccount']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/get-balance/{accountNumber}', [SpecialAPIController::class, 'getAccountBalance']);
//Route::middleware("restrictIP")->post('/generate-key', [SpecialAPIController::class, 'generateKey']);
//Route::middleware("restrictIP")->post('/save-developer-settings', [SpecialAPIController::class, 'saveDeveloperSettings']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->post('/bank-transfer', [SpecialAPIController::class, 'transferToBankAccount']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/transaction/query/{reference}', [SpecialAPIController::class, 'queryTransaction']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/account-statement', [SpecialAPIController::class, 'getAccountStatement']);

Route::post('/transactions/woven-webhook', [TransactionController::class, 'wovenWebhook'])->name('v2-woven-webhook')->middleware('log.route');
Route::post('/transactions/flutterwave-webhook', [TransactionController::class, 'flutterwaveWebhook'])->name('v2-flutterwave-webhook')->middleware('log.route');
Route::post('/transactions/providus-webhook', [TransactionController::class, 'providusWebhook'])->name('v2-providus-webhook')->middleware('log.route');
Route::post('/transactions/gtb-webhook', [TransactionController::class, 'gtbWebhook'])->name('v2-gtb-webhook')->middleware('log.route');
Route::post('/transactions/monnify-webhook', [TransactionController::class, 'monnifyWebhook'])->name('v2-monnify-webhook')->middleware('log.route');
Route::post('/transactions/cyberpay-webhook', [TransactionController::class, 'cyberpayWebhook'])->name('v2-cyberpay-webhook')->middleware('log.route');
Route::post('/transactions/grupp-webhook', [TransactionController::class, 'gruppServicesWebhook'])->name('v2-grupp-webhook')->middleware('log.route');

Route::prefix('/transactions/errandpay')->group(function () {
    Route::post('services-webhook', [TransactionController::class, 'servicesWebhook'])->name('v2-services-webhook')->middleware('log.route');
    Route::post('funds-transfer-webhook', [TransactionController::class, 'errandPayFundsTransferWebhook'])->name('v2-errandpay-funds-transfer-webhook')->middleware('log.route');
    Route::post('bills-payment-webhook', [TransactionController::class, 'errandPayBillsPaymentWebhook'])->name('v2-errandpay-bills-payment-webhook')->middleware('log.route');
    Route::post('check-balance', [TransactionController::class, 'errandPaycheckBalance'])->name('v2-errandpay-check-balance')->middleware('log.route');
    Route::post('get-customer-details', [TransactionController::class, 'getCustomerDetails'])->name('get-customer-details')->middleware('log.route');

});

Route::post('/transaction/get-exchange-rates', [TransactionController::class, 'getExchangeRates'])->name('v2-get-exchange-rates');
Route::middleware("restrictIP")->post('/users/restrict-account', [UserController::class, 'restrictUserAccount'])->name('v2-restrict-user-account');

Route::prefix('/accounts')->group(function () {

    Route::middleware("restrictIP")->post('/block', [NibbsController::class, 'blockUserAccount'])->name('v2-block-user-account');
    Route::middleware("restrictIP")->post('/unblock', [NibbsController::class, 'unblockUserAccount'])->name('v2-unblock-user-account');
    Route::middleware("restrictIP")->post('/credit-user', [NibbsController::class, 'creditUser'])->name('v2-credit-customer');
    Route::middleware("restrictIP")->post('/debit-user', [NibbsController::class, 'debitUser'])->name('v2-debit-user');
    Route::middleware("restrictIP")->post('/balance', [NibbsController::class, 'getAccountBalance'])->name('v2-account-balance');
    Route::middleware("restrictIP")->get('/lookup/{account_number}', [NibbsController::class, 'lookupAccount'])->name('v2-look-account');
    Route::middleware("restrictIP")->post('/amount/block', [NibbsController::class, 'lienAccount'])->name('v2-lien-account');
    Route::middleware("restrictIP")->post('/amount/unblock', [NibbsController::class, 'unlienAccount'])->name('v2-unlien-account');
    Route::middleware("restrictIP")->post('/advice', [NibbsController::class, 'accountMandate'])->name('v2-account-manadate');
    Route::middleware("restrictIP")->post('/advice/debit-user', [NibbsController::class, 'directDebitMandate'])->name('v2-direct-debit-mandate');
    Route::middleware("restrictIP")->post('/fi', [NibbsController::class, 'addNewBanks'])->name('v2-add-new-banks');
    Route::middleware("restrictIP")->post('/advice/credit-user', [NibbsController::class, 'creditUser'])->name('v2-credit-user-advice');
    // Route::get('/transaction/{reference}', [SpecialAPIController::class, 'queryTransaction']);

    Route::middleware("restrictIP")->post('/funds-transfer-advice/credit-user', [NibbsController::class, 'reverseFundsTransferCredit'])->name('v2-funds-transfer-reversal-credit-user');
    Route::middleware("restrictIP")->post('/funds-transfer-advice/debit-user', [NibbsController::class, 'reverseFundsTransferDebit'])->name('v2-funds-transfer-reversal-debit-user');
});
Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/updateFCM-token', [HomeController::class, 'updateToken']);
    Route::post('/meetinginfo', [MeetingController::class, 'meetingDetails']);

    Route::prefix('/ecommerce')->group(function () {
        Route::post('/items', [OrderController::class, 'index']);
        Route::post('/buy-item', [OrderController::class, 'makeOrder']);
        Route::post('/view-all-order', [OrderController::class, 'showOrdersAll']);
        Route::post('/view-pending-order', [OrderController::class, 'showOrderPending']);
        Route::post('/cancel-order', [OrderController::class, 'cancelOrders']);
    });
});
//Transactions
Route::prefix('/transactions')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::post('/verify-wallet-account', [TransactionController::class, 'verifyWalletAccount']);
        Route::post('/wallet-transfer', [TransactionController::class, 'walletTransfer']);

        //Beeting endpoints
        Route::get('/get-betting-companies', [TransactionController::class, 'getBettingCompanies']);
        Route::post('/validate-bet-account', [TransactionController::class, 'validateBetAccount'])->middleware('log.route');;
        Route::post('/topup-betting', [TransactionController::class, 'topupBetting'])->middleware('log.route');;

        Route::middleware('checkAccountStatus')->post('/initiate-transaction', [TransactionController::class, 'initiateTransaction'])->middleware('log.route');;
        Route::get('/history', [TransactionController::class, 'transactionHistory']);
        Route::middleware('checkAccountStatus')->post('/buy-airtime', [TransactionController::class, 'buyAirtime'])->middleware('log.route');;
        Route::middleware('checkAccountStatus')->post('/buy-data-bundle', [TransactionController::class, 'buyDataBundle'])->middleware('log.route');;
        Route::post('/verify-meter-number', [TransactionController::class, 'verifyMeterNumber']);
        Route::middleware('checkAccountStatus')->post('/buy-electricity', [TransactionController::class, 'buyElectricity'])->middleware('log.route');;
        Route::middleware('checkAccountStatus')->post('/subscribe-cable-tv', [TransactionController::class, 'subscribeCableTV']);
        Route::get('/get-subscriptions', [TransactionController::class, 'getSubscriptions']);
        Route::post('/verify-smart-card', [TransactionController::class, 'verifySmartCard']);
        Route::get('/get-data-bundles/{networkProvider}', [TransactionController::class, 'getDataBundles']);
        Route::middleware('checkAccountStatus')->post('/bank-transfer', [TransactionController::class, 'transferToBankAccount'])->middleware('log.route');

    });
    Route::middleware('checkApiKey')->get('/list', [TransactionController::class, 'getAllTransactions']);
    Route::middleware('checkApiKey')->post('/user-transactions', [TransactionController::class, 'getUserTransactions'])->middleware('log.route');;
    Route::middleware('checkApiKey')->get('/get-company-balance', [TransactionController::class, 'getCompanyBalance'])->middleware('log.route');;
    Route::middleware('checkApiKey')->post('/company-bank-transfer', [TransactionController::class, 'companyBankTransfer'])->middleware('log.route');;
});

//Cards
Route::prefix('/virtual-cards')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::get('/block-unblock', [CardController::class, 'blockUnblockCard']);

        Route::middleware('checkAccountStatus')->post('/create', [CardController::class, 'createUserCard']);
        Route::middleware('checkAccountStatus')->post('/fund', [CardController::class, 'fundVirtualCard']);
        Route::middleware('checkAccountStatus')->post('/withdraw', [CardController::class, 'withdrawVirtualCard']);
        Route::get('/get-balance', [CardController::class, 'getBalance']);
        Route::get('/get-card-details', [CardController::class, 'getCardDetails']);
        Route::get('/get-card', [CardController::class, 'getVirtualCard']);
        Route::get('/transactions', [CardController::class, 'transactionHistory']);
    });
});
//Cards
Route::prefix('/loan')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::get('/eligibility', [LoanController::class, 'eligibility']);
        Route::post('/process-loan', [LoanController::class, 'processLoan']);
        Route::post('/send-process-loan', [LoanController::class, 'PostBooklLoan']);
        Route::get('/list-loan', [LoanController::class, 'listUserLoan']);
    });
});
Route::prefix('/investment')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::get('/investment-variable', [InvestmentController::class, 'investmentVariable']);
        Route::post('/process-investment', [InvestmentController::class, 'processInvestment']);
        Route::post('/send-process-investment', [InvestmentController::class, 'bookinvestment']);
        Route::get('/list-investment', [InvestmentController::class, 'viewInvestment']);
        Route::post('/terminate-investment/{id}', [InvestmentController::class, 'ajax_investmentstatus']);
    });
});
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/transaction/get-info/{id}', [TransactionController::class, 'txnInfo']);
});
