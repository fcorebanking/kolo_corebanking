<?php

use App\Http\Controllers\api\v1\CardController;
use App\Http\Controllers\api\v1\CustomerSavingController;
use App\Http\Controllers\api\v1\InternationalTransaction;
use App\Http\Controllers\api\v1\InvestmentController;
use App\Http\Controllers\api\v1\LoanController;
use App\Http\Controllers\api\v1\LoginController;
use App\Http\Controllers\api\v1\MeetingController;
use App\Http\Controllers\api\v1\OrderController;
use App\Http\Controllers\api\v1\RegisterController;
use App\Http\Controllers\api\v1\SalaryController;
use App\Http\Controllers\api\v1\TransactionController;
use App\Http\Controllers\api\v1\user\UserController;
use App\Http\Controllers\api\v1\UtilityController;
use App\Http\Controllers\CompanyController; 
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Middleware\LogRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ThirdParty\GTBController;
use App\Http\Controllers\api\v1\TargetSavingsController;
use App\Http\Controllers\api\v1\FixedSavingsController;
use App\Http\Controllers\api\v1\CampaignController;
use App\Http\Controllers\api\v1\AjoController;
use App\Http\Controllers\ThirdParty\NinePSBSandboxController;

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

Route::get('/webhook-international', [InternationalTransaction::class, 'webhook']);
Route::post('/twilio-webhook', [TransactionController::class, 'twilioWebhook']);
Route::post('/test-finova-webhook', [TransactionController::class, 'finovaWebhook']);
Route::post('/4ts-finova-webhook', [TransactionController::class, 'finova4tsWebhook']);
Route::post('/create-physical-card', [TransactionController::class, 'createPhysicalCard']);
Route::get('/get-api-session', [TransactionController::class, 'testAPIExternal']);
Route::post('/admin-process-verify-reverse-transactions', [TransactionController::class, 'verifyAndReverseTransactionAdmin']);
Route::post('/bridge-webhook', [TransactionController::class, 'bridgeWebhook']);
Route::post('/9psb-test-hash', [TransactionController::class, 'ninetyPSBTestHash']);
Route::post('/9psb-webhook', [TransactionController::class, 'ninetyPSBWebhook']);
Route::post('/create-vpay-vaccount', [TransactionController::class, 'createVPayAccount']);
Route::post('/initiate-create-safehaven-vaccount', [TransactionController::class, 'initiateCreateSafehavenMaintenancAccount']);
Route::post('/create-safehaven-vaccount', [TransactionController::class, 'createSafehavenMaintenancAccount']);
Route::get('/company-tos-link', [UserController::class, 'getTOSLinkCompany']);

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get-company-setting-with-version/{id}', [UtilityController::class, 'check_app_version']);

Route::prefix('/users')->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/login-v2', [LoginController::class, 'loginV2']);
    Route::post('/pin-login', [LoginController::class, 'loginPin'])->middleware('log.route');

    Route::post('/register', [RegisterController::class, 'register']);
    Route::post('/create-account', [RegisterController::class, 'noBvnRegister']);
    Route::post('/verify-bvn-v2', [RegisterController::class, 'verifyBVN2'])->name('verify-bvn-v2');
    Route::get('/branchs', [RegisterController::class, 'branch']);
    Route::get('/company-product', [RegisterController::class, 'product']);

    Route::post('/ussd-register', [RegisterController::class, 'ussdRegister']);
    Route::post('/api-register', [RegisterController::class, 'apiRegister'])->middleware('log.route');
    Route::post('/verify-otp', [UserController::class, 'verifyOTP']);
    Route::post('/resend-otp', [UserController::class, 'resendOtp']);
    Route::post('/send-otp', [UserController::class, 'sendOtp']);
    Route::post('/forgot-password-send-otp', [UserController::class, 'forgotPasswordSendOTP']);
    Route::post('/reset-password', [UserController::class, 'resetPassword']); //users/change-pin
    Route::post('/confirm-otp', [UserController::class, 'confirmOTP']);
    Route::middleware('auth:api')->post('/add-account', [UserController::class, 'addAccount'])->middleware('log.route');
    Route::middleware('auth:api')->get('/get-tos-link', [UserController::class, 'getTOSLink'])->middleware('log.route');

    Route::middleware("checkApiKey")->get('/get-balance/{accountNumber}', [UserController::class, 'getAccountBalance']);

    Route::middleware('auth:api')->post('/change-password', [UserController::class, 'changePassword']);
    Route::middleware('auth:api')->post('/change-pin', [UserController::class, 'resetPin']);

    //Agent Account
    Route::group(['middleware' => 'auth:api'], function () {
        Route::post('/add-agent-customer', [UserController::class, 'addAgentCustomer']);
        Route::post('/agent-customer', [UserController::class, 'viewmyCustomer']);
        Route::post('/top-agent-customer', [UserController::class, 'topup_script']);
        Route::post('/request-agent', [UserController::class, 'requestAgent']);
        Route::get('/esusu-product', [UserController::class, 'esusuProduct']);
        Route::get('/esusu-transaction', [UserController::class, 'EsusTXNhistory']);
        Route::post('/esusu-withdrawal', [UserController::class, 'initWithdraw']);
        //EsusTXNhistory
        Route::get('/playground', [UserController::class, 'playground']);
        // esusuProduct
        Route::delete('/delete-account', [UserController::class, 'deleteRequest']);
        //Talent
        //   Route::get('/talent-category', [TalentModuleController::class, 'ViewCategory']);
        // Route::get('/talent/{id}', [TalentModuleController::class, 'viewTalent']);
        //    Route::post('/talent/{id}', [TalentModuleController::class, 'RegisterTalent']);
        //   Route::get('/talent-charges', [TalentModuleController::class, 'charges']);
        //    Route::post('/vote-talent/{id}', [TalentModuleController::class, 'VoteTalent']);
        //    Route::get('/view-talent/{id}', [TalentModuleController::class, 'ViewTalentVote']);

    });
    //resetPin
    Route::post('/upload-file', [UserController::class, 'uploadFile']);
    Route::post('/send-tier-notice', [UserController::class, 'sendTierNotice']);
    Route::middleware('auth:api')->get('/get-details', [UserController::class, 'getDetails'])->withoutMiddleware([LogRoute::class]);
    Route::middleware('auth:api')->put('/update-profile', [UserController::class, 'updateProfile']);
    Route::middleware('auth:api')->get('/all', [UserController::class, 'index']);

    //New API
    Route::middleware('checkApiKey')->get('/virtual-accounts', [UserController::class, 'getVirtualAccounts']);
});

Route::get('/perform-providus-account-action', [ProvidusController::class, 'providusAccountReassignment']);
Route::post('/create_providus_account_test', [ProvidusController::class, 'createAccount']);
Route::get('/check_unused_providus_account', [TransactionController::class, 'checkUnusedProvidusAccounts']);
Route::get('/check_assigned_providus_accounts', [TransactionController::class, 'checkAssignedProvidusAccounts']);
Route::get('/matching_wb_account_details_with_user_table', [TransactionController::class, 'matchingWBAccountDetailsWithUserTable']);

Route::prefix('/ussd-apis')->group(function () {
    Route::post('/bank-transfer', [TransactionController::class, 'transferToBankAccountUSSD'])->middleware('usertransfer_throttle')->middleware('log.route');
    Route::post('/verify-bank-account', [TransactionController::class, 'verifyBankAccountFull'])->middleware('log.route');
});

Route::post('/verify-bank-account', [TransactionController::class, 'verifyBankAccount'])->name('v1-verify-bank-account');
Route::post('/verify-bank-account-test', [TransactionController::class, 'verifyBankAccountTest'])->name('v1-verify-bank-account-test');
Route::post('/verify-account-full', [TransactionController::class, 'verifyBankAccountFullname'])->name('v1-verify-account-full');
//Route::middleware('auth:api')->post('/verify-bvn', [TransactionController::class, 'verifyBVN'])->name('v1-verify-bvn');
Route::post('/verify-bvn', [TransactionController::class, 'verifyBVN'])->name('v1-verify-bvn');
Route::post('/verify-bvn/otp', [TransactionController::class, 'verifyBVNOtp'])->name('verify-bvn.otp');
Route::middleware('checkApiKey')->get('/verify-bvn/{bvn}', [TransactionController::class, 'verifyBVNAlt'])->name('verify-bvn-alt');
Route::get('/get-banks', [TransactionController::class, 'getAllBanks'])->name('v1-get-banks');

//saviour modified
Route::get('/get-meter-type', [TransactionController::class, 'getMeterType']);
Route::get('/get-meter-service', [TransactionController::class, 'getMeterService']);
Route::get('/get-meter-service-type', [TransactionController::class, 'getMeterServiceType']);
Route::get('/get-bets-type', [TransactionController::class, 'getBets']);

Route::get('/login-kuda', [KudaController::class, 'login']);
Route::post('/kuda-create-account', [KudaController::class, 'createAccount']);
Route::post('/kuda-webhook', [TransactionController::class, 'kudaWebhook'])->middleware('log.route');
Route::post('/kuda-maintenance-webhook', [TransactionController::class, 'kudaWebMaintenanceWebhook'])->middleware('log.route');

//Parkway APIs
//Route::post('/parkway-create-account', [ParkwayController::class, 'createVirtualAccount']);

//Route::middleware("restrictIP")->middleware('checkApiKey')->post('/create-account', [SpecialAPIController::class, 'createAccount']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/get-balance/{accountNumber}', [SpecialAPIController::class, 'getAccountBalance']);/
//Route::middleware("restrictIP")->post('/generate-key', [SpecialAPIController::class, 'generateKey']);
//Route::middleware("restrictIP")->post('/save-developer-settings', [SpecialAPIController::class, 'saveDeveloperSettings']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->post('/bank-transfer', [SpecialAPIController::class, 'transferToBankAccount']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/transaction/query/{reference}', [SpecialAPIController::class, 'queryTransaction']);
//Route::middleware("restrictIP")->middleware('checkCompanyCustomer')->get('/account-statement', [SpecialAPIController::class, 'getAccountStatement']);

Route::post('/transactions/admin-debit-transaction', [TransactionController::class, 'debitCustomeFromApi'])->name('admin-debit-transaction')->middleware('log.route');

Route::post('/transactions/flutterwave-webhook', [TransactionController::class, 'flutterwaveWebhook'])->name('flutterwave-webhook')->middleware('log.route');
Route::post('/transactions/providus-webhook', [TransactionController::class, 'providusWebhook'])->name('providus-webhook')->middleware('log.route');
Route::post('/transactions/gtb-webhook', [TransactionController::class, 'gtbWebhook'])->name('gtb-webhook')->middleware('log.route');
Route::post('/transactions/monnify-webhook', [TransactionController::class, 'monnifyWebhook'])->name('monnify-webhook')->middleware('log.route');
Route::post('/transactions/cyberpay-webhook', [TransactionController::class, 'cyberpayWebhook'])->name('cyberpay-webhook')->middleware('log.route');
Route::post('/transactions/vpay-webhook', [TransactionController::class, 'vpayWebhook'])->name('vpay-webhook')->middleware('log.route');
Route::post('/transactions/safehaven-webhook', [TransactionController::class, 'safeHavenWebhook'])->name('safehaven-webhook')->middleware('log.route');
Route::post('/transactions/sudo-webhook', [TransactionController::class, 'safeHavenSudoWebhook'])->name('sudo-safehaven-webhook')->middleware('log.route');
Route::post('/transactions/safehaven-card-maintenance-webhook', [TransactionController::class, 'safeHavenCardMaintenanceWebhook'])->middleware('log.route');
Route::post('/transactions/maplerad-webhook', [TransactionController::class, 'mapleradWebhook'])->name('sudo-safehaven-webhook')->middleware('log.route');

Route::post('/transactions/paystack-webhook', [TransactionController::class, 'paystackWebhook'])->name('paystack-webhook')->middleware('log.route');
Route::post('/transactions/vtpass-webhook', [TransactionController::class, 'vtpassWebhook'])->name('vtpass-webhook')->middleware('log.route');

Route::post('/transactions/4t5-gift-card', [TransactionController::class, 'postGiftTransaction'])->name('post-gift-card')->middleware('log.route');
Route::get('/transactions/giftcardsubcategories', [TransactionController::class, 'getGiftCardSubCategories'])->name('gift-card-subcategory')->middleware('log.route');

Route::post('/transactions/verify-cgate-details', [TransactionController::class, 'verifyCgateDetails'])->name('verify-cgate')->middleware('log.route');
Route::post('/transactions/cgate-webhook', [TransactionController::class, 'cgateWebhook'])->name('cgate-webhook')->middleware('log.route');
Route::post('/transactions/assetmatrix-webhook', [TransactionController::class, 'assetMatrixWebhook'])->name('assetmatrix-webhook')->middleware('log.route');
Route::post('/transactions/fairmoney-webhook', [TransactionController::class, 'fairMoneyWebhook'])->name('fairmoney-webhook')->middleware('log.route');
Route::post('/transactions/bani-webhook', [TransactionController::class, 'baniWebhook'])->name('bani-webhook')->middleware('log.route');

Route::post('/transactions/transfer-funds', [TransactionController::class, 'transferFunds'])->name('transfer-funds')->middleware('log.route');
Route::post('/transactions/assign-terminal', [TransactionController::class, 'assignTerminal'])->name('assign-terminal')->middleware('log.route');
Route::post('/transactions/register-customer', [TransactionController::class, 'registerCustomer'])->name('unassign-terminal')->middleware('log.route');
Route::post('/transactions/unassign-terminal', [TransactionController::class, 'unassignTerminal'])->name('unassign-terminal')->middleware('log.route');
Route::post('/transactions/amucha-webhook', [TransactionController::class, 'amuchaWebhook'])->name('amucha-webhook');
Route::post('/transactions/process-card-order', [TransactionController::class, 'processOrder'])->name('process-amucha-order')->middleware('log.route');
Route::post('/transactions/verify-card-otp', [TransactionController::class, 'verifyCardOtp'])->name('verify-amucha-cardpin')->middleware('log.route');
Route::post('/transactions/strowallet-webhook', [TransactionController::class, 'strowalletWebhook'])->name('amucha-webhook')->middleware('log.route');
Route::get('/transactions/verify-card-order-transaction/{transactionReference}', [TransactionController::class, 'verifyCardOrderTransaction'])->name('verify-card-order-transaction')->middleware('log.route');

Route::get('/companies/create-company-gtb-accounts', [CompanyController::class, 'createCompanyGtbAccounts'])->name('create-company-gtb-accounts')->middleware('log.route');
Route::get('/companies/create-company-bani-accounts', [CompanyController::class, 'createCompanyBaniAccounts'])->name('create-company-bani-accounts')->middleware('log.route');

Route::prefix('/transactions/errandpay')->group(function () {
    Route::post('services-webhook', [TransactionController::class, 'servicesWebhook'])->name('services-webhook')->middleware('log.route');
    Route::post('funds-transfer-webhook', [TransactionController::class, 'errandPayFundsTransferWebhook'])->name('errandpay-funds-transfer-webhook')->middleware('log.route');
    Route::post('bills-payment-webhook', [TransactionController::class, 'errandPayBillsPaymentWebhook'])->name('errandpay-bills-payment-webhook')->middleware('log.route');
    Route::post('check-balance', [TransactionController::class, 'errandPaycheckBalance'])->name('errandpay-check-balance')->middleware('log.route');
    Route::get('get-pos-terminals', [TransactionController::class, 'getPosTerminals'])->name('pos-terminals')->middleware('log.route')->middleware('auth:api');
    Route::post('transfer-pos-funds', [TransactionController::class, 'transferPosFunds'])->name('transfer-pos-funds')->middleware('log.route')->middleware('auth:api');
});

Route::post('/transaction/new-transaction', [TransactionController::class, 'newTransaction'])->name('new-transaction');
Route::post('/transaction/retry', [TransactionController::class, 'retryTransaction'])->name('retry-transaction');
Route::post('/transaction/card-balance', [TransactionController::class, 'cardBalance'])->name('card-balance');
Route::post('/transaction/network-management', [TransactionController::class, 'networkManagement'])->name('new-transaction');

Route::post('/transaction/pos-balance', [TransactionController::class, 'getTerminalBalance'])->name('pos-balance')->middleware('log.route');

Route::post('/transaction/get-exchange-rates', [TransactionController::class, 'getExchangeRates'])->name('get-exchange-rates');
Route::middleware("checkApiKey")->post('/users/restrict-account', [UserController::class, 'restrictUserAccount'])->name('restrict-user-account');
Route::middleware("checkApiKey")->post('/users/remove-account-restriction', [UserController::class, 'unrestrictUserAccount'])->name('remove-account-restriction');

Route::prefix('/accounts')->group(function () {

    //  Route::middleware("restrictIP")->post('/block', [NibbsController::class, 'blockUserAccount'])->name('block-user-account')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/unblock', [NibbsController::class, 'unblockUserAccount'])->name('unblock-user-account')->middleware('log.route');
    //   Route::middleware("restrictIP")->post('/credit-user', [NibbsController::class, 'creditUser'])->name('credit-customer')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/debit-user', [NibbsController::class, 'debitUser'])->name('debit-user')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/balance', [NibbsController::class, 'getAccountBalance'])->name('account-balance')->middleware('log.route');
    //  Route::middleware("restrictIP")->get('/lookup/{account_number}', [NibbsController::class, 'lookupAccount'])->name('look-account')->middleware('log.route');
    //   Route::middleware("restrictIP")->post('/amount/block', [NibbsController::class, 'lienAccount'])->name('lien-account')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/amount/unblock', [NibbsController::class, 'unlienAccount'])->name('unlien-account')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/advice', [NibbsController::class, 'accountMandate'])->name('account-manadate')->middleware('log.route');
    //  Route::middleware("restrictIP")->post('/advice/debit-user', [NibbsController::class, 'directDebitMandate'])->name('direct-debit-mandate')->middleware('log.route');
    //   Route::middleware("restrictIP")->post('/fi', [NibbsController::class, 'addNewBanks'])->name('add-new-banks')->middleware('log.route');
    //   Route::middleware("restrictIP")->post('/advice/credit-user', [NibbsController::class, 'creditUser'])->name('credit-user-advice')->middleware('log.route');
    //   Route::get('/transaction/{reference}', [SpecialAPIController::class, 'queryTransaction']);

    //  Route::middleware("restrictIP")->post('/funds-transfer-advice/credit-user', [NibbsController::class, 'reverseFundsTransferCredit'])->name('funds-transfer-reversal-credit-user');
    //  Route::middleware("restrictIP")->post('/funds-transfer-advice/debit-user', [NibbsController::class, 'reverseFundsTransferDebit'])->name('funds-transfer-reversal-debit-user');
});

Route::group(['middleware' => 'auth:api'], function () {

    Route::post('/updateFCM-token', [HomeController::class, 'updateToken']);
    Route::post('/meetinginfo', [MeetingController::class, 'meetingDetails']);

    Route::prefix('/ecommerce')->group(function () {
        //
        Route::get('/category', [OrderController::class, 'category']);
        Route::post('/items', [OrderController::class, 'index']);
        Route::post('/buy-item', [OrderController::class, 'makeOrder']);
        Route::get('/view-all-order', [OrderController::class, 'showOrdersAll']);
        Route::get('/view-pending-order', [OrderController::class, 'showOrderPending']);
        Route::post('/cancel-order', [OrderController::class, 'cancelOrders']);
    });
});
//Transactions
Route::prefix('/transactions')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::post('/verify-wallet-account', [TransactionController::class, 'verifyWalletAccount']);
        Route::post('/wallet-transfer', [TransactionController::class, 'walletTransfer'])->middleware('log.route');
        Route::get('/get-beneficiaries', [TransactionController::class, 'getBeneficiaries']);
        Route::get('/get-pos-balance', [TransactionController::class, 'getPOSBalance'])->name('pos-get-balance')->middleware('log.route');

        //Beeting endpoints
        Route::get('/get-betting-companies', [TransactionController::class, 'getBettingCompanies']);
        Route::post('/validate-bet-account', [TransactionController::class, 'validateBetAccount'])->middleware('log.route');
        Route::post('/topup-betting', [TransactionController::class, 'topupBetting'])->middleware('log.route');

        Route::middleware('checkAccountStatus')->post('/initiate-transaction', [TransactionController::class, 'initiateTransaction'])->middleware('log.route');
        Route::get('/history', [TransactionController::class, 'transactionHistory']);
        Route::post('/filter-history', [TransactionController::class, 'transactionHistoryMobile']);
        Route::get('/pos-history', [TransactionController::class, 'posTransactionHistory']);

        Route::middleware('checkAccountStatus')->post('/buy-airtime', [TransactionController::class, 'buyAirtime'])->middleware('log.route');
        //Route::post('/buy-airtime', [TransactionController::class, 'buyAirtime'])->middleware('log.route');

        Route::middleware('checkAccountStatus')->post('/buy-airtime-test', [TransactionController::class, 'buyAirtimeTest'])->middleware('log.route');

        Route::middleware('checkAccountStatus')->post('/buy-data-bundle', [TransactionController::class, 'buyDataBundle'])->middleware('log.route');
//        Route::post('/buy-data-bundle', [TransactionController::class, 'buyDataBundle'])->middleware('log.route');
        Route::post('/verify-meter-number', [TransactionController::class, 'verifyMeterNumber']);
        Route::middleware('checkAccountStatus')->post('/buy-electricity', [TransactionController::class, 'buyElectricity'])->middleware('log.route');
        Route::middleware('checkAccountStatus')->post('/subscribe-cable-tv', [TransactionController::class, 'subscribeCableTV'])->middleware('log.route');
        // Route::post('/buy-electricity', [TransactionController::class, 'buyElectricity'])->middleware('log.route');
        //Route::post('/subscribe-cable-tv', [TransactionController::class, 'subscribeCableTV'])->middleware('log.route');
        Route::get('/get-subscriptions', [TransactionController::class, 'getSubscriptions']);
        Route::post('/verify-smart-card', [TransactionController::class, 'verifySmartCard'])->middleware('log.route');
        Route::get('/get-data-bundles/{networkProvider}', [TransactionController::class, 'getDataBundles']);
        Route::get('/get-saving-products', [TransactionController::class, 'getSavingsProducts']);
        Route::middleware('checkAccountStatus')->post('/bank-transfer', [TransactionController::class, 'transferToBankAccount'])->middleware('log.route');
        Route::middleware('checkAccountStatus')->post('/bank-transfer-test', [TransactionController::class, 'transferToBankAccountTest'])->middleware('log.route');
        Route::middleware('checkAccountStatus')->post('/process-verify-reverse-transactions', [TransactionController::class, 'processVerifyAndReverseTransaction'])->middleware('log.route');
    });

    Route::middleware('checkApiKey')->get('/status/sh/{transactionReference}', [TransactionController::class, 'processSafeHavenVerifyAndReverseTransaction'])->middleware('log.route');
    Route::middleware('checkApiKey')->get('/list', [TransactionController::class, 'getAllTransactions']);
    Route::middleware('checkApiKey')->post('/user-transactions', [TransactionController::class, 'getUserTransactions'])->middleware('log.route');
    Route::middleware('checkApiKey')->get('/get-company-balance', [TransactionController::class, 'getCompanyBalance'])->middleware('log.route');
    Route::middleware('checkApiKey')->post('/company-bank-transfer', [TransactionController::class, 'companyBankTransfer'])->middleware('log.route');
    Route::middleware('checkApiKey')->get('/get-user-info/{accountNumber}', [UserController::class, 'getUserInfo']);

    Route::post('/credit-debit-customer-account', [TransactionController::class, 'creditDebitCustomerAccount'])->middleware('log.route');
    Route::post('/report-transaction', [TransactionController::class, 'reportTransaction']);

    Route::post('/set-spend-save', [TransactionController::class, 'setSpendSave'])->middleware('log.route');
    Route::post('/withdraw-spend-save', [TransactionController::class, 'withdrawSpendSave'])->middleware('log.route');
    Route::get('/get-setting-spend-save', [TransactionController::class, 'getSpendSave']);

    Route::get('/generate-user-token', [TransactionController::class, 'getUserToken'])->middleware('log.route');
    Route::get('/generate-user-schedule-token', [TransactionController::class, 'getUserTokenSchedule'])->middleware('log.route');

    Route::post('/create-schedule-transfer', [TransactionController::class, 'scheduleTransfer'])->middleware('log.route');
    Route::get('/user-schedule-transfer', [TransactionController::class, 'getscheduleTransfer'])->middleware('log.route');

    Route::post('/create-salary-schedule-transfer', [TransactionController::class, 'scheduleSalaryTransfer'])->middleware('log.route');
    Route::get('/salary-schedule-transfer', [TransactionController::class, 'getscheduleSalaryTransfer'])->middleware('log.route');

    Route::get('/user-get-cards', [TransactionController::class, 'getscheduleTransfer']);

    Route::get('/get-staff-payroll-detail', [SalaryController::class, ' getStaffPayrolDetails']);
    Route::get('/staff-payroll', [SalaryController::class, 'staffPayroll'])->middleware('log.route');
    Route::post('/setup-payroll', [SalaryController::class, 'ajax_setup_payroll'])->middleware('log.route');

});

Route::middleware('checkApiKey')->post('company/update-user-kyc-info', [TransactionController::class, 'updateKycInfo'])->middleware('log.route');

//Cards
Route::prefix('/physical-cards')->group(function () {
    Route::group(['middleware' => 'checkApiKey'], function () {

        Route::post('/get-user-info', [UserController::class, 'getUserInfo'])->middleware('log.route');
        Route::post('/get-user-card-balance', [UserController::class, 'getUserCardBalance'])->middleware('log.route');

        Route::get('/block-unblock', [CardController::class, 'blockUnblockCard'])->middleware('log.route');

        // Route::middleware('checkAccountStatus')->post('/create', [CardController::class, 'createUserCard'])->middleware('log.route');
        // Route::middleware('checkAccountStatus')->post('/fund', [CardController::class, 'fundVirtualCard'])->middleware('log.route');
        // Route::middleware('checkAccountStatus')->post('/withdraw', [CardController::class, 'withdrawVirtualCard'])->middleware('log.route');
        // Route::get('/get-balance', [CardController::class, 'getBalance'])->middleware('log.route');
        // Route::get('/get-card-details', [CardController::class, 'getCardDetails'])->middleware('log.route');
        // Route::get('/get-card', [CardController::class, 'getVirtualCard'])->middleware('log.route');
        // Route::get('/transactions', [CardController::class, 'transactionHistory']);
    });
});

//Cards
Route::prefix('/virtual-cards')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::get('/block-unblock', [CardController::class, 'blockUnblockCard'])->middleware('log.route');

        Route::middleware('checkAccountStatus')->post('/create', [CardController::class, 'createUserCard'])->middleware('log.route');
        Route::middleware('checkAccountStatus')->post('/changecardpin', [CardController::class, 'changeUserCardPin'])->middleware('log.route');

        Route::middleware('checkAccountStatus')->post('/fund', [CardController::class, 'fundVirtualCard'])->middleware('log.route');
        Route::middleware('checkAccountStatus')->post('/withdraw', [CardController::class, 'withdrawVirtualCard'])->middleware('log.route');
        Route::get('/get-balance', [CardController::class, 'getBalance'])->middleware('log.route');
        Route::get('/get-card-details', [CardController::class, 'getCardDetails'])->middleware('log.route');
        Route::post('/get-card-full-details', [CardController::class, 'getCardDetailswithPin'])->middleware('log.route');

        Route::get('/get-card', [CardController::class, 'getVirtualCard'])->middleware('log.route');
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
        Route::post('/pay', [LoanController::class, 'PaylLoan']);
        Route::get('/repayment/{id}', [LoanController::class, 'viewRepayment']);
        //viewRepayment
    });
});
Route::prefix('/investment')->group(function () {
    Route::group(['middleware' => 'auth:api'], function () {

        Route::get('/investment-variable', [InvestmentController::class, 'investmentVariable']);
        Route::get('/all-investment-variable', [InvestmentController::class, 'allInvestmentVariable']);
        Route::post('/process-investment', [InvestmentController::class, 'processInvestment']);
        Route::post('/send-process-investment', [InvestmentController::class, 'bookinvestment']);
        Route::get('/list-investment', [InvestmentController::class, 'viewInvestment']);
        Route::get('/list-all-investment', [InvestmentController::class, 'viewInvestmentUpdate']);
        Route::post('/terminate-investment/{id}', [InvestmentController::class, 'ajax_investmentstatus']);

    });
});
Route::group(['middleware' => 'auth:api'], function () {
    Route::get('/transaction/get-info/{id}', [TransactionController::class, 'txnInfo']);
    Route::get('/transaction/get-info-two/{id}', [TransactionController::class, 'txnInfoTwo']);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::prefix('/savings')->group(function () {
        Route::get('list/', [CustomerSavingController::class, 'show']);
        Route::post('store/', [CustomerSavingController::class, 'store']);
        Route::post('enable/{id}', [CustomerSavingController::class, 'enable']);
        Route::post('disable/{id}', [CustomerSavingController::class, 'cancel']);
        Route::get('list-payment/{id}', [CustomerSavingController::class, 'listSavingsPayments']);
        Route::get('/schedule-esusu', [CustomerSavingController::class, 'viewEsusuSchedule']);
        Route::post('/schedule-esusu', [CustomerSavingController::class, 'postEsusuSchedule']);
        Route::get('/schedule-esusu-product', [CustomerSavingController::class, 'viewEsusuScheduleProduct']);
        Route::post('/schedule-esusu-withdraw', [CustomerSavingController::class, 'cancelEsusuSchedule']);
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    // Route::post('/coinremitter/view-coin', [CoinremitterController::class, 'viewCoin']);
    // Route::put('/coinremitter/add-address', [CoinremitterController::class, 'get_new_address']);
    // Route::post('/coinremitter/validateAddress', [CoinremitterController::class, 'validateAddress']);
    // Route::post('/coinremitter/wallet-transfer', [CoinremitterController::class, 'walletTransfer']);
    // Route::post('/coinremitter/withdraw', [CoinremitterController::class, 'withdraw']);
    // Route::post('/coinremitter/create-invoice', [CoinremitterController::class, 'createInvoice']);
    // Route::get('/coinremitter/my-crypto-wallet', [CoinremitterController::class, 'myCrypto']);
    // Route::post('/coinremitter/my-crypto-transaction', [CoinremitterController::class, 'transactionHistory']);
    // Route::post('/coinremitter/transfer-history', [CoinremitterController::class, 'getTransferHistory']);
    // Route::get('/coinremitter/get-coin-rate', [CoinremitterController::class, 'get_coin_rate']);
    // Route::get('/coinremitter/get-coin-rate2', [CoinremitterController::class, 'get_coin_rate_OBJ']);
    //transactionHistory
});

Route::group(['middleware' => 'auth:api'], function () {

    Route::prefix('/transactions/africas')->group(function () {
        Route::get('/currencies', [InternationalTransaction::class, 'currencies']);
        Route::post('/conversion', [InternationalTransaction::class, 'conversion']);
        Route::post('/recieve-funds', [InternationalTransaction::class, 'receiveFund']);
        Route::post('/transfer-funds', [InternationalTransaction::class, 'transferFunds']);
        Route::get('/list-memos', [InternationalTransaction::class, 'listMemo']);
        Route::post('/internation-transfer', [InternationalTransaction::class, 'saveInternationTransferRequest']);
        Route::get('/get-product', [InternationalTransaction::class, 'get_currency']);
        Route::post('/add-currency', [InternationalTransaction::class, 'add_account']);
    });
});

Route::group(['middleware' => 'auth:api'], function () {

    Route::prefix('/admin')->group(function () {
        Route::get('/customers', [UserController::class, 'getCustomers']);
        Route::post('/transaction/verify-bvn', [App\Http\Controllers\TransactionController::class, 'verifyBVN'])->middleware('log.route');
        Route::post('/create-account-options', [App\Http\Controllers\CustomerController::class, 'createCustomerAccountOptions']);
        Route::post('/transfer-funds', [InternationalTransaction::class, 'transferFunds']);
        Route::get('/list-memos', [InternationalTransaction::class, 'listMemo']);
        Route::post('/internation-transfer', [InternationalTransaction::class, 'saveInternationTransferRequest']);
        Route::get('/get-product', [InternationalTransaction::class, 'get_currency']);
        Route::post('/add-currency', [InternationalTransaction::class, 'add_account']);
        Route::post('/generate_end_of_day', [TransactionController::class, 'generate_end_of_day'])->middleware('log.route');

    });
});
//

Route::post('/test/gtb/create-virtual-account', [ GTBController::class, 'testCreateVirtualAccount' ]);
Route::post('/test/kuda/create-virtual-account', [KudaController::class, 'testCreateAccount']);

Route::prefix('/target-savings')->middleware('auth:api')->group(function () {
    Route::post('/create', [TargetSavingsController::class, 'store']);
    Route::get('/list', [TargetSavingsController::class, 'index']);
    Route::get('/summary', [TargetSavingsController::class, 'summary']);
    Route::get('/details/{id}', [TargetSavingsController::class, 'show']);
    Route::post('/withdraw/{id}', [TargetSavingsController::class, 'withdraw']);
    Route::get('/transactions/{id}', [TargetSavingsController::class, 'transactions']);

    Route::get('/company/list', [TargetSavingsController::class, 'companyIndex']);
    Route::get('/company/settings', [TargetSavingsController::class, 'companySettings']);
    Route::put('/company/settings', [TargetSavingsController::class, 'updateCompanySettings']);
});

Route::prefix('/fixed-savings')->middleware('auth:api')->group(function () {

    Route::post('/create', [FixedSavingsController::class, 'store']);

    Route::get('/list', [FixedSavingsController::class, 'index']);

    Route::get('/summary', [FixedSavingsController::class, 'summary']);

    Route::get('/details/{id}', [FixedSavingsController::class, 'show']);

    Route::post('/deposit/{id}', [FixedSavingsController::class, 'deposit']);

    Route::post('/withdraw/{id}', [FixedSavingsController::class, 'withdraw']);

    Route::get('/transactions/{id}', [FixedSavingsController::class, 'transactions']);

    Route::get(
    '/company/list',
    [FixedSavingsController::class, 'companyIndex']
);

Route::get(
    '/company/settings',
    [FixedSavingsController::class, 'companySettings']
);

Route::put(
    '/company/settings',
    [FixedSavingsController::class, 'updateCompanySettings']
);

});

Route::prefix('campaigns')->group(function () {

    /*
     * Customer Campaign endpoints.
     */
    Route::post('/create', [
        CampaignController::class,
        'store'
    ]);

    Route::get('/list', [
        CampaignController::class,
        'index'
    ]);

    Route::get('/details/{id}', [
        CampaignController::class,
        'show'
    ]);

    Route::post('/deposit/{id}', [
        CampaignController::class,
        'deposit'
    ]);

    Route::post('/withdraw/{id}', [
        CampaignController::class,
        'withdraw'
    ]);

    Route::put('/{id}/target', [
        CampaignController::class,
        'updateTarget'
    ]);

    Route::post('/{id}/close', [
        CampaignController::class,
        'close'
    ]);

    Route::get('/transactions/{id}', [
        CampaignController::class,
        'transactions'
    ]);

    /*
     * Administrator endpoints.
     */
    Route::get('/company/list', [
        CampaignController::class,
        'companyIndex'
    ]);

    Route::get('/company/settings', [
        CampaignController::class,
        'companySettings'
    ]);

    Route::put('/company/settings', [
        CampaignController::class,
        'updateCompanySettings'
    ]);

    Route::post('/company/{id}/close', [
        CampaignController::class,
        'adminClose'
    ]);

    Route::get(
    '/company/summary',
    [CampaignController::class, 'companySummary']
   );

   Route::get(
    '/admin/dashboard',
    [CampaignController::class, 'adminDashboard']
);
});


    Route::get('/ajo/mine', [AjoController::class, 'mine'])->name('ajo.mine');
    Route::get('/ajo/pending-actions', [AjoController::class, 'pendingActions'])->name('ajo.pending-actions');
    Route::get('/ajo/account', [AjoController::class, 'account'])->name('ajo.account');
    Route::post('/ajo/account/deposit', [AjoController::class, 'depositToAccount'])->name('ajo.account.deposit');
    Route::post('/ajo/account/withdraw', [AjoController::class, 'withdrawFromAccount'])->name('ajo.account.withdraw');
    Route::post('/ajo/create', [AjoController::class, 'create'])->name('ajo.create');
    Route::post('/ajo/{ajoId}/invite', [AjoController::class, 'invite'])->whereNumber('ajoId')->name('ajo.invite');
    Route::post('/ajo/{ajoId}/join', [AjoController::class, 'join'])->whereNumber('ajoId')->name('ajo.join');
    Route::post('/ajo/invitations/{invitationId}/accept', [AjoController::class, 'acceptInvitation'])->whereNumber('invitationId')->name('ajo.invitation.accept');
    Route::post('/ajo/invitations/{invitationId}/decline', [AjoController::class, 'declineInvitation'])->whereNumber('invitationId')->name('ajo.invitation.decline');
    Route::post('/ajo/{ajoId}/leave/request', [AjoController::class, 'requestEarlyLeave'])->whereNumber('ajoId')->name('ajo.leave.request');
    Route::post('/ajo/members/{ajoMemberId}/leave/approve', [AjoController::class, 'approveEarlyLeave'])->whereNumber('ajoMemberId')->name('ajo.leave.approve');
    Route::post('/ajo/members/{ajoMemberId}/leave/reject', [AjoController::class, 'rejectEarlyLeave'])->whereNumber('ajoMemberId')->name('ajo.leave.reject');
    Route::get('/ajo/{ajoId}/conditions', [AjoController::class, 'conditions'])->whereNumber('ajoId')->name('ajo.conditions');
    Route::post('/ajo/{ajoId}/fee-split', [AjoController::class, 'feeSplit'])->whereNumber('ajoId')->name('ajo.fee-split');
    Route::post('/ajo/system/process-collections', [AjoController::class, 'processCollections'])->name('ajo.system.process-collections');
    Route::post('/ajo/system/process-maturity', [AjoController::class, 'processMaturity'])->name('ajo.system.process-maturity');
    Route::get('/ajo/invitations/pending',[AjoController::class, 'pendingInvitations'])->name('ajo.invitations.pending');
    Route::get('/ajo/settings/creation-fee', [AjoController::class, 'creationFee']);
    Route::put('/ajo/settings/creation-fee', [AjoController::class, 'updateCreationFee']);
    Route::get('/ajo/{ajoId}/members', [AjoController::class, 'members'])->whereNumber('ajoId');
    Route::get('/ajo/my-memberships', [AjoController::class, 'myMemberships']);

Route::prefix('9psb/sandbox')->group(function () {

    // Create virtual account
    Route::post(
        '/virtual-account',
        [NinePSBSandboxController::class, 'createVirtualAccount']
    );

    // Verify bank account
    Route::post(
        '/account/verify',
        [NinePSBSandboxController::class, 'verifyBankAccount']
    );

    // Bank transfer
    Route::post(
        '/transfer',
        [NinePSBSandboxController::class, 'bankTransfer']
    );

    // Verify transaction
    Route::post(
        '/transaction/verify',
        [NinePSBSandboxController::class, 'verifyTransaction']
    );

    // Verify transaction reversal
    Route::post(
        '/transaction/reversal',
        [NinePSBSandboxController::class, 'verifyTransactionReversal']
    );
});