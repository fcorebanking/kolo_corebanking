<?php
namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdParty\AmuchaController;
use App\Http\Controllers\ThirdParty\AssetMatrixController;
use App\Http\Controllers\ThirdParty\BaniController;
use App\Http\Controllers\ThirdParty\BridgeController;
use App\Http\Controllers\ThirdParty\CgateController;
use App\Http\Controllers\ThirdParty\CyberpayControllelr;
use App\Http\Controllers\ThirdParty\FairMoneyController;
use App\Http\Controllers\ThirdParty\FincraController;
use App\Http\Controllers\ThirdParty\GTBController;
use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\KudaBusinessController;
use App\Http\Controllers\ThirdParty\KudaController;
use App\Http\Controllers\ThirdParty\MapleradController;
use App\Http\Controllers\ThirdParty\MonnifyController;
use App\Http\Controllers\ThirdParty\NeonDataApiController;
use App\Http\Controllers\ThirdParty\NinePSBController;
use App\Http\Controllers\ThirdParty\ProvidusController;
use App\Http\Controllers\ThirdParty\SafehavenController;
use App\Http\Controllers\ThirdParty\StroWalletController;
use App\Http\Controllers\ThirdParty\TryluxController;
use App\Http\Controllers\ThirdParty\Trylux\TryluxPayload;
use App\Http\Controllers\ThirdParty\TwilioWhatsapp;
use App\Http\Controllers\ThirdParty\VPayController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Http\Controllers\ThirdParty\WovenController;
use App\Http\Resources\BenefeciariesResource;
use App\Mail\TransactionReport;
use App\Models\AgentTerminal;
use App\Models\ApiKey;
use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\CardRequestLog;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\CustomerSalary;
use App\Models\GtbWebhook;
use App\Models\loan;
use App\Models\Mandate;
use App\Models\MandateLog;
use App\Models\PreferredEbankingPartner;
use App\Models\PreferredEbankingPartnerCompany;
use App\Models\savings_product;
use App\Models\ScheduledPayment;
use App\Models\SpendSave;
use App\Models\transactions;
use App\Models\transaction_remarks;
use App\Models\UnusedProvidusAccounts;
use App\Models\User;
use App\Models\UserCard;
use App\Models\user_wallet;
use App\Models\WbaCharge;
use App\Models\wb_account_details;
use App\Models\WebhookLog;
use App\Models\WovenWebhook;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingCompanyService;
use App\Services\ebanking\EbankingService;
use App\Services\fortifyTransactionService;
use App\Services\PaystackService;
use App\Services\RemittaController;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{

    private $flutterwaveSecretKey;
    private $baseUrl;

    public function __construct()
    {

        $this->baseUrl = url('/');
    }

#[OA\Post(
    path: "/verify-bank-account",
    summary: "Verify a bank account",
    description: "Verifies a destination bank account and returns the account holder's name and bank name.",
    tags: ["Transfer - Wallet To Bank"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["bank_code", "account_number"],
            properties: [
                new OA\Property(property: "bank_code", type: "string", example: "000001"),
                new OA\Property(property: "account_number", type: "string", example: "0123456789")
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Account verified successfully",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Account Verified Successfully"),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(property: "first_name", type: "string", example: "Ada"),
                            new OA\Property(property: "last_name", type: "string", example: "Example"),
                            new OA\Property(property: "bank_name", type: "string", example: "Example Bank")
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Bank account verification failed",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Unable to verify bank account")
                ]
            )
        ),
        new OA\Response(
            response: 406,
            description: "Invalid request parameters",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Bank code and account number is required")
                ]
            )
        )
    ]
)]
public function verifyBankAccount(Request $request)
{
    logInfo($request->all(), "Verify Bank Account Request");

    $validation = Validator::make($request->all(), [
        "bank_code" => 'required',
        "account_number" => 'required|numeric|gt:0',
    ]);

    if ($validation->fails()) {
        $ra = [
            "status" => false,
            "message" => "Bank code and account number is required"
        ];

        return response()->json($ra, 406);
    }

    $preferredEbankingPartner = PreferredEbankingPartner::all();

    $ebankingService = new EbankingService($preferredEbankingPartner);

    $bankAccount = $ebankingService->verifyAccountNumberFull(
        $request->bank_code,
        $request->account_number
    );

    if (! $bankAccount['status']) {
        return response()->json($bankAccount, 400);
    }

    $bank_name = "";

    if (isset($bankAccount["data"]["bank_code"])) {
        $bank = banks::where(
            'bank_code',
            $bankAccount["data"]["bank_code"]
        )->first();

        $bank_name = $bank->bank_name;
    } else {
        $bank_name = $bankAccount['data']['bank_name'];
    }

    $accountName = $bankAccount["data"]["account_name"];

    $myAccountName = explode(" ", $accountName);

    $firstName = strlen($myAccountName[0]) < 3
        ? $myAccountName[1]
        : $myAccountName[0];

    $lastName = count($myAccountName) > 2
        ? (strlen($myAccountName[2]) < 3
            ? $myAccountName[1]
            : $myAccountName[2])
        : (count($myAccountName) > 1
            ? $myAccountName[1]
            : "");

    return response()->json([
        'status' => true,
        'message' => "Account Verified Successfully",
        'data' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'bank_name' => $bank_name,
        ],
    ]);
}

    public function verifyBankAccountTest(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bank_code"      => 'required',
            "account_number" => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Bank code and account number is required"];
            return response()->json($ra, 406);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        \logInfo(json_encode($preferredEbankingPartner), "preferredEbankingPartner");

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        \logInfo(json_encode($bankAccount), "bankAccount");

        if (! $bankAccount['status']) {
            return response()->json($bankAccount, 400);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank      = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bank_name = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $accountName = $bankAccount["data"]["account_name"];

        $myAccountName = [];

        $myAccountName = explode(" ", $accountName);

        $firstName = strlen($myAccountName[0]) < 3 ? $myAccountName[1] : $myAccountName[0];
        $lastName  = count($myAccountName) > 2 ? (strlen($myAccountName[2]) < 3 ? $myAccountName[1] : $myAccountName[2]) : (count($myAccountName) > 1 ? $myAccountName[1] : "");

        return response()->json(
            [
                'status'  => true,
                'message' => "Account Verified Successfully",
                'data'    => [
                    'first_name' => $firstName,
                    'last_name'  => $lastName,
                    'bank_name'  => $bank_name,

                ],
            ]
        );
    }

    public function verifyBankAccountFull(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                "bank_code"      => 'required',
                "account_number" => 'required|numeric|gt:0',
                "ApiKey"         => 'required',
            ]);
            $header = $request->header('ApiKey');
            if ($validation->fails()) {
                $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
                return response()->json($ra, 406);
            }
            if ($request->ApiKey != "enter key") {
                $ra = ["status" => false, "message" => "Invalid API key provided"];
                return response()->json($ra, 400);
            }

            $bank = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();

            if (! $bank) {
                return response()->json(["status" => 0, 'message' => "Invalid bank code"], 400);
            }

            $preferredEbankingPartner = PreferredEbankingPartner::all();

            $ebankingService                 = new EbankingService($preferredEbankingPartner);
            $bankTransferType                = new BankTransferType();
            $bankTransferType->bankCode      = $request->bank_code;
            $bankTransferType->shortBankCode = $bank->short_code;

            return $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function getCompanyBalance(Request $request)
    {

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $companyWallet = company_wallet::where('company_id', $checkApiKey->company_id)->first();

        if ($companyWallet) {
            return response()->json(['status' => true, 'message' => 'Company Balance fetched successfully', 'data' => ['balance' => $companyWallet->balance]], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to get Company balance'], 400);
        }
    }

public function creditDebitCustomerAccount(Request $request)
{
     $validation = Validator::make($request->all(), [
        "type"           => 'required|in:credit,debit',
        "account_number" => 'required|numeric|gt:0',
        "amount"         => 'required|numeric|gt:0',
        "description"    => 'required|string',
    ]);

    if ($validation->fails()) {
        return response()->json([
            "status"  => false,
            "message" => $validation->errors()->all()[0]
        ], 406);
    }

    // Get the authenticated API user
    $authUser = auth('api')->user();

    if (! $authUser) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated'
        ], 401);
    }

    // Get the company belonging to the authenticated user
    $companyId = $authUser->company_id;

    // Get the company's wallet
    $companyWallet = company_wallet::where(
        'company_id',
        $companyId
    )->first();

    if (! $companyWallet) {
        return response()->json([
            'status'  => false,
            'message' => 'Company wallet not found'
        ], 400);
    }

    // Find customer account
    $userAccount = wb_account_details::where(
        'virtual_accnum',
        $request->account_number
    )->first();

    if (! $userAccount) {
        return response()->json([
            'status'  => false,
            'message' => 'Invalid account number'
        ], 400);
    }

    $erroMessage = $request->type == "debit"
        ? "Cannot Debit this customer account"
        : "Cannot Credit this customer account";

    // Make sure the customer belongs to the authenticated user's company
    if ($companyId != $userAccount->user->company_id) {
        return response()->json([
            'status'  => false,
            'message' => $erroMessage
        ], 400);
    }

    // Credit or debit customer account
    $customerBalance = $request->type == "debit"
        ? debitCustomerOnly(
            $userAccount->id,
            $request->amount,
            $userAccount->user
        )
        : creditCustomerOnly(
            $userAccount->id,
            $request->amount
        );

    if (! $customerBalance['status']) {
        return response()->json(
            $customerBalance,
            400
        );
    }

    // Transaction type
    $transactionType = $request->type == 'credit'
        ? 109
        : 110;

    // Generate transaction reference
    $transactionId = generate_txnID();

    // Create transaction record
    $transaction = transactions::create([
        "branch_id"              => $userAccount->user->branch_id,
        "account_id"             => $userAccount->id,
        "description"            => $request->description,
        "balance_before"         => $customerBalance['balance_before'],
        "balance_after"          => $customerBalance['balance_after'],
        "amount"                 => $request->amount,
        "transaction_reference"  => $transactionId,
        "transaction_remarks_id" => 2,
        "transaction_type_id"    => $transactionType,
        "credit_debit_type_id"   => $request->type == "debit" ? 2 : 1,
        "created_date"            => date('Y-m-d'),
        "created_by"              => $userAccount->user_id,
    ]);

    $message = $request->type == "debit"
        ? "Customer Debited Successfully"
        : "Customer Credited Successfully";

    return response()->json([
        'status'  => true,
        'message' => $message,
        'data'    => [
            'balance'        => $customerBalance['balance_after'],
            'transaction_id' => $transactionId,
        ],
    ]);
}


public function companyBankTransfer(Request $request)
{
    $validation = Validator::make($request->all(), [
        'amount' => 'required|numeric|gt:0',
        'destination_account' => 'required|string',
        'bank_code' => 'required|string',
        'transaction_reference' => 'sometimes|string|max:100',
        'description' => 'nullable|string|max:255',
    ]);

    if ($validation->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validation->errors()->first(),
        ], 422);
    }

    $header = $request->header('ApiKey');

    if (!$header) {
        return response()->json([
            'status' => false,
            'message' => 'API key is required.',
        ], 401);
    }

    $apiKey = ApiKey::where('api_key', $header)->first();

    if (!$apiKey) {
        return response()->json([
            'status' => false,
            'message' => 'Invalid API key.',
        ], 401);
    }

    $company = Company::with('currency')->find($apiKey->company_id);

    if (!$company || !$company->currency) {
        return response()->json([
            'status' => false,
            'message' => 'Company or company currency configuration was not found.',
        ], 400);
    }

    $adminUser = User::where('company_id', $company->id)->first();

    if (!$adminUser || !$adminUser->company) {
        return response()->json([
            'status' => false,
            'message' => 'A valid company user account was not found.',
        ], 400);
    }

    $amount = (float) $request->amount;

    $chargeAmount = 0;

    if ((int) $company->currency->id === 1) {
        $chargeAmount = (float) getWbaTransferCharges(1, $amount);

        // Preserve your existing charge rule.
        if ($amount > 1000000) {
            $chargeAmount = 100;
        }
    }

    $transactionReference = $request->filled('transaction_reference')
        ? (string) $request->transaction_reference
        : generateTransactionReference();

    $providerSucceeded = false;

    try {
        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(
            $company->id
        );

        if (($checkAllowedTransaction['status'] ?? null) != 1) {
            $checkAllowedTransaction['status'] = false;

            return response()->json($checkAllowedTransaction, 400);
        }

        // Require ledger configuration before sending money externally.
        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(8, $company->id);

        if (!$ledgerSettings) {
            return response()->json([
                'status' => false,
                'message' => 'Company bank transfer ledger settings are not configured.',
            ], 422);
        }

        $ledgerCr = $ledgerSettings->ledger_cr;
        $ledgerDr = $ledgerSettings->ledger_dr;

        $bank = banks::where('bank_code', $request->bank_code)
            ->orWhere('short_code', $request->bank_code)
            ->first();

        if (!$bank) {
            return response()->json([
                'status' => false,
                'message' => 'The destination bank could not be identified.',
            ], 400);
        }

        // Preserve your existing default-provider selection.
        $preferredEbankingPartner = PreferredEbankingPartner::all();
        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull(
            $request->bank_code,
            $request->destination_account
        );

        if (!is_array($bankAccount) || !($bankAccount['status'] ?? false)) {
            return response()->json(
                is_array($bankAccount)
                    ? $bankAccount
                    : [
                        'status' => false,
                        'message' => 'Bank account verification returned an invalid response.',
                    ],
                400
            );
        }

        /*
         * Create a pending record first. Do not debit the company here:
         * an external transfer failure must not leave the company charged.
         */
        $transaction = DB::transaction(function () use (
            $transactionReference,
            $company,
            $adminUser,
            $amount,
            $chargeAmount,
            $ledgerCr,
            $ledgerDr
        ) {
            $existingTransaction = transactions::where(
                'transaction_reference',
                $transactionReference
            )
                ->lockForUpdate()
                ->first();

            if ($existingTransaction) {
                throw new \RuntimeException(
                    'Transaction reference already exists.'
                );
            }

            return transactions::create([
                'balance_before' => 0,
                'amount' => $amount,
                'balance_after' => 0,
                'description' => $adminUser->first_name . ' '
                    . $adminUser->last_name . ' / '
                    . $company->company_name,
                'branch_id' => $adminUser->branch_id,
                'company_id' => $company->id,
                'company_balance_before' => 0,
                'company_balance_after' => 0,
                'company_amount' => $amount + $chargeAmount,
                'transaction_reference' => $transactionReference,
                'transaction_remarks_id' => 1,
                'transaction_type_id' => 8,
                'credit_debit_type_id' => 2,
                'created_date' => date('Y-m-d'),
                'created_by' => $adminUser->id,
                'ledger_cr' => $ledgerCr,
                'ledger_dr' => $ledgerDr,
            ]);
        });

        $bankTransferType = new BankTransferType();
        $bankTransferType->bankCode = $request->bank_code;
        $bankTransferType->shortBankCode = $bank->short_code;
        $bankTransferType->accountNumber = $request->destination_account;
        $bankTransferType->description = $request->input('description', 'BM');
        $bankTransferType->amount = $amount;
        $bankTransferType->transactionReference =
            $transaction->transaction_reference;

        try {
            $bankTransfer = $ebankingService->transferToBankAccount(
                $bankTransferType
            );
        } catch (\Throwable $providerException) {
            logInfo([
                'transaction_reference' => $transactionReference,
                'company_id' => $company->id,
                'exception_class' => get_class($providerException),
                'exception_message' => $providerException->getMessage(),
            ], 'COMPANY BANK TRANSFER PROVIDER EXCEPTION - RECONCILIATION REQUIRED');

            // Provider outcome is unknown. Keep the transaction pending.
            return response()->json([
                'status' => false,
                'message' => 'Transfer status could not be confirmed. Contact support before retrying.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        logInfo([
            'company_id' => $company->id,
            'transaction_reference' => $transactionReference,
            'provider_status' => is_array($bankTransfer)
                ? ($bankTransfer['status'] ?? null)
                : null,
        ], 'COMPANY TRANSFER PROVIDER RESPONSE');

        if (
            !is_array($bankTransfer) ||
            !array_key_exists('status', $bankTransfer)
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Transfer status could not be confirmed. Contact support before retrying.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        if ((string) $bankTransfer['status'] !== '00') {
            /*
             * Only mark definitive provider rejections as failed.
             * Confirm these codes against your actual provider contract.
             */
            if (in_array((string) $bankTransfer['status'], ['01', '02', '03'], true)) {
                DB::transaction(function () use (
                    $transactionReference,
                    $company
                ) {
                    $lockedTransaction = transactions::where(
                        'transaction_reference',
                        $transactionReference
                    )
                        ->where('company_id', $company->id)
                        ->lockForUpdate()
                        ->first();

                    if (
                        $lockedTransaction &&
                        (int) $lockedTransaction->transaction_remarks_id === 1
                    ) {
                        $lockedTransaction->transaction_remarks_id = 3;
                        $lockedTransaction->save();
                    }
                });

                $bankTransfer['status'] = false;

                return response()->json($bankTransfer, 400);
            }

            // Unknown/ambiguous provider status: leave pending for reconciliation.
            return response()->json([
                'status' => false,
                'message' => 'Transfer status could not be confirmed. Contact support before retrying.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        $providerSucceeded = true;

        /*
         * Provider confirmed success. Debit the company and finalize the
         * transaction together. If the debit fails, keep it pending.
         */
        try {
            $companyDebit = DB::transaction(function () use (
    $transactionReference,
    $company,
    $adminUser,
    $amount,
    $chargeAmount,
    $bankAccount,
    $bank,
    $request
) {
                $lockedTransaction = transactions::where(
                    'transaction_reference',
                    $transactionReference
                )
                    ->where('company_id', $company->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedTransaction) {
                    throw new \RuntimeException(
                        'Transaction record missing during finalization.'
                    );
                }

                if ((int) $lockedTransaction->transaction_remarks_id === 2) {
                    throw new \RuntimeException(
                        'Transaction was already finalized.'
                    );
                }

                if ((int) $lockedTransaction->transaction_remarks_id !== 1) {
                    throw new \RuntimeException(
                        'Transaction is not in the expected pending state.'
                    );
                }

                $debitResult = debitCompanyWithCharge(
                    $company->id,
                    $amount,
                    $chargeAmount,
                    $adminUser
                );

                if (
                    !is_array($debitResult) ||
                    !($debitResult['status'] ?? false)
                ) {
                    logInfo([
                        'transaction_reference' => $transactionReference,
                        'company_id' => $company->id,
                        'debit_result' => is_array($debitResult)
                            ? $debitResult
                            : gettype($debitResult),
                    ], 'CRITICAL: COMPANY PROVIDER TRANSFER SUCCEEDED BUT DEBIT FAILED');

                    throw new \RuntimeException(
                        'Company debit failed after provider success.'
                    );
                }

                $accountName = $bankAccount['data']['account_name']
                    ?? $request->input('destination_account');

                $lockedTransaction->description =
                    $adminUser->first_name . ' '
                    . $adminUser->last_name . ' / '
                    . $company->company_name . ' / '
                    . $accountName . '/'
                    . $request->destination_account . '/'
                    . $bank->bank_name;

                $lockedTransaction->company_balance_before =
                    $debitResult['balance_before'];

                $lockedTransaction->company_balance_after =
                    $debitResult['charge_balance_after'];

                $lockedTransaction->company_amount = $amount + $chargeAmount;
                $lockedTransaction->transaction_remarks_id = 2;
                $lockedTransaction->save();

                return $debitResult;
            });
        } catch (\Throwable $finalizationException) {
            logInfo([
                'transaction_reference' => $transactionReference,
                'company_id' => $company->id,
                'provider_succeeded' => $providerSucceeded,
                'exception_class' => get_class($finalizationException),
                'exception_message' => $finalizationException->getMessage(),
            ], 'CRITICAL: COMPANY TRANSFER REQUIRES RECONCILIATION');

            return response()->json([
                'status' => false,
                'message' => 'The bank transfer succeeded but the company debit requires reconciliation. Contact support. Do not retry this reference.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Company bank transfer successful.',
            'data' => [
                'balance_before' => $companyDebit['balance_before'],
                'balance' => $companyDebit['charge_balance_after'],
                'balance_after' => $companyDebit['charge_balance_after'],
                'transaction_reference' => $transactionReference,
            ],
        ]);
    } catch (\Throwable $e) {
        logInfo([
            'transaction_reference' => $transactionReference,
            'company_id' => $company->id ?? null,
            'provider_succeeded' => $providerSucceeded,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
        ], 'COMPANY BANK TRANSFER CONTROLLER EXCEPTION');

        if ($e instanceof \RuntimeException &&
            str_contains($e->getMessage(), 'Transaction reference already exists')) {
            return response()->json([
                'status' => false,
                'message' => 'Transaction reference already exists.',
            ], 409);
        }

        if ($providerSucceeded) {
            return response()->json([
                'status' => false,
                'message' => 'The bank transfer succeeded but local processing requires reconciliation. Contact support. Do not retry this reference.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unable to process the transfer. Please contact support.',
            'transaction_reference' => $transactionReference,
        ], 500);
    }
}


    public function updateKycInfo(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "account_number" => "required",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $company = Company::where('id', $checkApiKey->company_id)->first();

        $userAccount = wb_account_details::where('virtual_accnum', $request->account_number)->first();

        if (! $userAccount) {
            return response()->json(['status' => false, 'message' => 'User account not found'], 400);
        }

        if ($userAccount->user->company_id != $company->id) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to perform this action'], 400);
        }

        $mainUser = $userAccount->user;

        if ($request->has('bvn')) {
            $mainUser->bvn = $request->bvn;
        }

        if ($request->has('kyc_level')) {
            $mainUser->kyc_level_id = $request->kyc_level;
        }

        if ($request->has('nin')) {
            $mainUser->nin = $request->nin;
        }

        $mainUser->save();

        return response()->json(["status" => true, 'message' => "KYC Information updated successfullly"]);
    }

    public function processSafeHavenVerifyAndReverseTransaction(Request $request, $transactionReference)
    {

        if (! $transactionReference) {
            return response()->json(['status' => 0, 'message' => "Transaction reference is required"]);
        }

        $transaction = transactions::where('transaction_reference', $transactionReference)->first();

        if (! $transaction) {
            return response()->json(['status' => 0, 'message' => "Invalid Transaction reference"]);
        }

        if ($transaction->transaction_type_id != 8) {
            return response()->json(['status' => 0, 'message' => "This transaction is not a bank transfer"]);
        }

        $startingDate    = Carbon::parse('2026-04-15 00:00:00');
        $transactionDate = Carbon::parse($transaction->created_at);

        if ($startingDate->gt($transactionDate)) {
            return response()->json(['status' => 0, 'message' => "This transaction cannot be reversed"]);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => 0, 'message' => "Transaction has already been reversed"]);
        }

        $transactions = ['BM2025122821591234654808724CITYAFRICA', 'BM2025123012292382492184995', 'BM2025123012375424851763987', 'BM2025123012394923398215462'];

        $reverseTransactionsMessages = reverseTransactionsMessages($request->transaction_reference);

        if ($transaction->transaction_remarks_id == 1 || $transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);
        }

        if ($transaction->created_at > Carbon::now()->subMinutes(1)) {
            return response()->json(['status' => 0, 'message' => "Transaction must be at least 1 minute before the transaction status can be confirmed"]);
        }

        $affliateCustomerCharge = 0;
        $affliateCompanyCharge  = 0;
        $whoToCredit            = $transaction->account_id == null ? 'company' : 'both';

        $childTransaction = transactions::where('parent_transaction_id', $transaction->id)->first();

        if ($childTransaction) {
            $affliateCustomerCharge = $childTransaction->amount;
            $affliateCompanyCharge  = $childTransaction->company_amount;
        }

        $kudaController = new KudaController();

        $partnerType = "others";

        $verifyTransaction = $kudaController->verifyTransaction($transactionReference);

        if ($verifyTransaction['status'] == "03") {
            $partnerType            = "kuda_business";
            $kudaBusinessController = new KudaBusinessController();
            $verifyTransaction      = $kudaBusinessController->verifyTransaction($transactionReference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType         = "safehaven";
            $safeHavenController = new SafehavenController();
            $verifyTransaction   = $safeHavenController->getTransactionStatus($transactionReference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType = "monnify";

            $monnifyController = new MonnifyController();
            $verifyTransaction = $monnifyController->getTransactionStatus($transactionReference);
        }

        if ($verifyTransaction['status'] == "00") {

            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            $verifyTransaction['status'] = 1;

            return response()->json($verifyTransaction);
        } else {

            logInfo($verifyTransaction, "Verify Transaction Before");
            if ($verifyTransaction['status'] == "02") {

                $verifyTransaction['status'] = 1;

                if ($partnerType == "safehaven" || $partnerType == "others") {
                    $totalCustomerAmount = $transaction->company_amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->company_amount + $affliateCompanyCharge;
                } else if ($partnerType != "safehaven") {
                    $totalCustomerAmount = $transaction->amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->amount + $affliateCompanyCharge;
                }

                debitReversal($transactionReference, $transaction->account_id, $transaction->company_id, $totalCustomerAmount, $totalCompanyAmount, $whoToCredit);
            } else {
                $verifyTransaction['status'] = 0;
            }

            logInfo($verifyTransaction, "Verify Transaction Res");
            return response()->json($verifyTransaction);
        }
    }

    public function verifyBankAccountFullname(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bank_code"      => 'required',
            "account_number" => 'required|numeric|gt:0',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => "Bank code and account number is required"];
            return response()->json($ra, 406);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        if (! $bankAccount['status']) {
            $bankAccount['status'] = 0;
            return response()->json($bankAccount);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank                             = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bankAccount['data']['bank_name'] = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $bankAccount['status']            = 1;
        $bankAccount['data']['bank_name'] = $bank_name;
        return response()->json($bankAccount);
    }

    public function validateBetAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bet_company"    => 'required',
            "bet_account_id" => 'required',
        ]);

        return response()->json(
            [
                'status'  => true,
                'message' => "This service is not available at the moment",
            ]
        );

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $url = "/validate_bet_account.php?bet_company=$request->bet_company&bet_account=$request->bet_account_id&is_demo=$isDemo&api_exchange_key=$apiExchangeKey";

        $betAccount = postRequest([], [], $url);

        if ($betAccount['success']) {

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'customer_name' => $betAccount['data']['firstName'] . " " . $betAccount['data']['lastName'],
                        'bet_company'   => $betAccount['data']['provider'],

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify bet account",
                    'data'    => $betAccount,
                ],
                400
            );
        }
    }

    public function checkUnusedProvidusAccounts(Request $request)
    {

        $providusAccounts = wb_account_details::where(['bankid' => '177', 'is_confirmed' => 0])->take(100)->get();

        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        if ($providusAccounts) {
            foreach ($providusAccounts as $providusAccount) {
                // transactions::where('account_id', $providusAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;
                $fundingTransaction = transactions::where('account_id', $providusAccount->id)->where('transaction_type_id', '=', 6)->first();

                if (! $fundingTransaction) {

                    $oldProvidusAccount = $providusAccount->virtual_accnum;

                    $assetMatrixController = new AssetMatrixController();
                    $assetMatrixAccount    = $assetMatrixController->generateAndVerifyAssetMatrixAccount();

                    logInfo($assetMatrixAccount, "New Account Generated");

                    $providusAccount->virtual_accnum = $assetMatrixAccount['data']['virtual_account'];
                    $providusAccount->acc_reference  = $assetMatrixAccount['data']['account_reference'];
                    $providusAccount->bankid         = 25;

                    $response['data'][] = "Providus Account with Account ID: $providusAccount->id changed from $oldProvidusAccount to $providusAccount->virtual_accnum";

                    UnusedProvidusAccounts::where('account_number', $oldProvidusAccount)->update(
                        [
                            'assigned_status' => 1,
                            'account_number'  => $assetMatrixAccount['data']['virtual_account'],
                        ]
                    );
                }

                $providusAccount->is_confirmed = 1;
                $providusAccount->save();
            }
            logInfo($response, "Providus Account Replacement Log");
        }

        return response()->json($response);
    }

    public function checkAssignedProvidusAccounts(Request $request)
    {

        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        $providusAccounts = UnusedProvidusAccounts::where('assigned_status', '1')->get();

        if ($providusAccounts) {
            foreach ($providusAccounts as $providusAccount) {
                // transactions::where('account_id', $providusAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;
                $fundingTransaction = wb_account_details::where('virtual_accnum', $providusAccount->account_number)->first();

                if (! $fundingTransaction) {
                    $providusAccount->assigned_status = 0;
                    $providusAccount->save();
                } else {
                    $response['data'][] = "Providus Account with $providusAccount->account_number";
                }
            }
        }

        return response()->json($response);
    }

    public function matchingWBAccountDetailsWithUserTable(Request $request)
    {
        $response = ['status' => true, 'message' => "Providus unused accounts found"];

        $assetMatrixAccounts = wb_account_details::select('user_id')->distinct()->where('bankid', '25')->get();

        if ($assetMatrixAccounts) {
            $response['data']['count'] = count($assetMatrixAccounts);
            foreach ($assetMatrixAccounts as $assetMatrixAccount) {
                // transactions::where('account_id', $assetMatrixAccount)->where('created_at', '<', Carbon::now()->subMonths(2))->;

                $newAssetMatrix = wb_account_details::where('user_id', $assetMatrixAccount->user_id)->first();

                $userAccount = User::where('id', $newAssetMatrix->user_id)->first();

                $oldAccount = $userAccount->virtual_account;

                $userAccount->virtual_account = $newAssetMatrix->virtual_accnum;
                $userAccount->save();

                // if (!$fundingTransaction) {
                //     $assetMatrixAccount->assigned_status = 0;
                //     $assetMatrixAccount->save();
                // } else {

                $response['data'][] = "Providus Account with old Account: $oldAccount, New AM Account No: $newAssetMatrix->virtual_accnum";

                // }
            }
        }

        logInfo($response, "Providus Account Replacement Log");

        return response()->json($response);
    }

    public function topupBetting(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "bet_company"    => 'required',
            "bet_account_id" => 'required',
            "amount"         => 'required|numeric|gt:0|min:100',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        // return response()->json(['status' => false, 'message' => 'This service is not available at the moment'], 400);

        $reference = generateAccountReference();

        $transactionPinValidation = validatePinCheckCustomerBalance(auth()->user()->pin, $request->amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(24, $request->amount);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $debitCustomer   = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->amount, $companySettings->bills_payment_charge, $wbaCharge);
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(52, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $request->amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_balance_before"],
            "company_amount"         => $debitCustomer["company_amount"],
            "company_balance_after"  => $debitCustomer["company_balance_after"],
            "description"            => "Bet Account Topup worth $request->amount",
            "transaction_reference"  => $reference,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 52,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $transactionChargeId = \generate_txnID();
        $ledger_cr           = null;
        $ledger_dr           = null;
        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        // Save Charges to transaction table
        $transaction = transactions::create([
            "balance_before"         => $debitCustomer['charge_balance_before'],
            "amount"                 => $companySettings->bills_payment_charge,
            "balance_after"          => $debitCustomer["charge_balance_after"],
            "account_id"             => $transactionPinValidation['account_id'],
            "company_id"             => \auth()->user()->company_id,
            "branch_id"              => auth()->user()->branch_id,
            "company_balance_before" => $debitCustomer["company_charge_balance_before"],
            "company_amount"         => $wbaCharge,
            "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
            "description"            => "Charge for Bet Account Topup",
            "transaction_reference"  => $transactionChargeId,
            "parent_transaction_id"  => $reference,
            "transaction_remarks_id" => 1,
            "transaction_type_id"    => 51,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);

        $isDemo         = env("APP_MODE");
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $body = [
            "amount"           => $request->amount,
            "bet_account_id"   => $request->bet_account_id,
            "bet_company"      => $request->bet_company,
            "reference"        => $reference,
            "is_demo"          => $isDemo,
            "api_exchange_key" => $apiExchangeKey,
        ];

        $url = "/topup_bet_account.php";

        $betAccount = postRequest([], $body, $url);

        if ($betAccount['success']) {

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Successfully Topup Bet Account",
                    'data'    => [
                        'status'        => $betAccount['success'],
                        'order_no'      => $betAccount['data']['orderNo'],
                        'reference'     => $betAccount['data']['reference'],
                        'error_message' => $betAccount['data']['status'],
                        'balance'       => $debitCustomer["charge_balance_after"],

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to Topup bet account",
                    'data'    => $betAccount,
                ],
                400
            );
        }
    }

#[OA\Post(
    path: "/api/v1/transactions/verify-wallet-account",
    summary: "Verify Wallet Account",
    description: "Verifies whether a destination wallet account number is valid before proceeding with a wallet-to-wallet transfer. The endpoint checks both customer wallet accounts and company wallet accounts and returns the beneficiary's details when the account is successfully verified.",
    tags: ["Transfer - Wallet To Wallet"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["account_number"],
            properties: [
                new OA\Property(
                    property: "account_number",
                    type: "string",
                    example: "1234567890",
                    description: "Destination wallet account number to be verified."
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Wallet account verified successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Account Verified Successfully"
                    ),
                    new OA\Property(
                        property: "data",
                        properties: [
                            new OA\Property(
                                property: "first_name",
                                type: "string",
                                example: "John"
                            ),
                            new OA\Property(
                                property: "last_name",
                                type: "string",
                                example: "Doe"
                            ),
                            new OA\Property(
                                property: "bank_name",
                                type: "string",
                                example: "Example Bank"
                            ),
                            new OA\Property(
                                property: "currency_id",
                                type: "integer",
                                example: 1
                            ),
                        ],
                        type: "object"
                    ),
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 406,
            description: "Validation failed because the account number is missing"
        ),
        new OA\Response(
            response: 409,
            description: "Cannot transfer to the authenticated user's own wallet account"
        ),
        new OA\Response(
            response: 400,
            description: "Failed to verify Wallet account"
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        )
    ]
)]
    public function verifyWalletAccount(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "account_number" => 'required',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->account_number == \auth()->user()->virtual_account) {
            return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
        }
        $wbAccount = wb_account_details::where('virtual_accnum', $request->account_number)->orWhere('virtual_accnum', ($request->account_number . "_"))->first();

        if ($wbAccount) {

            // if ($wbAccount->user_id == \auth()->user()->id) {

            //     return response()->json(
            //         [
            //             'status' => false,
            //             'message' => "Cannot Transfer to Self",
            //         ],
            //         409
            //     );
            // }

            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name'  => $wbAccount->user->first_name,
                        'last_name'   => $wbAccount->user->last_name,
                        'bank_name'   => $wbAccount->bank->bank_name,
                        'currency_id' => $wbAccount->currency_id ?? 1,
                    ],
                ]
            );
        }

        if ($wbAccount == null) {
            $companyAccount = company_wallet::where('vaccount', $request->account_number)->first();
            return response()->json(
                [
                    'status'  => true,
                    'message' => "Account Verified Successfully",
                    'data'    => [
                        'first_name' => $companyAccount->company->company_name,
                        'last_name'  => "",
                        'bank_name'  => "",

                    ],
                ]
            );
        } else {

            return response()->json(
                [
                    'status'  => false,
                    'message' => "Failed to verify Wallet account",
                ],
                400
            );
        }
    }

#[OA\Post(
    path: "/api/v1/transactions/bank-transfer",
    summary: "Complete a bank transfer",
    description: "Completes a bank transfer using the transaction reference returned by the initiate-transaction endpoint. The request field receipient_name is intentionally spelled as currently required by the backend.",
    tags: ["Transfer - Wallet To Bank"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: [
                "final_amount",
                "transaction_reference",
                "destination_account",
                "receipient_name",
                "bank_code",
                "transaction_pin"
            ],
            properties: [
                new OA\Property(property: "final_amount", type: "number", format: "float", example: 1000),
                new OA\Property(property: "destination_account", type: "string", example: "0123456789"),
                new OA\Property(property: "bank_code", type: "string", example: "000001"),
                new OA\Property(property: "receipient_name", type: "string", example: "Ada Example"),
                new OA\Property(property: "transaction_reference", type: "string", example: "TRX-123456"),
                new OA\Property(property: "transaction_pin", type: "string", example: "1234"),
                new OA\Property(property: "description", type: "string", example: "Transfer to Ada Example")
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Bank transfer completed or the transaction endpoint returned a result",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Wallet to Bank Transfer Successful"),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(property: "balance", type: "number", format: "float", example: 49000)
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Transfer rejected, transaction failed, or another attempt is required",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Insufficient Balance")
                ]
            )
        ),
        new OA\Response(
            response: 406,
            description: "Invalid request parameters or unavailable bank service",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Service is not available right now")
                ]
            )
        ),
        new OA\Response(
            response: 409,
            description: "Duplicate, completed, failed, or otherwise conflicting transaction",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Transaction has already been completed")
                ]
            )
        )
    ]
)]

public function transferToBankAccount(Request $request)
{
    // Never log transaction PINs, passwords, tokens, or the entire request.
    logInfo([
        'user_id' => auth()->id(),
        'company_id' => auth()->user()->company_id ?? null,
        'transaction_reference' => $request->input('transaction_reference'),
        'amount' => $request->input('final_amount'),
        'bank_code' => $request->input('bank_code'),
        'destination_account_last4' => substr(
            (string) $request->input('destination_account'),
            -4
        ),
    ], 'Bank Transfer Request');

    $currentUser = auth('api')->user() ?? auth()->user();

    if (!$currentUser) {
        return response()->json([
            'status' => false,
            'message' => 'Unauthenticated.',
        ], 401);
    }

    $lockKey = "user:last_request:{$currentUser->id}";
    $lock = Cache::lock($lockKey, 60);

    if (!$lock->get()) {
        return response()->json([
            'status' => false,
            'message' => 'Transaction is in progress. Try again.',
        ], 409);
    }

    $transactionStarted = false;
    $providerSucceeded = false;
    $localDebitCompleted = false;
    $providerCallStarted = false;
    $transaction = null;
    $debitCustomer = null;

    $transactionReference = (string) $request->input(
        'transaction_reference'
    );

    try {
        /*
         * ============================================================
         * 1. VALIDATION
         * ============================================================
         */

        $validation = Validator::make($request->all(), [
            'final_amount' => 'required|numeric|gt:0',
            'transaction_reference' => 'required|string|max:100',
            'destination_account' => 'required|string',
            'receipient_name' => 'required|string|max:255',
            'bank_code' => 'required|string',
            'transaction_pin' => 'required|numeric|gt:0',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validation->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validation->errors()->first(),
            ], 422);
        }

        if ($request->bank_code === '090267') {
            return response()->json([
                'status' => false,
                'message' => 'Service is not available right now',
            ], 406);
        }

        $amount = (float) $request->final_amount;

        if ($amount > 3000000) {
            return response()->json([
                'status' => false,
                'message' => 'Maximum transfer amount is 3,000,000',
            ], 400);
        }

        /*
         * ============================================================
         * 2. CUSTOMER / ACCOUNT VALIDATION
         * ============================================================
         */

        $account = $currentUser->accounts()->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Customer account was not found.',
            ], 400);
        }

        if (!$currentUser->company) {
            return response()->json([
                'status' => false,
                'message' => 'Company configuration was not found.',
            ], 400);
        }

        $validateSavingsProduct = $this->accountProductCharges($amount);

        if (!($validateSavingsProduct['status'] ?? false)) {
            return response()->json($validateSavingsProduct, 400);
        }

        $charge = (float) (
            $validateSavingsProduct['data']['charge'] ?? 0
        );

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(
            $currentUser->company_id
        );

        if (($checkAllowedTransaction['status'] ?? null) != 1) {
            $checkAllowedTransaction['status'] = false;

            return response()->json(
                $checkAllowedTransaction,
                400
            );
        }

        /*
         * Validate PIN and customer balance before anything is debited.
         */
        $transactionPinValidation = validatePinCheckCustomerBalance(
            $request->transaction_pin,
            $amount + $charge
        );

        if (!($transactionPinValidation['status'] ?? false)) {
            return response()->json(
                $transactionPinValidation,
                400
            );
        }

        $accountId = $transactionPinValidation['account_id'] ?? null;

        if (!$accountId || (int) $accountId !== (int) $account->id) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The validated account does not match the authenticated customer.',
            ], 403);
        }

        $userWallet = user_wallet::where(
            'wb_account_detail_id',
            $account->id
        )->first();

        if (!$userWallet) {
            return response()->json([
                'status' => false,
                'message' => 'Customer wallet was not found.',
            ], 400);
        }

        if ((float) $userWallet->balance < ($amount + $charge)) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient Balance',
            ], 400);
        }

        if (checkcustomerrestriction($currentUser->id, '2')) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Your account has been restricted. Please contact support.',
            ], 400);
        }

        $validateTransferAmount = $this->validateTransfer($amount);

        if (!($validateTransferAmount['status'] ?? false)) {
            return response()->json(
                $validateTransferAmount,
                400
            );
        }

        /*
         * ============================================================
         * 3. CHARGES
         * ============================================================
         */

        $wbaCharge = (float) getWbaTransferCharges(1, $amount);

        // Preserve existing stamp-duty/charge calculation.
        if ($amount > 9999) {
            $wbaCharge += 50;
            $charge += 50;
        }

        /*
         * ============================================================
         * 4. COMPANY / CUSTOMER BALANCE PRE-CHECK
         * ============================================================
         */

        $companyDebitBankCode = $this->getCompanyBankCode();

        logInfo([
            'company_id' => $currentUser->company_id,
            'company_debit_bankcode' => $companyDebitBankCode,
            'transaction_reference' => $transactionReference,
        ], 'COMPANY DEBIT BANK DEBUG');

        $checkCompanyBalance = match ((int) $companyDebitBankCode) {
            90286 => ['status' => true],
            default => $this->checkCompanyBalanceThresholdTransaction(
                $amount
            ),
        };

        if (!($checkCompanyBalance['status'] ?? false)) {
            return response()->json(
                $checkCompanyBalance,
                400
            );
        }

        /*
         * This is ONLY a balance check.
         * It does NOT debit anything.
         */
        $checkDebitCustomer = match ((int) $companyDebitBankCode) {
            90286 => checkCustomerAndCompanyWithChargeSafeHaven(
                $accountId,
                $currentUser->company_id,
                $amount,
                $charge,
                $wbaCharge
            ),

            default => checkCustomerAndCompanyWithCharge(
                $accountId,
                $currentUser->company_id,
                $amount,
                $charge,
                $wbaCharge
            ),
        };

        if (
            !is_array($checkDebitCustomer) ||
            !($checkDebitCustomer['status'] ?? false)
        ) {
            return response()->json(
                is_array($checkDebitCustomer)
                    ? $checkDebitCustomer
                    : [
                        'status' => false,
                        'message' =>
                            'Unable to validate transfer balances.',
                    ],
                400
            );
        }

        /*
         * ============================================================
         * 5. LEDGER CONFIGURATION
         * ============================================================
         */

        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(
            27,
            $currentUser->company_id
        );

        if (!$ledgerSettings) {
            return response()->json([
                'status' => false,
                'message' =>
                    'Bank Transfer Charge ledger is not configured for this transaction type.',
            ], 422);
        }

        $ledgerCr = $ledgerSettings->ledger_cr;
        $ledgerDr = $ledgerSettings->ledger_dr;

        /*
         * ============================================================
         * 6. RESOLVE EBANKING PROVIDER
         * ============================================================
         */

        $preferredEbankingPartner = PreferredEbankingPartnerCompany::where(
            'company_id',
            $currentUser->company_id
        )->first();

        if ($preferredEbankingPartner) {
            $ebankingService = new EbankingCompanyService(
                $preferredEbankingPartner
            );
        } else {
            $preferredEbankingPartner = PreferredEbankingPartner::all();

            $ebankingService = new EbankingService(
                $preferredEbankingPartner
            );
        }

        /*
         * ============================================================
         * 7. VERIFY DESTINATION ACCOUNT
         * ============================================================
         */

        $bankAccount = $ebankingService->verifyAccountNumberFull(
            $request->bank_code,
            $request->destination_account,
            $currentUser->company_id
        );

        if (
            !is_array($bankAccount) ||
            !($bankAccount['status'] ?? false)
        ) {
            return response()->json(
                is_array($bankAccount)
                    ? $bankAccount
                    : [
                        'status' => false,
                        'message' =>
                            'Bank account verification returned an invalid response.',
                    ],
                400
            );
        }

        $bank = banks::where(
            'bank_code',
            $request->bank_code
        )
            ->orWhere(
                'short_code',
                $request->bank_code
            )
            ->first();

        if (!$bank) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The destination bank could not be identified.',
            ], 400);
        }

        /*
         * ============================================================
         * 8. LOCK / RESERVE TRANSACTION
         * ============================================================
         */

        DB::beginTransaction();
        $transactionStarted = true;

        $transaction = transactions::where(
            'transaction_reference',
            $transactionReference
        )
            ->where(
                'company_id',
                $currentUser->company_id
            )
            ->where(
                'amount',
                $amount
            )
            ->lockForUpdate()
            ->first();

        if (!$transaction) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'Invalid transaction reference, company, or amount.',
            ], 400);
        }

        if (
            !empty($transaction->account_id) &&
            (int) $transaction->account_id !== (int) $accountId
        ) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'This transaction does not belong to the authenticated account.',
            ], 403);
        }

        if ((int) $transaction->transaction_remarks_id === 2) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'Transaction has already been completed.',
            ], 409);
        }

        if ((int) $transaction->transaction_remarks_id === 3) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'This transaction has been marked failed. Contact support before retrying.',
            ], 409);
        }

        if ((int) $transaction->transaction_remarks_id === 5) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'This transaction has already been reversed.',
            ], 409);
        }

        if ((int) $transaction->transaction_remarks_id === 1) {
            DB::rollBack();
            $transactionStarted = false;

            return response()->json([
                'status' => false,
                'message' =>
                    'This transaction is already pending or being processed.',
            ], 409);
        }

        /*
         * Mark as processing BEFORE the local debit and provider call.
         */
        $transaction->transaction_remarks_id = 1;
        $transaction->save();

        DB::commit();
        $transactionStarted = false;

        /*
         * ============================================================
         * 9. LOCAL DEBIT
         *
         * THIS IS THE MAJOR CHANGE.
         *
         * We debit locally BEFORE calling EbankingService.
         * ============================================================
         */

        try {
            $debitCustomer = match ((int) $companyDebitBankCode) {
                90286 => debitCustomerAndCompanyWithChargeServiceWallet(
                    $accountId,
                    $currentUser->company_id,
                    $amount,
                    $charge,
                    $wbaCharge,
                    $currentUser
                ),

                default => debitCustomerAndCompanyWithCharge(
                    $accountId,
                    $currentUser->company_id,
                    $amount,
                    $charge,
                    $wbaCharge,
                    $currentUser
                ),
            };

            if (
                !is_array($debitCustomer) ||
                !($debitCustomer['status'] ?? false)
            ) {
                logInfo([
                    'transaction_reference' => $transactionReference,
                    'user_id' => $currentUser->id,
                    'company_id' => $currentUser->company_id,
                    'debit_result' => is_array($debitCustomer)
                        ? $debitCustomer
                        : gettype($debitCustomer),
                ], 'LOCAL TRANSFER DEBIT FAILED BEFORE PROVIDER');

                DB::transaction(function () use (
                    $transactionReference,
                    $currentUser
                ) {
                    $failedTransaction = transactions::where(
                        'transaction_reference',
                        $transactionReference
                    )
                        ->where(
                            'company_id',
                            $currentUser->company_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (
                        $failedTransaction &&
                        (int) $failedTransaction->transaction_remarks_id === 1
                    ) {
                        $failedTransaction->transaction_remarks_id = 3;
                        $failedTransaction->save();
                    }
                });

                return response()->json([
                    'status' => false,
                    'message' =>
                        $debitCustomer['message']
                        ?? 'Unable to debit your account.',
                    'transaction_reference' =>
                        $transactionReference,
                ], 400);
            }

            $localDebitCompleted = true;

            /*
             * Store the debit information on the transaction.
             * This is important for reconciliation.
             */
            DB::transaction(function () use (
                $transactionReference,
                $currentUser,
                $debitCustomer,
                $amount,
                $ledgerCr,
                $ledgerDr,
                $wbaCharge,
                $bankAccount,
                $bank,
                $request
            ) {
                $lockedTransaction = transactions::where(
                    'transaction_reference',
                    $transactionReference
                )
                    ->where(
                        'company_id',
                        $currentUser->company_id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$lockedTransaction) {
                    throw new \RuntimeException(
                        'Transaction record missing after local debit.'
                    );
                }

                if (
                    (int) $lockedTransaction->transaction_remarks_id !== 1
                ) {
                    throw new \RuntimeException(
                        'Transaction is not in the expected processing state after debit.'
                    );
                }

                $lockedTransaction->description =
                    'You transferred funds to '
                    . (
                        $bankAccount['data']['account_name']
                        ?? $request->receipient_name
                    )
                    . '/'
                    . $request->destination_account
                    . ' / '
                    . $bank->bank_name
                    . ' / '
                    . $currentUser->company->company_name;

                $lockedTransaction->account_id =
                    $debitCustomer['account_id'];

                $lockedTransaction->balance_before =
                    $debitCustomer['balance_before'];

                $lockedTransaction->amount = $amount;

                $lockedTransaction->balance_after =
                    $debitCustomer['charge_balance_after'];

                $lockedTransaction->company_balance_before =
                    $debitCustomer['company_balance_before'];

                $lockedTransaction->company_amount =
                    $amount + $wbaCharge;

                $lockedTransaction->company_balance_after =
                    $debitCustomer['company_charge_balance_after'];

                $lockedTransaction->ledger_cr = $ledgerCr;
                $lockedTransaction->ledger_dr = $ledgerDr;

                $lockedTransaction->save();
            });

        } catch (\Throwable $localDebitException) {

    logInfo([
        'transaction_reference' => $transactionReference,
        'company_id' => $currentUser->company_id,
        'local_debit_completed' => $localDebitCompleted,
        'exception_class' => get_class($localDebitException),
        'exception_message' => $localDebitException->getMessage(),
    ], 'LOCAL DEBIT OR DEBIT RECORDING EXCEPTION');

    /*
     * If the debit was completed but saving its transaction
     * details failed, the provider has NOT been called yet.
     *
     * Therefore, it is safe to reverse the local debit.
     */
    if ($localDebitCompleted && is_array($debitCustomer)) {

        $reversal = reverseCustomerAndCompanyTransferDebit(
            $accountId,
            $currentUser->company_id,
            $amount,
            $charge,
            $wbaCharge,
            $currentUser,
            (int) $companyDebitBankCode,
            $transactionReference,
            (float) ($debitCustomer['spend_save_cash'] ?? 0)
        );

        if (!is_array($reversal) || !($reversal['status'] ?? false)) {

            logInfo([
                'transaction_reference' => $transactionReference,
                'company_id' => $currentUser->company_id,
                'reversal_result' => $reversal,
            ], 'CRITICAL: PRE-PROVIDER LOCAL REVERSAL FAILED');

            return response()->json([
                'status' => false,
                'message' => 'The local debit was completed, but reversal requires reconciliation. Please contact support. Do not retry this reference.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        return response()->json([
            'status' => false,
            'message' => 'Unable to complete the transfer. The local debit has been reversed successfully.',
            'transaction_reference' => $transactionReference,
        ], 400);
    }

    /*
     * If the debit helper did not complete successfully,
     * no reversal is required.
     */
    DB::transaction(function () use (
        $transactionReference,
        $currentUser
    ) {

        $failedTransaction = transactions::where(
            'transaction_reference',
            $transactionReference
        )
            ->where('company_id', $currentUser->company_id)
            ->lockForUpdate()
            ->first();

        if (
            $failedTransaction &&
            (int) $failedTransaction->transaction_remarks_id === 1
        ) {
            $failedTransaction->transaction_remarks_id = 3;
            $failedTransaction->save();
        }
    });

    return response()->json([
        'status' => false,
        'message' => 'Unable to process the local debit. Please try again.',
        'transaction_reference' => $transactionReference,
    ], 500);
    }

        /*
         * ============================================================
         * 10. BUILD BANK TRANSFER REQUEST
         * ============================================================
         */

        $gatewayDescription =
            $currentUser->first_name
            . ' '
            . $currentUser->last_name
            . ' / '
            . $currentUser->company->company_name;

        $bankTransferType = new BankTransferType();

        $bankTransferType->bankCode =
            $request->bank_code;

        $bankTransferType->shortBankCode =
            $bank->short_code;

        $bankTransferType->accountNumber =
            $request->destination_account;

        $bankTransferType->description =
            $gatewayDescription;

        $bankTransferType->amount =
            $amount;

        $bankTransferType->transactionReference =
            $transaction->transaction_reference;

        $bankTransferType->sessionId =
            $currentUser->virtual_account;

        $bankTransferType->companyID =
            $currentUser->company_id;

        $bankTransferType->senderAccountNumber =
            $account->virtual_accnum ?? '';

        /*
         * ============================================================
         * 11. CALL EBANKING PROVIDER
         * ============================================================
         *
         * IMPORTANT:
         * We do NOT put this inside a DB transaction.
         * The external provider cannot be rolled back by DB rollback.
         */

        try {

    // From this point onward, the provider may receive the request.
    $providerCallStarted = true;

    $bankTransfer = $ebankingService->transferToBankAccount(
        $bankTransferType
    );

        } catch (\Throwable $providerException) {

            logInfo([
                'transaction_reference' =>
                    $transactionReference,
                'company_id' =>
                    $currentUser->company_id,
                'exception_class' =>
                    get_class($providerException),
                'exception_message' =>
                    $providerException->getMessage(),
            ], 'BANK TRANSFER PROVIDER EXCEPTION - RECONCILIATION REQUIRED');

            /*
             * We DO NOT reverse here.
             *
             * The provider may have received and completed
             * the transfer even though our application received
             * an exception/timeout.
             */

            return response()->json([
                'status' => false,
                'message' =>
                    'Transfer status could not be confirmed. Please contact support; do not retry this reference.',
                'transaction_reference' =>
                    $transactionReference,
            ], 500);
        }

        logInfo([
            'company_id' =>
                $currentUser->company_id,

            'preferred_partner_type' =>
                is_object($preferredEbankingPartner)
                    ? get_class($preferredEbankingPartner)
                    : gettype($preferredEbankingPartner),

            'preferred_partner_count' =>
                $preferredEbankingPartner instanceof
                \Illuminate\Support\Collection
                    ? $preferredEbankingPartner->count()
                    : null,

            'provider_status' =>
                is_array($bankTransfer)
                    ? ($bankTransfer['status'] ?? null)
                    : null,

            'bank_transfer_value' =>
                json_encode($bankTransfer),

            'transaction_reference' =>
                $transactionReference,

        ], 'TRANSFER PROVIDER DEBUG');

        /*
         * ============================================================
         * 12. AMBIGUOUS PROVIDER RESPONSE
         * ============================================================
         */

        if (
            !is_array($bankTransfer) ||
            !array_key_exists('status', $bankTransfer)
        ) {

            logInfo([
                'transaction_reference' =>
                    $transactionReference,

                'provider_response_type' =>
                    gettype($bankTransfer),

            ], 'BANK TRANSFER AMBIGUOUS PROVIDER RESPONSE');

            /*
             * Do NOT reverse.
             * The provider outcome is unknown.
             */
            return response()->json([
                'status' => false,
                'message' =>
                    'Transfer status could not be confirmed. Please contact support.',
                'transaction_reference' =>
                    $transactionReference,
            ], 500);
        }

        /*
         * ============================================================
         * 13. PROVIDER FAILED
         * ============================================================
         */

        if ((string) $bankTransfer['status'] !== '00') {

    /*
     * Only these statuses are currently treated as definitive
     * provider failures under your existing provider-status logic.
     */
    if (in_array(
        (string) $bankTransfer['status'],
        ['01', '02', '03'],
        true
    )) {

        /*
         * A definitive provider rejection means the local
         * debit must be reversed.
         */
        if (!$localDebitCompleted || !is_array($debitCustomer)) {

            logInfo([
                'transaction_reference' => $transactionReference,
                'provider_status' => $bankTransfer['status'],
                'local_debit_completed' => $localDebitCompleted,
            ], 'CRITICAL: PROVIDER REJECTED BUT LOCAL DEBIT STATE IS MISSING');

            return response()->json([
                'status' => false,
                'message' => 'Transfer requires reconciliation. Please contact support.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        $reversal = reverseCustomerAndCompanyTransferDebit(
            $accountId,
            $currentUser->company_id,
            $amount,
            $charge,
            $wbaCharge,
            $currentUser,
            (int) $companyDebitBankCode,
            $transactionReference,
            (float) ($debitCustomer['spend_save_cash'] ?? 0)
        );

        if (
            !is_array($reversal) ||
            !($reversal['status'] ?? false)
        ) {

            logInfo([
                'transaction_reference' => $transactionReference,
                'company_id' => $currentUser->company_id,
                'provider_status' => $bankTransfer['status'],
                'provider_response' => $bankTransfer,
                'reversal_result' => $reversal,
            ], 'CRITICAL: PROVIDER FAILED BUT LOCAL REVERSAL FAILED');

            /*
             * Do not mark this transaction as normally failed.
             * The local debit still exists and needs reconciliation.
             */
            return response()->json([
                'status' => false,
                'message' => 'The bank transfer failed, but the local reversal requires reconciliation. Please contact support. Do not retry this reference.',
                'transaction_reference' => $transactionReference,
            ], 500);
        }

        /*
         * IMPORTANT:
         * The reversal helper already marks the transaction
         * transaction_remarks_id = 5.
         *
         * DO NOT run another DB transaction to change it to 3.
         */

        logInfo([
            'transaction_reference' => $transactionReference,
            'provider_status' => $bankTransfer['status'],
            'reversal_result' => $reversal,
        ], 'BANK TRANSFER FAILED AND LOCAL DEBIT REVERSED');

        $bankTransfer['status'] = false;

        return response()->json(
            $bankTransfer,
            400
        );
    }

    /*
     * Unknown or non-definitive provider status.
     * Do not reverse because the provider outcome is uncertain.
     */
    logInfo([
        'transaction_reference' => $transactionReference,
        'provider_status' => $bankTransfer['status'],
    ], 'BANK TRANSFER STATUS AMBIGUOUS - RECONCILIATION REQUIRED');

    return response()->json([
        'status' => false,
        'message' => 'Transfer status could not be confirmed. Please contact support.',
        'transaction_reference' => $transactionReference,
    ], 500);
 }

        /*
         * ============================================================
         * 14. PROVIDER SUCCESS
         * ============================================================
         */

        $providerSucceeded = true;

        /*
         * The customer/company debit has ALREADY happened.
         *
         * DO NOT call debitCustomerAndCompanyWithCharge() again.
         *
         * We only finalize the transaction here.
         */
        try {

            $finalization = DB::transaction(
                function () use (
                    $transactionReference,
                    $currentUser,
                    $amount,
                    $ledgerCr,
                    $ledgerDr
                ) {

                    $lockedTransaction =
                        transactions::where(
                            'transaction_reference',
                            $transactionReference
                        )
                        ->where(
                            'company_id',
                            $currentUser->company_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedTransaction) {
                        throw new \RuntimeException(
                            'Transaction record missing during finalization.'
                        );
                    }

                    if (
                        (int) $lockedTransaction->transaction_remarks_id === 2
                    ) {
                        throw new \RuntimeException(
                            'Transaction was already finalized.'
                        );
                    }

                    if (
                        (int) $lockedTransaction->transaction_remarks_id !== 1
                    ) {
                        throw new \RuntimeException(
                            'Transaction is not in the expected pending state.'
                        );
                    }

                    /*
                     * Debit has already happened before the provider call.
                     *
                     * Therefore we only mark the transaction successful.
                     */
                    $lockedTransaction->amount = $amount;

                    $lockedTransaction->ledger_cr =
                        $ledgerCr;

                    $lockedTransaction->ledger_dr =
                        $ledgerDr;

                    $lockedTransaction->transaction_remarks_id = 2;

                    $lockedTransaction->save();

                    return [
                        'status' => true,

                        'charge_balance_after' =>
                            $lockedTransaction->balance_after,
                    ];
                }
            );

        } catch (\Throwable $finalizationException) {

            logInfo([
                'transaction_reference' =>
                    $transactionReference,

                'company_id' =>
                    $currentUser->company_id,

                'provider_succeeded' =>
                    $providerSucceeded,

                'local_debit_completed' =>
                    $localDebitCompleted,

                'exception_class' =>
                    get_class($finalizationException),

                'exception_message' =>
                    $finalizationException->getMessage(),

            ], 'CRITICAL: TRANSFER REQUIRES RECONCILIATION');

            return response()->json([
                'status' => false,
                'message' =>
                    'The bank transfer succeeded but local processing requires reconciliation. Please contact support. Do not retry this reference.',
                'transaction_reference' =>
                    $transactionReference,
            ], 500);
        }

        /*
         * ============================================================
         * 15. BENEFICIARY + NOTIFICATIONS
         * ============================================================
         */

        try {

            $accountName =
                $bankAccount['data']['account_name']
                ?? $request->receipient_name;

            $beneficiary = Beneficiary::where(
                'accountNo',
                $request->destination_account
            )
                ->whereNull('transaction_id')
                ->where(
                    'user_id',
                    $currentUser->id
                )
                ->first();

            if (!$beneficiary) {
                $beneficiary = new Beneficiary();

                $beneficiary->name =
                    $request->receipient_name;

                $beneficiary->bank_id =
                    $bank->id;

                $beneficiary->accountNo =
                    $request->destination_account;

                $beneficiary->user_id =
                    $currentUser->id;

                $beneficiary->save();
            }

            $description =
                $request->description ?? '';

            if ((int) $currentUser->company_id === 33) {

                goldencreditSMSPost(
                    $currentUser->phone,
                    'transaction',
                    $amount,
                    $finalization['charge_balance_after'],
                    $currentUser->id
                );

            } else {

                sendSMS(
                    $currentUser->id,
                    "Debit Amt: | {$amount}, Desc: App/ {$description} "
                    . "Acc: {$accountName} "
                    . "Bal: N {$finalization['charge_balance_after']} "
                    . "Date: " . date('Y-m-d')
                    . " Ref: {$transactionReference}",
                    true,
                    true,
                    'Debit Transaction'
                );
            }

            $msg =
                "Debit Amt:{$amount},<br><br> Desc: App/"
                . "{$accountName}{$description}"
                . "<br><br> Avail Bal:"
                . $finalization['charge_balance_after']
                . "<br><br>Date:" . date('Y-m-d')
                . "<br><br>Ref: {$transactionReference}";

            $this->sendTransactionReportEmail(
                'Debit Transaction',
                $msg,
                '',
                $currentUser
            );

        } catch (\Throwable $notificationException) {

            logInfo([
                'transaction_reference' =>
                    $transactionReference,

                'exception_class' =>
                    get_class($notificationException),

                'exception_message' =>
                    $notificationException->getMessage(),

            ], 'TRANSFER COMPLETED BUT NOTIFICATION FAILED');
        }

        /*
         * ============================================================
         * 16. SUCCESS RESPONSE
         * ============================================================
         */

        return response()->json([
            'status' => true,
            'message' =>
                'Wallet to Bank Transfer Successful',

            'data' => [
                'balance' =>
                    $finalization['charge_balance_after'],

                'transaction_reference' =>
                    $transactionReference,
            ],
        ]);

    } catch (\Throwable $e) {

        if (
            $transactionStarted &&
            DB::transactionLevel() > 0
        ) {
            DB::rollBack();
            $transactionStarted = false;
        }

        logInfo([
            'transaction_reference' =>
                $transactionReference,

            'user_id' =>
                $currentUser->id ?? null,

            'provider_succeeded' =>
                $providerSucceeded,

            'local_debit_completed' =>
                $localDebitCompleted,

            'exception_class' =>
                get_class($e),

            'exception_message' =>
                $e->getMessage(),

        ], 'BANK TRANSFER CONTROLLER EXCEPTION');

        if ($providerSucceeded) {
            return response()->json([
                'status' => false,
                'message' =>
                    'The bank transfer succeeded but local processing requires reconciliation. Please contact support. Do not retry this reference.',
                'transaction_reference' =>
                    $transactionReference,
            ], 500);
        }

        /*
         * If local debit already happened but we reach this catch
         * without a definitive provider response, DO NOT automatically
         * reverse. The provider outcome may still be unknown.
         */
        /*
 * If the local debit happened but the provider call never started,
 * the provider cannot have processed this transfer through this
 * execution path. Reverse the local debit.
 */
 if ($localDebitCompleted && !$providerCallStarted) {

    $reversal = reverseCustomerAndCompanyTransferDebit(
        $accountId,
        $currentUser->company_id,
        $amount,
        $charge,
        $wbaCharge,
        $currentUser,
        (int) $companyDebitBankCode,
        $transactionReference,
        (float) ($debitCustomer['spend_save_cash'] ?? 0)
    );

    if (!is_array($reversal) || !($reversal['status'] ?? false)) {

        logInfo([
            'transaction_reference' => $transactionReference,
            'reversal_result' => $reversal,
        ], 'CRITICAL: OUTER CATCH REVERSAL FAILED');

        return response()->json([
            'status' => false,
            'message' => 'The local debit requires reconciliation. Please contact support. Do not retry this reference.',
            'transaction_reference' => $transactionReference,
        ], 500);
    }

    return response()->json([
        'status' => false,
        'message' => 'Transfer processing failed before reaching the bank provider. Your local debit has been reversed.',
        'transaction_reference' => $transactionReference,
    ], 500);
 }

 /*
 * If the provider call started, its outcome may be unknown.
 * Do not automatically reverse.
 */
 if ($localDebitCompleted) {

    return response()->json([
        'status' => false,
        'message' => 'Transfer status could not be confirmed. Please contact support; do not retry this reference.',
        'transaction_reference' => $transactionReference,
    ], 500);
 }

        return response()->json([
            'status' => false,
            'message' =>
                'Unable to process the transfer. Please try again later or contact support.',
            'transaction_reference' =>
                $transactionReference ?: null,
        ], 500);

    } finally {

        if (
            $transactionStarted &&
            DB::transactionLevel() > 0
        ) {
            DB::rollBack();
        }

        $lock->release();
    }
}


    public function transferToCrossBorder(Request $request)
    {
        $userId  = auth()->user()->id ?? $request->id;
        $lockKey = "user:last_request:{$userId}";
        $lock    = Cache::lock($lockKey, 20); // 10-second lock
        if ($lock->get()) {
            try {
                $description = $request->description;

                $validation = Validator::make($request->all(), [
                    "final_amount"          => "required|numeric|gt:0",
                    "transaction_reference" => "required",
                    "destination_account"   => "required",
                    "receipient_name"       => "required|string",
                    "bank_code"             => "required",
                    "transaction_pin"       => "required|numeric|gt:0",
                ]);

                if ($validation->fails()) {
                    $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
                    return response()->json($ra, 406);
                }

                $charge = 0;

                $currentUser = auth()->user();

                $charge = isset($validateSavingsProduct['data']['charge']) ? $validateSavingsProduct['data']['charge'] : 0;

                $totalAmount = $request->final_amount + $charge;

                DB::beginTransaction();

                $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $totalAmount);
                if (! $transactionPinValidation['status']) {
                    return response()->json($transactionPinValidation, 400);
                }

                // $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
                $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $request->transaction_reference])->first();

                if ($transaction) {
                    if ($transaction->transaction_remarks_id == 2) {
                        return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                    } else if ($transaction->transaction_remarks_id == 3) {
                        return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                    } else if ($transaction->transaction_remarks_id == 5) {
                        return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                    } else if ($transaction->transaction_remarks_id == 1) {
                        return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                    } else {

                        $transaction->transaction_remarks_id = 1;
                        $transaction->save();

                        $wb_account_detail_id = wb_account_details::where('user_id', $currentUser->id)->where('virtual_accnum', $currentUser->remittar_customer_id)->first();
                        $userWallet           = user_wallet::where('wb_account_detail_id', $wb_account_detail_id->id)->first();

                        if ($userWallet->balance >= $totalAmount) {

                            if (\checkcustomerrestriction($currentUser->id, '2')) {
                                return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                            }

                            $wbaCharge = getWbaTransferCharges(1, $request->final_amount);
                            logInfo(json_encode($wbaCharge), "wbaCharge");

                            $charge = $request->final_amount > 9999 ? ($charge + 50) : $charge + 0;

                            logInfo(json_encode($charge), "customer Charge stamp duty");

                            $customerBalance = $this->checkCustomerBalanceCrossborder($request->final_amount, $userWallet);

                            if (! $customerBalance['status']) {
                                return response()->json($customerBalance, 400);
                            }

                            $bridge = new BridgeController();

                            logInfo(json_encode($currentUser->company->id), "PreferredEbankingPartnerCompany");
                            $preferredEbankingPartner = PreferredEbankingPartnerCompany::where('company_id', $currentUser->company->id)->first();

                            if (isset($preferredEbankingPartner->company_id)) {
                                //logInfo(json_encode($currentUser->company->id), "PreferredEbankingPartnerCompany");

                                logInfo(json_encode($preferredEbankingPartner), "PreferredEbankingPartnerCompany");
                                $ebankingService = new EbankingCompanyService($preferredEbankingPartner);

                            } else {
                                $preferredEbankingPartner = PreferredEbankingPartner::all();
                                $ebankingService          = new EbankingService($preferredEbankingPartner);

                            }

                            $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->destination_account, $currentUser->company->id);

                            if (! $bankAccount['status']) {
                                $bankAccount['status'] = false;
                                return response()->json($bankAccount);
                            }

                            $bank               = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();
                            $gatewayDescription = $currentUser->first_name . " " . $currentUser->last_name . " / " . $currentUser->company->company_name;

                            $checkDebitCustomer = match ($company_debit_bankcode) {

                                90286 => checkCustomerAndCompanyWithChargeSafeHaven($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge), //7000299948
                                default
                                      => checkCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge),
                            };

                            // $checkDebitCustomer = checkCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge);
                            logInfo(json_encode($checkDebitCustomer), "checkDebitCustomer");
                            if (! $checkDebitCustomer['status']) {

                                $checkDebitCustomer['status'] = false;
                                return response()->json($checkDebitCustomer);
                            }

                            $myDescription = auth()->user()->first_name . " " . auth()->user()->last_name . " / " . \auth()->user()->company->company_name;
                            // $myDescription .= $request->description == "" ? "" : $request->description;
                            DB::commit();

                            $bankTransferType                       = new BankTransferType();
                            $bankTransferType->bankCode             = $request->bank_code;
                            $bankTransferType->shortBankCode        = $bank->short_code;
                            $bankTransferType->accountNumber        = $request->destination_account;
                            $bankTransferType->description          = $gatewayDescription;
                            $bankTransferType->amount               = $request->final_amount;
                            $bankTransferType->transactionReference = $transaction->transaction_reference;
                            $bankTransferType->sessionId            = $currentUser->virtual_account;
                            $bankTransferType->companyID            = $currentUser->company->id ?? "";
                            $bankTransferType->senderAccountNumber  = $currentUser->accounts->first()->virtual_accnum ?? "";

                            $bridge       = new BridgeController();
                            $bankTransfer = $bridge->transferToBankAccount($customer, $totalAmount, $transaction);

                            // if ($bankTransfer['status'] == "01") {
                            //     $reversalResult = debitReversal($transaction->id, $transaction->account_id, $transaction->company_id, $transaction->amount, $bankTransferInfo->charge + $bankTransferInfo->amount, $bankTransferInfo->transfer_entity_type);
                            //     $bankAccount['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
                            //     $transaction->transaction_remarks_id = 3;
                            //     $transaction->save();

                            //     logInfo($reversalResult, "Reversal of Transaction Result");

                            //     return $bankTransfer;
                            // }

                            if ($bankTransfer['status'] != "00") {
                                $transaction->transaction_remarks_id = 3;
                                $transaction->save();
                                $bankTransfer['status'] = 0;

                                return $bankTransfer;
                            } else {
                                // debitCustomerAndCompanyWithChargeServiceWallet

                                $debitCustomer = match ($company_debit_bankcode) {

                                    90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge), //7000299948
                                    default
                                          => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge),
                                };
                                //       $debitCustomer = debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $currentUser->company_id, $request->final_amount, $charge, $wbaCharge);

                                $accountId                   = $debitCustomer['account_id'];
                                $customerBalanceBefore       = $debitCustomer['balance_before'];
                                $customerBalanceAfter        = $debitCustomer['balance_after'];
                                $customerChargeBalanceBefore = $debitCustomer['charge_balance_before'];
                                $customerChargeBalanceAfter  = $debitCustomer['charge_balance_after'];

                                $companyBalanceBefore       = $debitCustomer['company_balance_before'];
                                $companyAmount              = $debitCustomer['company_amount'];
                                $companyBalanceAfter        = $debitCustomer['company_balance_after'];
                                $companyChargeBalanceBefore = $debitCustomer['company_charge_balance_before'];
                                $companyChargeBalanceAfter  = $debitCustomer['company_charge_balance_after'];

                                $newdescription = $request->description == "" ? $currentUser->first_name : $request->description;

                                $newdescription = $newdescription . "/" . $request->receipient_name . "/" . $request->destination_account . " - " . $bank->bank_name;

                                $myDescription = "You transferred funds to " . $bankAccount['data']['account_name'] . "/" . $request->destination_account . " / " . $bank->bank_name . " / " . $currentUser->company->company_name;

                                $transaction->description = $myDescription;

                                $transaction->balance_before = $customerBalanceBefore;
                                //  $transaction->amount         = $request->final_amount + $charge;
                                $transaction->amount        = $request->final_amount;
                                $transaction->balance_after = $customerChargeBalanceAfter;

                                $transaction->company_balance_before = $companyBalanceBefore;
                                $transaction->company_amount         = $request->final_amount + $wbaCharge;
                                $transaction->company_balance_after  = $companyChargeBalanceAfter;

                                $transaction->save();

                                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $transaction->company_id);
                                if ($ledger_Settings) {
                                    $ledger_cr = $ledger_Settings->ledger_cr;
                                    $ledger_dr = $ledger_Settings->ledger_dr;
                                } else {
                                    return ['status' => false, 'message' => 'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings !'];
                                }

                                if ($currentUser->company->id == 33) {
                                    $phone            = $currentUser->phone;
                                    $transaction_type = "transaction";
                                    $amount           = $request->final_amount;
                                    $balance          = $debitCustomer['charge_balance_after'];
                                    $account_id       = $currentUser->id;

                                    goldencreditSMSPost($phone, $transaction_type, $amount, $balance, $account_id);
                                } else {
                                    sendSMS($currentUser->id, "Debit Amt: | $request->final_amount, Desc: App/ {$request->description} " . "Acc: {$bankAccount['data']['account_name']} " . "Bal: N {$debitCustomer['charge_balance_after']} " . "Date: " . date('Y-m-d') . " Ref: " . $request->transaction_reference, true, true, 'Debit Transaction');
                                    // $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();
                                }
                            }

                            $transaction->transaction_remarks_id = 2;
                            $transaction->save();

                            $check_transfer_log = Beneficiary::where('accountNo', $request->destination_account)
                                ->whereNull('transaction_id')
                                ->first();

                            if ($check_transfer_log) {

                            } else {
                                $transfer_log            = new Beneficiary;
                                $transfer_log->name      = $request->receipient_name;
                                $transfer_log->bank_id   = $bank->id;
                                $transfer_log->accountNo = $request->destination_account;
                                $transfer_log->user_id   = auth()->user()->id;
                                $transfer_log->save();
                            }

                            $subtext = "Debit Transaction";
                            $reason  = "";
                            $msg     = "Debit Amt:$request->final_amount,<br><br> Desc: App/" . $bankAccount['data']['account_name'] . "$request->description" . "<br><br> Avail Bal:" . $debitCustomer['charge_balance_after'] . "<br><br>Date:" . date('Y-m-d') . " <br><br>Ref: " . $request->transaction_reference;

                            $this->sendTransactionReportEmail($subtext, $msg, $reason, $currentUser);
                            //  spendSaveRecordTransaction($debitCustomer, $transaction);

                            return response()->json([
                                "status"  => true,
                                'message' => "Wallet to Bank Transfer Successful",
                                'data'    => [
                                    'balance' => $debitCustomer['charge_balance_after'],
                                ],
                            ]);
                        } else {
                            return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                        }
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
                }
            } finally {
                $lock->release();
            }
        } else {
            return response()->json(['message' => "Transaction is in progress. Try again."], 400);
        }

    }

#[OA\Get(
    path: "/api/v1/transactions/get-beneficiaries",
    summary: "Get saved bank transfer beneficiaries",
    description: "Retrieves the authenticated user's saved bank beneficiaries for reuse during bank transfers.",
    tags: ["Transfer - Wallet To Bank"],
    security: [["bearerAuth" => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: "Beneficiaries retrieved successfully",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Beneficiary Retieved Successful"),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "accounts",
                                type: "array",
                                items: new OA\Items(
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "name", type: "string", example: "Ada Example"),
                                        new OA\Property(property: "bank_name", type: "string", example: "Example Bank"),
                                        new OA\Property(property: "bank_code", type: "string", example: "000001"),
                                        new OA\Property(property: "accountNo", type: "string", example: "0123456789")
                                    ]
                                )
                            )
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        )
    ]
)]
public function getBeneficiaries(Request $request)
{
    $beneficiaries = Beneficiary::where('user_id', auth()->user()->id)
        ->select('bank_id', 'name', 'accountNo')
        ->distinct()
        ->get();

    if ($beneficiaries != null) {
        return response()->json([
            "status" => true,
            'message' => "Beneficiary Retieved Successful",
            'data' => [
                'accounts' => BenefeciariesResource::collection($beneficiaries),
            ],
        ]);
    }
}

    public function transferToBankAccountUSSD(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "amount"                => "required|numeric|gt:0",
            "transaction_reference" => "required",
            "destination_account"   => "required",
            "receipient_name"       => "required|string",
            "bank_code"             => "required|string",
            "api_key"               => "required|string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        if ($request->api_key != "enter your key") {
            $ra = ["status" => false, "message" => "Invalid API key provided"];
            return response()->json($ra, 400);
        }

        // $newdescription = $request->description == "" ? "From " . \auth()->user()->first_name : $request->description;
        $newdescription = $request->description ?? "From " . \auth()->user()->first_name;
        // $newdescription = auth()->user()->first_name . " " . auth()->user()->last_name . " / " . auth()->user()->company->company_name;
        // $newdescription = "Transfer using USSD";

        $bank = banks::where('bank_code', $request->bank_code)->orwhere('short_code', $request->bank_code)->first();

        if (! $bank) {
            return response()->json(["status" => false, 'message' => "Invalid bank code"], 400);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankTransferType                       = new BankTransferType();
        $bankTransferType->bankCode             = $request->bank_code;
        $bankTransferType->shortBankCode        = $bank->short_code;
        $bankTransferType->accountNumber        = $request->destination_account;
        $bankTransferType->description          = $newdescription;
        $bankTransferType->amount               = $request->amount;
        $bankTransferType->transactionReference = $request->transaction_reference;

        $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

        if ($bankTransfer['status'] == "01") {

            $bankTransfer['message'] = "Unable to restrieve customer name, Funds has been reversed, try again later or use another bank";
            $bankTransfer['status']  = false;

            return response()->json($bankTransfer, 400);
        }

        $bankTransfer['status'] = true;

        return response()->json($bankTransfer);
    }

    public function verifyBankAccountUSSD(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "account_number" => "required",
            "bank_code"      => "required|string",
            "ApiKey"         => "required|string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        if ($request->ApiKey != "enter your key") {
            $ra = ["status" => false, "message" => "Invalid API key provided"];
            return response()->json($ra, 400);
        }

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($request->bank_code, $request->account_number);

        if (! $bankAccount['status']) {
            $bankAccount['status'] = 0;
            return response()->json($bankAccount);
        }

        $bank_name = "";

        if (isset($bankAccount["data"]["bank_code"])) {
            $bank                             = banks::where('bank_code', $bankAccount["data"]["bank_code"])->first();
            $bankAccount['data']['bank_name'] = $bank->bank_name;
        } else {
            $bank_name = $bankAccount['data']['bank_name'];
        }

        $bankAccount['data']['bank_name'] = $bank_name;
        return response()->json($bankAccount);
    }

    #[OA\Post(
    path: "/api/v1/transactions/wallet-transfer",
    summary: "Complete Wallet to Wallet Transfer",
    description: "Finalizes a wallet-to-wallet transfer after the transaction has been initiated and the destination wallet has been verified. The endpoint validates the transaction reference, destination wallet, transaction PIN, available balance, transfer restrictions, and then debits the sender's wallet and credits the destination wallet.",
    tags: ["Transfer - Wallet To Wallet"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: [
                "transaction_reference",
                "destination_account",
                "transaction_pin"
            ],
            properties: [
                new OA\Property(
                    property: "final_amount",
                    type: "number",
                    format: "float",
                    example: 5000.00,
                    description: "Amount to transfer."
                ),
                new OA\Property(
                    property: "transaction_type",
                    type: "string",
                    example: "4",
                    description: "Transaction type identifier."
                ),
                new OA\Property(
                    property: "description",
                    type: "string",
                    example: "Transfer for groceries",
                    description: "Description or narration for the transfer."
                ),
                new OA\Property(
                    property: "destination_account",
                    type: "string",
                    example: "1234567890",
                    description: "Destination wallet account number."
                ),
                new OA\Property(
                    property: "transaction_reference",
                    type: "string",
                    example: "TXN-REF-12345",
                    description: "Transaction reference generated during the transfer initiation process."
                ),
                new OA\Property(
                    property: "transaction_pin",
                    type: "string",
                    format: "password",
                    example: "1234",
                    description: "Transaction PIN used to authorize the transfer."
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Wallet Transfer Successful",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Wallet Transfer Successful"
                    ),
                    new OA\Property(
                        property: "data",
                        properties: [
                            new OA\Property(
                                property: "balance",
                                type: "number",
                                format: "float",
                                example: 45000.00,
                                description: "Sender's wallet balance after the transfer."
                            ),
                        ],
                        type: "object"
                    ),
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 400,
            description: "Wallet transfer failed, invalid transaction reference, insufficient balance, restricted account, invalid destination wallet, or invalid transaction PIN"
        ),
        new OA\Response(
            response: 406,
            description: "Validation failed or required transfer information is missing"
        ),
        new OA\Response(
            response: 409,
            description: "Transaction has already been completed or transfer is being made to the sender's own wallet"
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        )
    ]
)]
    public function walletTransfer(Request $request)
    {
        $description   = $request->description;
        $transactionId = generateTransactionReference();
        $validation    = Validator::make($request->all(), [
            "transaction_reference" => "required|string",
            "destination_account"   => "required|numeric",
            "transaction_pin"       => "required|numeric|gt:0",
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }
        if ($request->final_amount == null) {
            $ra = ["status" => false, "message" => "Please enter amount"];
            return response()->json($ra, 406);
        }
        $wbAccount = wb_account_details::where('virtual_accnum', $request->destination_account)->orWhere('virtual_accnum', ($request->destination_account . "_"))->first();

        /*  if (! $wbAccount) {
            $companyAcc = $this->walletTransferCompany($request, $description, $transactionId);

            $ra = ["status" => false, "message" => "Beneficiary not found!"];
            return response()->json($ra, 406);
        }*/

        if (
            in_array(auth()->user()->company_id, [87, 88, 89])

        ) {
            // $sourceWbAccount = wb_account_details::where('virtual_accnum', $request->source_account)->orWhere('virtual_accnum', ($request->source_account . "_"))->orWhere('user_id', auth()->user()->id)->first();

            $sourceWbAccount = wb_account_details::where('virtual_accnum', $request->source_account)->first() ?? wb_account_details::where('virtual_accnum', $request->source_account . "_")->first() ?? wb_account_details::where('user_id', auth()->user()->id)->first();
            logInfo(json_encode($sourceWbAccount), "walletTransfer sourceWbAccoun");
            if (! $sourceWbAccount) {
                $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
                return response()->json($ra, 406);
            }
        } else {
            $sourceWbAccount = wb_account_details::where(['user_id' => auth()->user()->id, 'currency_id' => $wbAccount->currency_id])->first();
            if (! $sourceWbAccount) {
                $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
                return response()->json($ra, 406);
            }
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount, $sourceWbAccount->id);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else {
                $customerBalance = $this->checkCustomerBalance($request->final_amount, $sourceWbAccount->id);
                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }
                if ($wbAccount) {

                    if ($request->destination_account == \auth()->user()->virtual_account) {
                        return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                    }

                    if (\checkcustomerrestriction($wbAccount->user_id, 1)) {
                        return response()->json(["status" => false, 'message' => "Destinaion account is not allowed to recieve payment at the moment"], 400);
                    }
                    $validateTransferAmount = $this->validateTransfer($request->final_amount);

                    if (! $validateTransferAmount['status']) {
                        return \response()->json($validateTransferAmount, 400);
                    }
                    if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                        return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                    }

                    if (
                        in_array(auth()->user()->company_id, [87, 88, 89]) && ($wbAccount->currency_id != $sourceWbAccount->currency_id)) {
                        $final_amount = $this->getExchangeRates($request, $wbAccount->currency_id, $sourceWbAccount->currency_id);
                    } else {
                        $final_amount = $request->final_amount;
                    }

                    $debitCustomer = \debitCustomerOnly($customerBalance['account_id'], $request->final_amount, \auth()->user());
                    // $debitCustomer = $this->debitCustomer($request);
                    if ($debitCustomer['status']) {
                        $creditCustomer = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
                        // $creditCustomer = \creditCustomerOnly("1", "1000");
                        $myDescription       = "You received payment from " . \auth()->user()->first_name . " - " . $request->description . $sourceWbAccount->virtual_accnum;
                        $debitTransactionRef = \generate_txnID();
                        $ledger_cr           = null;
                        $ledger_dr           = null;
                        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(32, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $creditTransaction  = transactions::create([
                            "balance_before"         => $creditCustomer->balance,
                            "amount"                 => $final_amount,
                            "balance_after"          => $creditCustomer->balance + $final_amount,
                            "account_id"             => $wbAccount->id,
                            "description"            => $myDescription,
                            "branch_id"              => $wbAccount->user->branch_id,
                            "transaction_reference"  => $debitTransactionRef,
                            "parent_transaction_id"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 32,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);
                        $creditCustomer->balance += $final_amount;
                        $creditCustomer->save();
                        $descArray  = explode("-", $myDescription);
                        $this->updateTransactionAsSuccessful($request->transaction_reference, $debitCustomer['balance_after']);
                        // sendSMS(['txnid' => $creditTransaction->id, 'userid' => $wbAccount->id], "Credit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:$creditCustomer->balance Date:" . date('Y-m-d') . " Ref:$creditTransaction->transaction_reference", false, true, 'Credit Transaction');
                        // sendSMS(['txnid' => $transaction->id, 'userid' => auth()->user()->id], "Debit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:" . $debitCustomer["balance_after"] . " Date:" . date('Y-m-d') . " Ref:$transaction->transaction_reference", false, true, 'Debit Transaction');
                        return response()->json(["status" => true, 'message' => "Wallet Transfer Successful", 'data' => ['balance' => $debitCustomer['balance_after']]]);
                    } else {
                        return response()->json(["status" => false, 'message' => "Wallet Transfer failed"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid User Wallet Account"], 400);
                }
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
        }
    }

    public function walletTransferCompany(Request $request, $description, $transactionId)
    {

        $wbAccount = company_wallet::where('vaccount', $request->destination_account)->first();
        logInfo(json_encode($wbAccount), "walletTransferCompany");
        if (! $wbAccount) {
            $ra = ["status" => false, "message" => "Beneficiary not found!"];
            return response()->json($ra, 406);
        }
        $sourceWbAccount = wb_account_details::where(['user_id' => auth()->user()->id, 'currency_id' => 1])->first();

        if (! $sourceWbAccount) {
            $ra = ["status" => false, "message" => "Beneficiary currency doesn not match any of your wallet!"];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount, $sourceWbAccount->id);

        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference, 'amount' => $request->final_amount])->first();
        if ($transaction) {
            if ($transaction->transaction_remarks_id == 2) {
                return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
            } else {
                $customerBalance = $this->checkCustomerBalance($request->final_amount, $sourceWbAccount->id);
                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }
                if ($wbAccount) {

                    if ($request->destination_account == \auth()->user()->virtual_account) {
                        return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                    }
                    if ($wbAccount->id != 14) {
                        return response()->json(["status" => false, 'message' => "Invalid account number"], 409);
                    }

                    $validateTransferAmount = $this->validateTransfer($request->final_amount);

                    if (! $validateTransferAmount['status']) {
                        return \response()->json($validateTransferAmount, 400);
                    }
                    if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                        return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                    }
                    $debitCustomer = \debitCustomerOnly($customerBalance['account_id'], $request->final_amount, \auth()->user());
                    // $debitCustomer = $this->debitCustomer($request);
                    if ($debitCustomer['status']) {
                        $creditCustomer = user_wallet::where('wb_account_detail_id', $wbAccount->id)->first();
                        // $creditCustomer = \creditCustomerOnly("1", "1000");
                        $myDescription       = "You received payment from " . \auth()->user()->first_name . " - " . $request->description . $sourceWbAccount->virtual_accnum;
                        $debitTransactionRef = \generate_txnID();
                        $ledger_cr           = null;
                        $ledger_dr           = null;
                        $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(32, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $creditTransaction  = transactions::create([
                            "balance_before"         => $creditCustomer->balance,
                            "amount"                 => $request->final_amount,
                            "balance_after"          => $creditCustomer->balance + $request->final_amount,
                            "account_id"             => $wbAccount->id,
                            "description"            => $myDescription,
                            "branch_id"              => $wbAccount->user->branch_id,
                            "transaction_reference"  => $debitTransactionRef,
                            "parent_transaction_id"  => $transactionId,
                            "transaction_remarks_id" => 2,
                            "transaction_type_id"    => 32,
                            "credit_debit_type_id"   => 1,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);
                        $creditCustomer->balance += $request->final_amount;
                        $creditCustomer->save();
                        $descArray  = explode("-", $myDescription);
                        $this->updateTransactionAsSuccessful($request->transaction_reference, $debitCustomer['balance_after']);
                        // sendSMS(['txnid' => $creditTransaction->id, 'userid' => $wbAccount->id], "Credit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:$creditCustomer->balance Date:" . date('Y-m-d') . " Ref:$creditTransaction->transaction_reference", false, true, 'Credit Transaction');
                        // sendSMS(['txnid' => $transaction->id, 'userid' => auth()->user()->id], "Debit Amt:$request->final_amount Desc: " . $descArray[0] . " Avail Bal:" . $debitCustomer["balance_after"] . " Date:" . date('Y-m-d') . " Ref:$transaction->transaction_reference", false, true, 'Debit Transaction');
                        return response()->json(["status" => true, 'message' => "Wallet Transfer Successful", 'data' => ['balance' => $debitCustomer['balance_after']]]);
                    } else {
                        return response()->json(["status" => false, 'message' => "Wallet Transfer failed"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid User Wallet Account"], 400);
                }
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Reference"], 400);
        }
    }

#[OA\Post(
    path: "/api/v1/transactions/initiate-transaction",
    summary: "Initiate a transaction",
    description: "Initializes a transaction and returns the transaction reference, applicable charge, transfer amount, and total. Use transaction_type 8 for the bank-transfer flow. Used in both wallet-to-wallet and wallet-to-bank transfers.",
    tags: ["Transfer - Initiate Transaction"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            required: ["final_amount", "transaction_type"],
            properties: [
                new OA\Property(property: "final_amount", type: "number", format: "float", example: 1000),
                new OA\Property(property: "description", type: "string", example: "Transfer to Ada Example"),
                new OA\Property(property: "transaction_type", type: "integer", example: 8)
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Transaction initialized successfully",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Pending Transaction initialized"),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(property: "charge", type: "number", format: "float", example: 10),
                            new OA\Property(property: "transaction_reference", type: "string", example: "TRX-123456"),
                            new OA\Property(property: "transactionTypeId", type: "integer", example: 8),
                            new OA\Property(property: "transfer_amount", type: "number", format: "float", example: 1000),
                            new OA\Property(property: "total", type: "number", format: "float", example: 1010)
                        ]
                    )
                ]
            )
        ),
        new OA\Response(
            response: 400,
            description: "Transaction could not be initialized",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "Insufficient Balance")
                ]
            )
        ),
        new OA\Response(
            response: 406,
            description: "Invalid transaction parameters",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: false),
                    new OA\Property(property: "message", type: "string", example: "The final amount must be greater than zero")
                ]
            )
        )
    ]
)]
    public function initiateTransaction(Request $request)
    {

        $transactionId = generateTransactionReference();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "transaction_type" => "required|numeric|gt:0",
            "description"      => "string",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id); // adding for schedule transfer

        if ($checkAllowedTransaction['status'] != 1) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction, 400);
        }

        $description = $request->description;

        if ($request->final_amount > 0) {

            if ($request->transaction_type == 3) {
                $companyName     = auth()->user()->company->company_name;
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => auth()->user()->company->wallet->balance,
                    "amount"                 => $request->amount,
                    "description"            => "Funding Admin Wallet",
                    "transaction_reference"  => $transactionId,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_remarks_id" => 6,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
                $body   = [
                    "tx_ref"          => $transactionId,
                    "amount"          => $request->amount,
                    "currency"        => auth()->user()->company->currency->code,
                    "redirect_url"    => $this->baseUrl . "/" . "company/" . auth()->user()->company->short_name . "/transaction/verify-fund-wallet",
                    "payment_options" => "card",
                    "customer"        => [
                        "email"       => Auth()->user()->email,
                        "phonenumber" => Auth()->user()->phone,
                        "name"        => Auth()->user()->first_name . ' ' . Auth()->user()->last_name,
                    ],
                    "customizations"  => [
                        "title"       => "Funding WBA Wallet",
                        "description" => "Fund Wired Banking Wallet",
                        "logo"        => "https://wiredbanking.ng/img/logo/logo-dark.png",
                    ],
                ];
                $url = 'https://api.flutterwave.com/v3/payments';

                $jsonData = \postRequest($header, $body, $url);

                // {"status":"success","message":"Hosted Link","data":{"link":"https:\/\/ravemodal-dev.herokuapp.com\/v3\/hosted\/pay\/79d6b15bf0bcb701e77d"}}
                return response()->json(["status" => true, "transactionId" => $transactionId, 'link' => $jsonData['data']['link'], 'message' => " " . generateTransactionReference()]);
            } else if ($request->transaction_type == 5) {

                $description = $description ?? "Company Wallet Transfer";

                if ($request->final_amount > 0) {

                    if (auth()->user()->company->wallet->balance >= $request->final_amount) {

                        $charge          = 0;
                        $ledger_cr       = null;
                        $ledger_dr       = null;
                        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                        if ($ledger_Settings) {
                            $ledger_cr = $ledger_Settings->ledger_cr;
                            $ledger_dr = $ledger_Settings->ledger_dr;
                        }
                        $transaction = transactions::create([
                            "balance_before"         => auth()->user()->company->wallet->balance,
                            "amount"                 => $request->final_amount,
                            "description"            => $description,
                            "transaction_reference"  => $transactionId,
                            "branch_id"              => \auth()->user()->branch_id,
                            "company_id"             => auth()->user()->company_id,
                            "transaction_remarks_id" => 6,
                            "transaction_type_id"    => $request->transaction_type,
                            "credit_debit_type_id"   => 2,
                            "created_date"           => date('Y-m-d'),
                            "created_by"             => auth()->user()->id,
                            "ledger_cr"              => $ledger_cr,
                            "ledger_dr"              => $ledger_dr,
                        ]);

                        return response()->json(
                            [
                                "status"  => true,
                                'message' => "Transaction initialized",
                                'data'    => [
                                    'charge'            => $charge ?? 0.00,
                                    'transactionId'     => $transaction->id,
                                    'transactionTypeId' => $request->transaction_type,
                                    'exchangeAmount'    => $request->final_amount,
                                    'total'             => $request->finalAmount,
                                ],
                            ]
                        );
                    } else {
                        return response()->json(["status" => false, 'message' => "Insufficient Balance"], 400);
                    }
                } else {
                    return response()->json(["status" => false, 'message' => "Invalid Transfer amount"], 400);
                }
            } else if ($request->transaction_type == 8 || $request->transaction_type == 19) {

                $amount      = 0;
                $totalAmount = 0;
                $charge      = 834;

                if ($request->transaction_type == 19) {
                    $description = $description ?? "Mobile Money Transfer";
                    $totalAmount = $request->finalAmount;
                    $amount      = $request->finalAmount;
                } else if ($request->transaction_type == 8) {

                    $validateSavingsProduct = $this->accountProductCharges($request->final_amount);

                    if (! $validateSavingsProduct['status']) {
                        return \response()->json($validateSavingsProduct, 400);
                    }

                    $charge = $validateSavingsProduct['data']['charge'];
                    logInfo($charge, "Charge for Transaction type 8");

                    $amount      = $request->final_amount;
                    $totalAmount = $request->final_amount + $charge;
                    $description = $description ?? "You sent money to $request->receipient_name via Bank Transfer";
                }

                $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount);

                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
                }
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $amount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "branch_id"              => \auth()->user()->branch_id,
                    "company_balance_before" => $customerBalance['company_balance'],
                    "company_amount"         => $amount,
                    "system_description"     => auth()->user()->company->company_name . " - " . auth()->user()->first_name . " sent money to " . $request->receipient_name,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 6,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Pending Transaction initialized",
                        'data'    => [
                            'charge'                => $charge ?? 0.00,
                            'transaction_reference' => $transactionId,
                            'transactionTypeId'     => $request->transaction_type,
                            'transfer_amount'       => $amount,
                            'total'                 => $totalAmount,
                        ],
                    ]
                );

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else if ($request->transaction_type == 4) {
                //  return response()->json("Not available now", 400);
                $amount      = 0;
                $totalAmount = 0;

                $amount      = $request->final_amount;
                $totalAmount = $request->final_amount;
                $name        = isset($request->receipient_name) ? $request->receipient_name : "N/A";
                $description = "You sent payment to $name $request->description / $request->destination_account ";

                $customerBalance = $this->checkCustomerAndCompanyBalance($totalAmount);

                if (! $customerBalance['status']) {
                    return response()->json($customerBalance, 400);
                }

                if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
                    return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
                }

                if ($request->destination_account == \auth()->user()->virtual_account) {
                    return response()->json(["status" => false, 'message' => "Cannot transfer to self"], 409);
                }

                $charge = 0;

                $validateTransferAmount = $this->validateTransfer($request->final_amount);

                if (! $validateTransferAmount['status']) {
                    return \response()->json($validateTransferAmount, 400);
                }
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger($request->transaction_type, \auth()->user()->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $customerBalance['balance'],
                    "amount"                 => $totalAmount,
                    "account_id"             => $customerBalance['account_id'],
                    "description"            => $description,
                    "transaction_reference"  => $transactionId,
                    "company_balance_before" => $customerBalance["company_balance"],
                    "company_amount"         => 0,
                    "company_id"             => auth()->user()->company_id,
                    "transaction_remarks_id" => 6,
                    "branch_id"              => \auth()->user()->branch_id,
                    "transaction_type_id"    => $request->transaction_type,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => auth()->user()->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);

                return response()->json(
                    [
                        "status"  => true,
                        'message' => "Pending Transaction initialized",
                        'data'    => [
                            'charge'                => $charge ?? 0.00,
                            'transaction_reference' => $transactionId,
                            'transactionTypeId'     => $request->transaction_type,
                            'transfer_amount'       => $amount,
                            'total'                 => $totalAmount,
                        ],
                    ]
                );

                // return response()->json(["status" => false, 'message' => "Insufficient Balance ", 'data' => $userWallet->balance], 400);
            } else {
                return response()->json(["status" => false, 'message' => "Invalid Transaction Type"], 400);
            }
        } else {
            return response()->json(["status" => false, 'message' => "Invalid Transaction Amount"], 400);
        }
    }

    public function wovenWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        if ($request->transaction_type != "TRANSFER") {
            return;
        }

        $wovenController = new WovenController();

        $wovenController->insertIntoWebhookTable($request);

        $verifyPaymentResponse = $wovenController->verifyWovenTransaction($request->unique_reference);

        if (! $verifyPaymentResponse['status']) {
            return response()->json($verifyPaymentResponse, 400);
        }

        if ($verifyPaymentResponse['data']['nuban'] != $request->nuban) {
            \logInfo("Verification nuban " . $verifyPaymentResponse['data']['nuban'] . " " . $request->nuban, "Nuban Information");
            return response()->json(['status' => false, 'message' => "Invalid Webhook account number"], 400);
        }

        $wovenController->transactionReference = $request->unique_reference;
        $wovenController->wbaCharge            = getWbaTransferCharges(3, $verifyPaymentResponse['data']['amount']);
        $wovenController->virtualAccount       = $request->nuban;
        $wovenController->amount               = $verifyPaymentResponse['data']['amount'];
        $wovenController->webhookType          = 'woven';
        $wovenController->description          = "You received Funds from $request->source_account_name";
        $wovenController->transactionDate      = date('Y-m-d');
        $wovenController->accountReference     = $request->transaction_date;
        $wovenController->request              = $request;
        $wovenController->fromTempWebhook      = 'false';

        $handleWebhook = $wovenController->handleWebhook();
        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function cyberpayWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {
            $webhookLog = WebhookLog::where('unique_reference', $request->PaymentReference)->first();

            if (! $webhookLog) {
                WebhookLog::create([
                    'webhook_type'     => 'cyberpay',
                    'body'             => json_encode($request->all()),
                    'unique_reference' => $request->PaymentReference,
                ]);
            }

            $mandate = Mandate::where('mandate_code', $request->MandateCode)->first();

            if (! $mandate) {
                return response()->json(['status' => false, "message" => "Invalid Mandate code Provided"]);
            }

            $mandateLog = MandateLog::where('payment_reference', $request->PaymentReference)->first();

            if ($mandateLog) {
                return response()->json(['status' => false, "message" => "Payment Reference already exists"]);
            }

            $cyberpayController = new CyberpayControllelr();

            $verifyPaymentResponse = $cyberpayController->verifyMandateStatus($request->MandateCode);

            if (! $verifyPaymentResponse['status']) {
                return response()->json($verifyPaymentResponse, 400);
            }

            MandateLog::create([
                "mandate_id"           => $mandate->id,
                'payment_reference'    => $request->PaymentReference,
                'marchant_ref'         => $request->MarchantRef,
                'response_description' => $request->ResponseDescription,
                'amount'               => $request->Amount / 100,
                'narration'            => $request->Narration,
                'charge'               => $request->Charge / 100,
                'payment_status'       => $request->PaymentStatus,
                'payment_date'         => $request->PaymentDate,
            ]);

            if ($request->ResponseCode == "00") {

                $companyAmount   = $request->Amount / 100;
                $creditCompany   = creditCompanyWithCharge($mandate->user->company->id, $companyAmount, 0);
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(100, $mandate->user->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_amount"         => $companyAmount,
                    'company_balance_before' => $creditCompany['balance_before'],
                    'company_balance_after'  => $creditCompany['balance_after'],
                    "description"            => "Payment received for mandate deduction $request->MandateCode",
                    "system_description"     => "Payment received for mandate deductin with NIBBS $request->MandateCode",
                    "transaction_reference"  => $request->PaymentReference,
                    "transaction_remarks_id" => 2,
                    "company_id"             => $mandate->user->company_id,
                    "transaction_type_id"    => 100,
                    "credit_debit_type_id"   => 1,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $mandate->user->id,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            }

            return response()->json(['status' => true, "message" => "Cyberpay webhook handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to Handle webhook... Please contact support"], 400);
        }
    }

    public function kudaWebhook(Request $request)
    {

        logInfo($request->all(), "Kuda Web Hook Request body");

        $kudaController = new KudaController();

        if ($request->transactionType != "Credit") {
            return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
        }

        $kudaController->transactionReference = $request->transactionReference;
        $kudaController->wbaCharge            = getWbaTransferCharges(2, $request->amount / 100);
        $kudaController->virtualAccount       = $request->accountNumber;
        $kudaController->amount               = $request->amount;
        $kudaController->webhookType          = 'kuda';
        $kudaController->description          = "You received Funds from $request->senderName";
        $kudaController->transactionDate      = date('Y-m-d');
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = 'false';

        $handleWebhook = $kudaController->handleWebhook();

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200);
    }

    public function kudaWebMaintenanceWebhook(Request $request)
    {

        logInfo($request->all(), "Kuda Web Hook for Maintenance Request body");

        $kudaController = new KudaController();

        if ($request->transactionType != "Credit") {
            return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
        }

        $kudaController->transactionReference = $request->transactionReference;
        $kudaController->wbaCharge            = getWbaTransferCharges(2, $request->amount / 100);
        $kudaController->virtualAccount       = $request->accountNumber;
        $kudaController->amount               = $request->amount;
        $kudaController->webhookType          = 'kuda';
        $kudaController->description          = "You received Funds from $request->senderName";
        $kudaController->transactionDate      = date('Y-m-d');
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = 'false';

        $handleWebhook = $kudaController->processCompanyMaintenanceWebhook();

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200);
    }

    public function providusWebhook(Request $request)
    {
        try {
            if ($request->event == "customer_account_created" || $request->input('data.status') == "pending") {
                return \response()->json(['status' => false, 'message' => 'Transaction is not a Credit Transaction']);
            }
            $providusController = new ProvidusController();

            //remove providus verification
/*
            $verifyWebhook = $providusController->verifyIncomingWebhook($request);

            if (! $verifyWebhook['status']) {
                return response()->json($verifyWebhook, 400);
            }

            $verifyTransaction = $providusController->verifyProvidusTransaction($request->settlementId);

            if (! $verifyTransaction["status"]) {
                return response()->json($verifyTransaction, 400);
            }

            //remove providus verification
*/
            /*    $providusController->transactionReference = $request->settlementId;
            $providusController->wbaCharge            = getWbaTransferCharges(9, $request->transactionAmount);
            $providusController->virtualAccount       = $request->accountNumber;
            $providusController->amount               = $request->transactionAmount;
            $providusController->webhookType          = 'providus';
            $providusController->description          = "You received Funds from $request->sourceAccountName";
            $providusController->transactionDate      = date('Y-m-d');
            $providusController->request              = $request;
            $providusController->fromTempWebhook      = 'false';*/

            $providusController->transactionReference = $request->input('data.reference');
            $providusController->wbaCharge            = getWbaTransferCharges(9, $request->input('data.amount'));
            $providusController->virtualAccount       = $request->input('data.beneficiaryAccountNumber');
            $providusController->amount               = $request->input('data.amount');
            $providusController->webhookType          = 'providus';
            $providusController->description          = "You received Funds from {$request->input('data.originatorAccountName')}";
            $providusController->transactionDate      = date('Y-m-d');
            $providusController->request              = $request;
            $providusController->fromTempWebhook      = 'false';

            $status = $request->input('data.status');
            if ($status == "failed") {
                return \response()->json(['status' => false, 'message' => 'Transaction Failed']);
            }

            $handleWebhook = $providusController->handleWebhook();

            return response()->json($handleWebhook);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Laravel Error");

            return ['responseCode' => "02", "requestSuccessful" => true, 'sessionId' => $request->sessionId, 'responseMessage' => "rejected transaction"];

            // return response()->json(['status' => false, 'message' => "FAiled to handled"], 400);
        }
    }

    public function gtbWebhook(Request $request)
    {
        $gtbController = new GTBController();
        $verifyWebhook = $gtbController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }

        $gtbController->insertIntoWebhookTable($request);

        $gtbController->transactionReference = $request->transaction_reference;
        $gtbController->wbaCharge            = getWbaTransferCharges(8, $request->principal_amount);
        $gtbController->virtualAccount       = $request->virtual_account_number;
        $gtbController->amount               = $request->principal_amount;
        $gtbController->webhookType          = 'gtb';
        $gtbController->description          = "You received Funds from " . $gtbController->getSenderName($request->remarks);
        $gtbController->transactionDate      = $request->transaction_date;
        $gtbController->request              = $request;
        $gtbController->fromTempWebhook      = 'false';

        $handleWebhook = $gtbController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function vpayWebhook(Request $request)
    {

        $vpayController = new VPayController();
        /* $verifyWebhook  = $vpayController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }*/

        $vpayController->transactionReference = $request->reference;
        $vpayController->wbaCharge            = getWbaTransferCharges(7, $request->amount);
        $vpayController->virtualAccount       = $request->account_number;
        $vpayController->amount               = $request->amount;
        $vpayController->webhookType          = 'vpay';
        $vpayController->description          = "You received Funds from $request->originator_account_name";
        $vpayController->transactionDate      = $request->timestamp;
        $vpayController->request              = $request;
        $vpayController->fromTempWebhook      = 'false';

        $handleWebhook = $vpayController->handleVPayWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function fairMoneyWebhook(Request $request)
    {

        $fairmoneyController = new FairMoneyController();
        $verifyWebhook       = $fairmoneyController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        $currentRequest = $request->all();

        if (isset($currentRequest['eventType'])) {
            if ($currentRequest['eventType'] == "Fund Transfer") {
                return response()->json(['status' => false, 'meesage' => 'Event Not virtual account funding'], 400);
            }
        }

        if ($currentRequest['Data']['TransactionStatus'] != "APPROVED") {
            return response()->json(['status' => false, 'meesage' => 'Transaction not approved'], 400);
        }

        if ($currentRequest['EventType'] != "Virtual Account Funding") {
            return response()->json(['status' => false, 'meesage' => 'Event Not virtual account funding'], 400);
        }

        $fairmoneyController->transactionReference = $currentRequest["Data"]["TransactionReference"];
        $fairmoneyController->wbaCharge            = getWbaTransferCharges(34, $currentRequest["Data"]["Amount"]);
        $fairmoneyController->virtualAccount       = $currentRequest["Data"]["CustomerAccountNumber"];
        $fairmoneyController->amount               = $currentRequest["Data"]["Amount"];
        $fairmoneyController->webhookType          = 'fairmoney';
        $fairmoneyController->description          = $currentRequest["Data"]["Narration"];
        $fairmoneyController->transactionDate      = $currentRequest["Data"]["TransactionDate"];
        $fairmoneyController->request              = $request;
        $fairmoneyController->fromTempWebhook      = 'false';

        $handleWebhook = $fairmoneyController->handleWebhook();

        logInfo($handleWebhook, "Handle webhhook result");

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function baniWebhook(Request $request)
    {

        $baniController = new BaniController();
        $verifyWebhook  = $baniController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        // array (
        //     'event' => 'payin_bank_transfer',
        //     'data' =>
        //     array (
        //       'pay_ref' => 'BNPay-1gnhbs85zehwne6maqmxvg3jb86jt3',
        //       'pay_ext_ref' => NULL,
        //       'holder_first_name' => 'Godwin',
        //       'holder_last_name' => 'Famous',
        //       'custom_data' => NULL,
        //       'pay_amount' => 1000.0,
        //       'pay_method' => 'bank_transfer',
        //       'holder_phone' => NULL,
        //       'holder_phone_carrier' => NULL,
        //       'order_details' => NULL,
        //       'holder_currency' => 'NGN',
        //       'holder_country_code' => 'NG',
        //       'pay_status' => 'activated',
        //       'holder_account_number' => '6151196689',
        //       'pub_date' => '2023-09-21T09:33:02.298556Z',
        //       'modified_date' => '2023-09-21T09:34:21.964342Z',
        //       'holder_bank_name' => '9Payment Service Bank',
        //       'customer_ref' => 'MC-24734437117661638951',
        //       'merch_currency' => 'NGN',
        //       'merch_amount' => 995.0,
        //       'pay_amount_collected' => 1000.0,
        //       'pay_amount_outstanding' => '0.00',
        //       'holder_account_type' => 'permanent',
        //       'transaction_ref' => 'dyn4nnk6zc5a35pwhr02xvqa1',
        //       'source_account_name' => 'WBALITE-SHEMA EMMANUEL / SETTLEMENT',
        //       'actual_amount_paid' => 1000.0,
        //       'narration' => 'Inflow from WBALITE-SHEMA EMMANUEL / SETTLEMENT|SHEMA EMMANUEL   SETTLEMENT',
        //     ),
        //   ),

        $currentRequest = $request->all();

        if ($currentRequest['event'] != "payin_bank_transfer") {
            return response()->json(['status' => false, 'meesage' => 'Webhook not of bank transfer type'], 400);
        }

        if ($currentRequest['data']['pay_method'] != "bank_transfer") {
            return response()->json(['status' => false, 'meesage' => 'Payment method is not bank transfer'], 400);
        }

        $baniController->transactionReference = $currentRequest["data"]["transaction_ref"];
        $baniController->wbaCharge            = getWbaTransferCharges(35, $currentRequest["data"]["pay_amount"]);
        $baniController->virtualAccount       = $currentRequest["data"]["holder_account_number"];
        $baniController->amount               = $currentRequest["data"]["pay_amount"];
        $baniController->webhookType          = 'bani';
        $baniController->description          = $currentRequest['data']['narration'];
        $baniController->transactionDate      = $currentRequest["data"]["pub_date"];
        $baniController->request              = $request;
        $baniController->fromTempWebhook      = 'false';

        $handleWebhook = $baniController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function amuchaWebhook(Request $request)
    {
        $currentRequest = $request->all();

        // if (!isset($currentRequest["data"]["transaction"]['transactionId'])) {
        //     return response()->json(['status' => true, 'message' => 'testing successful']);
        // }

        $baniController = new AmuchaController();
        // $verifyWebhook = $baniController->verifyIncomingWebhook($request);

        // if (!$verifyWebhook['status']) {
        //     return response()->json($verifyWebhook, 400);
        // }

        // if ($currentRequest['event_type'] != "payment_success") {
        //     return response()->json(['status' => false, 'meesage' => 'Webhoook was not successful'], 400);
        // }

        // if (!isset($currentRequest["data"]["transaction"]["aliasAccountNumber"])) {
        //     $forwardWebhookResponse = forwardRequestToWirelesspay('user/card/webhook', $request->all());

        //     logInfo($forwardWebhookResponse, "Response of forwarded Amucha Webhook");
        //     return $forwardWebhookResponse;
        // }

        $baniController->transactionReference = $currentRequest["data"]["transaction"]['transactionId'];
        $baniController->wbaCharge            = getWbaTransferCharges(37, $currentRequest["data"]["transaction"]["transactionAmount"]);
        $baniController->virtualAccount       = $currentRequest["data"]["transaction"]["aliasAccountNumber"];
        $baniController->amount               = $currentRequest["data"]["transaction"]["transactionAmount"];
        $baniController->webhookType          = 'amucha';
        $baniController->description          = "you received payment from " . ($currentRequest["data"]["customer"]["billerId"] ?? $currentRequest["data"]["customer"]["senderName"] ?? 'Unknown');
        $baniController->transactionDate      = $currentRequest["data"]["transaction"]["time"];
        $baniController->request              = $request;
        $baniController->fromTempWebhook      = 'false';

        $handleWebhook = $baniController->handleWebhook();

        logInfo($handleWebhook, "Amucha Webhook response");

        return response()->json($handleWebhook, isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 400);
    }

    public function verifyCardOrderTransaction(Request $request, $transactionReference)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->verifyTransaction($transactionReference);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function transferFunds(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->transferFunds($request->amount, $request->receiver_account_id, $request->merchant_ref, $request->account_id);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function assignTerminal(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->assignTerminal($request->serial_number, $request->terminal_label, $request->account_id);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function unassignTerminal(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->unassignTerminal($request->serial_number, $request->terminal_label);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }

    public function registerCustomer(Request $request)
    {
        $currentRequest = $request->all();

        $amuchaController = new AmuchaController();
        $verifyWebhook    = $amuchaController->registerCustomer($request->account_ref, $request->phone_number, $request->email, $request->bvn, $request->pin, $request->account_name, $request->call_back_url, $request->expiry_date);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }
        return response()->json($verifyWebhook);
    }


public function safeHavenWebhook(Request $request)
{
    $safeHavenController = new SafehavenController();

    logInfo($request->all(), "SafehavenWeb Hook Request body");

    $currentRequest = $request->all();

    /*
    |--------------------------------------------------------------------------
    | Validate webhook type
    |--------------------------------------------------------------------------
    */
    if (!isset($currentRequest['type'])) {
        return response()->json([
            'status' => false,
            'message' => 'Webhook type is missing',
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | SafeHaven Identity Credit Check Webhook
    |--------------------------------------------------------------------------
    |
    | This is sent after a BVN/NIN identity check.
    |
    | Example:
    | type = identityCreditCheck
    |
    */
    if ($currentRequest['type'] === 'identityCreditCheck') {

        logInfo(
            $currentRequest,
            "Safehaven Identity Credit Check Webhook"
        );

        $identityData = $currentRequest['data'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | You can store/update the identity verification here if needed.
        |--------------------------------------------------------------------------
        |
        | Important:
        | Do NOT treat this webhook alone as "Account Verified Successfully"
        | because your SafeHaven response is still returning:
        |
        | phoneAndDobVerified = false
        |
        | The user still needs to complete the OTP/phone + DOB verification
        | flow.
        |
        */

        return response()->json([
            'status' => true,
            'message' => 'Identity credit check webhook received',
        ], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | SafeHaven Transfer Webhook
    |--------------------------------------------------------------------------
    |
    | Keep your existing transfer processing here.
    |
    */
    if ($currentRequest['type'] === 'transfer') {

        if (
            !isset($currentRequest['data']['paymentReference']) ||
            !isset($currentRequest['data']['amount']) ||
            !isset($currentRequest['data']['creditAccountNumber'])
        ) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid transfer webhook payload',
            ], 400);
        }

        $safeHavenController->transactionReference =
            $currentRequest['data']['paymentReference'];

        $safeHavenController->wbaCharge =
            getWbaTransferCharges(
                38,
                $currentRequest['data']['amount']
            );

        $safeHavenController->virtualAccount =
            $currentRequest['data']['creditAccountNumber'];

        $safeHavenController->amount =
            $currentRequest['data']['amount'];

        $safeHavenController->webhookType = 'safehaven';

        $safeHavenController->senderName =
            $currentRequest['data']['debitAccountName'];

        $safeHavenController->description =
            "You received Funds from " .
            $currentRequest['data']['debitAccountName'];

        $safeHavenController->transactionDate =
            $currentRequest['data']['createdAt'];

        $safeHavenController->request = $request;

        $safeHavenController->fromTempWebhook = 'false';

        $handleWebhook = $safeHavenController->handleWebhook();

        $httpCode = !isset($handleWebhook['httpCode'])
            ? 400
            : 200;

        return response()->json(
            $handleWebhook,
            $httpCode
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Unsupported webhook type
    |--------------------------------------------------------------------------
    */
    return response()->json([
        'status' => false,
        'message' => 'Unsupported SafeHaven webhook type',
    ], 400);
}

    public function safeHavenCardMaintenanceWebhook(Request $request)
    {

        $safeHavenController = new SafehavenController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        logInfo($request->all(), "SafehavenWeb Hook Request body");

        $currentRequest = $request->all();

        if ($currentRequest['type'] != "transfer") {
            return response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $safeHavenController->transactionReference = $currentRequest['data']['paymentReference'];
        $safeHavenController->wbaCharge            = getWbaTransferCharges(38, $currentRequest['data']['amount']);
        $safeHavenController->virtualAccount       = $currentRequest['data']['creditAccountNumber'];
        $safeHavenController->amount               = $currentRequest['data']['amount'];
        $safeHavenController->webhookType          = 'safehaven';
        $safeHavenController->senderName           = $currentRequest['data']['debitAccountName'];
        $safeHavenController->description          = "You received Funds from" . $currentRequest['data']['debitAccountName'];
        $safeHavenController->transactionDate      = $currentRequest['data']['createdAt'];
        $safeHavenController->request              = $request;
        $safeHavenController->fromTempWebhook      = 'false';

        $handleWebhook = $safeHavenController->processCompanyCardSudoMaintenanceWebhook();

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        return response()->json($handleWebhook, $httpCode);
    }

    public function safeHavenSudoWebhook(Request $request)
    {

        $safeHavenController = new SafehavenController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $safeHavenController->handleSudoWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Sudo result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function mapleradWebhook(Request $request)
    {

        $mapleradController = new MapleradController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $mapleradController->handleMapleradWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook maplerad result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function fincraWebhook(Request $request)
    {
        $fincraController = new FincraController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $fincraController->handleFincraWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook fincra result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function strowalletWebhook()
    {
        $stroWalletController = new StroWalletController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $stroWalletController->handleStroWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook StroWallet result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);
    }

    public function errandPaycheckBalance(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "serial_number"    => 'required|string',
            "transaction_type" => 'required|string',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $terminal = AgentTerminal::where('terminal_code', $request->serial_number)->first();

        if (! $terminal) {
            $terminalResponse = forwardRequestToAM("services-webhook", $request->all());
            return $terminalResponse;
        }

        if ($request->transaction_type != "inward") {
            if ($request->pin != "") {
                if ($request->pin == $terminal->agent->pin) {
                    return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
                } else {
                    return \response()->json(['is_pin_valid' => false, "message" => 'Invalid transaction pin']);
                }
            }
        }
        return \response()->json(['is_pin_valid' => true, 'balance' => $terminal->balance, 'agent_status' => $terminal->status != 1 ? "InActive" : "Active"]);
    }

    public function transferPosFunds(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "serial_number"      => 'required|string',
            "transaction_amount" => 'required|numeric|gt:0',
        ]);

        if ($validation->fails()) {
            return response()->json(['status' => false, 'message' => $validation->errors()->all()[0]]);
        }

        $user = auth()->user();

        $userPos = AgentTerminal::where('terminal_code', $request->serial_number)->first();

        if (! $userPos) {
            return response()->json(['status' => false, 'message' => 'Invalid POS Serial number']);
        }

        if ($userPos->user_id != $user->id) {
            return response()->json(['status' => false, 'message' => 'POS Serial number does to belong to the user']);
        }

        $transactionRef = generate_txnID();

        if ($userPos->balance < $request->transaction_amount) {
            return response()->json(['status' => false, 'message' => 'Insufficient POS balance']);
        }

        $creditCustomerAndCompany = creditCustomerAndCompanyPOSAmountWithCharge($user->accounts[0]->id, $userPos->terminal_code, auth()->user()->company_id, $request->transaction_amount, 0, 0, $user);

        logInfo($creditCustomerAndCompany, "Credit Customer and Company POS");
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(100, $user->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $creditCustomerAndCompany['pos_balance_before'],
            "amount"                 => $request->transaction_amount,
            "balance_after"          => $creditCustomerAndCompany['pos_balance_after'],
            "description"            => "POS Wallet Debit - $userPos->terminal_code to credit Savings wallet",
            "transaction_reference"  => $transactionRef,
            "account_id"             => $user->accounts[0]->id,
            "company_balance_before" => 0,
            "company_amount"         => 0,
            "company_balance_after"  => 0,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 69,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        $companyTransactionRef = generate_txnID();
        $ledger_cr             = null;
        $ledger_dr             = null;
        $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(100, auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }
        $transaction = transactions::create([
            "balance_before"         => $creditCustomerAndCompany['balance_before'],
            "amount"                 => $request->transaction_amount,
            "balance_after"          => $creditCustomerAndCompany['balance_after'],
            "description"            => "Received payment from POS Terminal - $userPos->terminal_code. the sum of $request->transaction_amount",
            "transaction_reference"  => $companyTransactionRef,
            "account_id"             => $user->accounts[0]->id,
            "company_id"             => auth()->user()->company_id,
            "company_balance_before" => $creditCustomerAndCompany['company_balance_before'],
            "company_amount"         => $request->transaction_amount,
            "company_balance_after"  => $creditCustomerAndCompany['company_balance_after'],
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 74,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,
        ]);

        sendSMS($user->accounts[0]->id, "Credit Amt:$request->transaction_amount TXNcharge:0 Desc: POS Wallet Credit - $userPos->terminal_code Avail Bal:$userPos->balance Date:" . date('Y-m-d') . " Ref:$transactionRef", false, true, 'Credit Transaction');

        return response()->json(['status' => true, 'message' => 'POS Terminal Debited Successfully']);
    }

    public function newTransaction(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->resdTransaction($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function retryTransaction(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->retryTransaction($tryluxPayload);

        return response()->json($response, $tryluxPayload['status'] ? 200 : 400);
    }

    public function cardBalance(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->pan            = $request->pan;
        $tryluxPayload->stan           = $request->stan;
        $tryluxPayload->amount         = $request->amount;
        $tryluxPayload->rrn            = $request->rrn;
        $tryluxPayload->iccData        = $request->icc_data;
        $tryluxPayload->trackData      = $request->track_data;
        $tryluxPayload->postDataCode   = $request->post_data_code;
        $tryluxPayload->cardExpDate    = $request->card_exp_date;
        $tryluxPayload->sequenceNumber = $request->sequence_number;
        $tryluxPayload->pin            = $request->pin;
        $tryluxPayload->terminalId     = $request->terminal_id;
        $tryluxPayload->acquiringCode  = $request->acquiring_code;
        $tryluxPayload->accountType    = $request->account_type;

        $response = $tryluxController->cardBalance($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function networkManagement(Request $request)
    {
        $tryluxController = new TryluxController();

        $tryluxPayload = new TryluxPayload();

        $tryluxPayload->stan            = $request->stan;
        $tryluxPayload->serialNumber    = $request->serial_number;
        $tryluxPayload->onlyAccountInfo = $request->only_account_info;

        $response = $tryluxController->resdNetworkManagement($tryluxPayload);

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    public function getPosTerminals(Request $request)
    {
        $terminal = AgentTerminal::where('user_id', auth()->user()->id)->get();

        if (count($terminal) == 0) {
            return \response()->json(['status' => false, 'message' => "No Terminals found for This user"], 400);
        }

        return \response()->json(['status' => true, 'message' => 'Terminals fetched successfully', 'data' => $terminal]);
    }

    public function servicesWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        // return response()->json(['status' => false, 'message' => 'Service Disabled Temporarily']);

        $currentRequest = $request->all();

        try {

            // {
            //     "StatusCode": "00",
            //     "StatusDescription": "Successful",
            //     "SerialNumber": "82912785",
            //     "Amount": 10.00,
            //     "TransactionReference": "384345838903",
            //     "Currency": "NGN",
            //     "TransactionDate": "07/03/2022",
            //     "TransactionTime": "10:44:03 AM",
            //     "TransactionType": "CashOut",
            //     "ServiceCode": "C01",
            //     "Fee": 0.05,
            //     "PostingType": "Cr",
            //     "AdditionalDetails": {
            //         "MaskedPAN": "506110xxxxxxxxx0460",
            //         "Stan": "838903",
            //         "CardExpiry": "2404",
            //         "TerminalID": "2033CXFD"
            //     }
            // }

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if ($webhook) {
                return response()->json(['status' => false, 'responseMessage' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {
                return response()->json(['status' => false, 'message' => "Invalid Terminal Serial Number"], 400);
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company_id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            $customerCharge = 0;
            $companyCharge  = 0;

            if ($request->PostingType == "Cr") {

                // Get Erandpay POS WBA Charges
                // Get amount to be deducted from company
                $companyCharge = getWbaTransferCharges(11, $request->Amount);

                $companySettings = company_settings::where('company_id', $companyId)->first();

                $customerCharge  = $companySettings->additional_pos_charge_percent * $request->Amount;
                $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                $customerCharge += $companyCharge;
                $customerAmount -= $customerCharge;
            }

            if ($request->TransactionType == "FundTransfer") {
                $customerCharge    = getWbaTransferCharges(1, $request->Amount);
                $companyCommission = 2;
            }

            $transactionType = 0;

            $transactionType = $request->TransactionType == "CashOut" ? 63 : ($request->TransactionType == "FundTransfer" ? 65 : 64);

            $description        = "";
            $companyDescription = "";

            if ($request->TransactionType == "CashOut") {
                $description        = "You recieved payment from POS Terminal: $terminal->terminal_code";
                $companyDescription = "You recieved commission from POS Terminal - $terminal->terminal_code payment received";
                $systemDescription  = "Payment received from POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                $systemDescription  = "Card Details Masked PAN: " . $currentRequest["AdditionalDetails"]["MaskedPAN"] . " Exp Date: " . $currentRequest["AdditionalDetails"]["CardExpiry"];
                $systemDescription .= " TerminalID " . $currentRequest["AdditionalDetails"]["TerminalID"];
            } else if ($request->TransactionType == "FundTransfer") {
                $description        = "Funds transfer via termial: $terminal->terminal_code";
                $companyDescription = "Funds transfer commission via terminal - $terminal->terminal_code";
                $systemDescription  = "Funds Transferred via POS Terminal: $terminal->terminal_code, Amount: $originalAmount";

                $systemDescription  = "Terminal ID: $terminal->terminal_code, Card Details Destination Acc. Name: " . $currentRequest["AdditionalDetails"]["DestinationAccountName"] . " Destination Account Number: " . $currentRequest["AdditionalDetails"]["DestinationAccountNumber"];
                $systemDescription .= " Destination Bank Name " . $currentRequest["AdditionalDetails"]["DestinationBankName"];
            } else if ($request->TransactionType == "BillsPayment") {
                $customerCharge        = 0;
                $initialCustomerCharge = 0;
                $description           = "You paid bills via termial: $terminal->terminal_code";
                $companyCommission     = 0;

                $systemDescription  = "You purchased " . $currentRequest["AdditionalDetails"]["BillCategory"] . " from " . $currentRequest["AdditionalDetails"]["BillService"];
                $systemDescription .= " for " . $currentRequest["AdditionalDetails"]["Beneficiary"];
            }

            $creditCustomer = $request->PostingType == "Cr" ? creditPOSTerminal($terminal->terminal_code, $originalAmount, $customerCharge, $initialCustomerCharge, $companyId)
                : \debitCustomerAndCompanyPOSWithChargeAndCommission($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCommission);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transactionType, $customer->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([
                "balance_before"         => $creditCustomer['pos_balance_before'],
                "amount"                 => $creditCustomer['pos_amount'],
                "balance_after"          => $creditCustomer['pos_balance_after'],
                "company_amount"         => 0,
                'company_balance_before' => 0,
                'company_balance_after'  => 0,
                "description"            => $description,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "system_description"     => $systemDescription,
                "transaction_reference"  => $request->TransactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => $transactionType,
                "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $customer->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ]);

            if ($creditCustomer['company_amount'] > 0) {

                $chargeTransactionRef = generate_txnID();
                $ledger_cr            = null;
                $ledger_dr            = null;
                $ledger_Settings      = get_CR_DR_TXN_Type_Ledger(70, $customer->company_id);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => 0,
                    "amount"                 => 0,
                    "balance_after"          => 0,
                    "company_amount"         => $creditCustomer['company_amount'],
                    'company_balance_before' => $creditCustomer['company_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_balance_after'],
                    "description"            => $companyDescription,
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "transaction_reference"  => $chargeTransactionRef,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 70,
                    "credit_debit_type_id"   => $request->PostingType == "Cr" ? 1 : 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => $customer->id,
                    "ledger_dr"              => $ledger_dr,
                    "ledger_cr"              => $ledger_cr,
                ]);
            }

            if ($request->TransactionType != "FundTransfer") {

                if ($customerCharge > 0) {
                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(76, $customer->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['pos_charge_balance_before'],
                        "amount"                 => $customerCharge,
                        "company_amount"         => 0,
                        "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                        'company_balance_before' => 0,
                        'company_balance_after'  => 0,
                        "description"            => "Charge from POS payment received from Terminal - $terminal->terminal_code",
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 76,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customer->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);
                }
            }

            $balanceAfter = $creditCustomer['pos_charge_balance_after'];
            $desc         = "You recieved payment from POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Credit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->settlementId";

            // $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction', '', 0, false);

            $message = $request->PostingType == "Cr" ? "POS Credited successfully" : "POS Debited successfully";

            return response()->json(['status' => true, 'message' => $message]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function errandPayFundsTransferWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {

            // {
            //     "StatusCode": "00",
            //     "StatusDescription": "Successful",
            //     "SerialNumber": "82912785",
            //     "Amount": 10.00,
            //     "TransactionReference": "384345838903",
            //     "Currency": "NGN",
            //     "TransactionDate": "07/03/2022",
            //     "TransactionTime": "10:44:03 AM",
            //     "TransactionType": "CashOut",
            //     "ServiceCode": "C01",
            //     "Fee": 0.05,
            //     "PostingType": "Cr",
            //     "AdditionalDetails": {
            //         "MaskedPAN": "506110xxxxxxxxx0460",
            //         "Stan": "838903",
            //         "CardExpiry": "2404",
            //         "TerminalID": "2033CXFD"
            //     }
            // }

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if ($webhook) {
                return response()->json(['status' => false, 'message' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {
                return response()->json(['status' => false, 'message' => "Invalid Terminal Id"], 400);
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company->id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            // Get Errandpay POS WBA Charges
            // Get amount to be deducted from company
            $companyCharge = getWbaTransferCharges(11, $request->Amount);

            $companySettings = company_settings::where('company_id', $companyId)->first();

            $customerCharge  = $companySettings->additional_pos_charge_percent * 0.01 * $request->Amount;
            $customerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
            $customerAmount -= $customerCharge;

            $creditCustomer  = \creditCustomerAndCompanyPOSAmountWithCharge($accountId, $terminal->terminal_code, $companyId, $originalAmount, $customerCharge, $companyCharge);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(68, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([
                "balance_before"         => $creditCustomer['balance_before'],
                "amount"                 => $originalAmount,
                "balance_after"          => $creditCustomer['balance_after'],
                "description"            => "You Received funds via POS Terminal",
                "company_id"             => $companyId,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "transaction_reference"  => $request->TransactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 68,
                "credit_debit_type_id"   => 1,
                "created_date"           => date('Y-m-d'),
                "created_by"             => 1,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $chargeTransaction = transactions::create([
                "balance_before"         => $creditCustomer['charge_balance_before'],
                "amount"                 => $customerCharge,
                "company_amount"         => $companyCharge,
                "balance_after"          => $creditCustomer['charge_balance_after'],
                'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                "description"            => "Charge from payment received",
                "company_id"             => $companyId,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "parent_transaction_id"  => $transaction->id,
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 27,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => 1,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $balanceAfter = $creditCustomer['charge_balance_after'];
            $desc         = "You Received funds via POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Credit Amt:" . $creditCustomer['charge_balance_after'] . " Desc:$desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->TransactionReference";

            $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction', '', 0, false);

            return response()->json(['status' => true, 'message' => "Webhook Handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function errandPayBillsPaymentWebhook(Request $request)
    {
        $transactionId = generateTransactionReference();

        try {

            // {
            //     "StatusCode": "00",
            //     "StatusDescription": "Successful",
            //     "SerialNumber": "82912785",
            //     "Amount": 10.00,
            //     "TransactionReference": "384345838903",
            //     "Currency": "NGN",
            //     "TransactionDate": "07/03/2022",
            //     "TransactionTime": "10:44:03 AM",
            //     "TransactionType": "CashOut",
            //     "ServiceCode": "C01",
            //     "Fee": 0.05,
            //     "PostingType": "Cr",
            //     "AdditionalDetails": {
            //         "MaskedPAN": "506110xxxxxxxxx0460",
            //         "Stan": "838903",
            //         "CardExpiry": "2404",
            //         "TerminalID": "2033CXFD"
            //     }
            // }

            $webhook = transactions::where('transaction_reference', $request->TransactionReference)->first();

            if ($webhook) {
                return response()->json(['status' => false, 'message' => "Duplicate transaction"]);
            }

            WebhookLog::create([
                'webhook_type' => 'errandpay',
                'body'         => json_encode($request->all()),
            ]);

            $terminal = AgentTerminal::where('terminal_code', $request->SerialNumber)->first();

            if (! $terminal) {
                return response()->json(['status' => false, 'message' => "Invalid Terminal Id"], 400);
            }

            $originalAmount = $request->Amount;

            $customerAmount = $originalAmount;

            $customer  = $terminal->agent;
            $companyId = $terminal->agent->company->id;
            $branchId  = $terminal->agent->branch->id;
            $accountId = $terminal->agent->wb_account_details[0]->id;

            // Get Errandpay WBA Charges
            // Get amount to be deducted from company
            $companyCharge = getWbaTransferCharges(11, $request->Amount);

            $companySettings = company_settings::where('company_id', $companyId)->first();

            $customerCharge = 0;

            if ($companySettings->who_to_pay_incoming_transfer_charge == 1) {
                if ($companySettings->incoming_transfer_charge_state == 0) {
                    $customerCharge  = $companySettings->incoming_transfer_charge_amount;
                    $customerAmount -= $customerCharge;
                } else {
                    $customerCharge  = $companySettings->incoming_transfer_charge_percentage * 0.01 * $request->Amount;
                    $customerCharge  = $customerCharge > $companySettings->incoming_transfer_charge_capped_at ? $companySettings->incoming_transfer_charge_capped_at : $customerCharge;
                    $customerAmount -= $customerCharge;
                }
            }

            $creditCustomer   = \debitCustomerAndCompanyWithCharge($accountId, $companyId, $originalAmount, $customerCharge, $companyCharge);
            $ledger_cr        = null;
            $ledger_dr        = null;
            $ledger_Settings  = get_CR_DR_TXN_Type_Ledger(6, $companyId);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction  = transactions::create([
                "balance_before"         => $creditCustomer['balance_before'],
                "amount"                 => $originalAmount,
                "company_amount"         => $originalAmount,
                "balance_after"          => $creditCustomer['balance_after'],
                'company_balance_before' => $creditCustomer['company_balance_before'],
                'company_balance_after'  => $creditCustomer['company_balance_after'],
                "description"            => "You Paid Bill using POS Terminal",
                "company_id"             => $companyId,
                "branch_id"              => $branchId,
                "account_id"             => $accountId,
                "transaction_reference"  => $request->TransactionReference,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 6,
                "credit_debit_type_id"   => 1,
                "created_date"           => date('Y-m-d'),
                "created_by"             => 1,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            if ($customerCharge > 0) {
                $ledger_cr       = null;
                $ledger_dr       = null;
                $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
                if ($ledger_Settings) {
                    $ledger_cr = $ledger_Settings->ledger_cr;
                    $ledger_dr = $ledger_Settings->ledger_dr;
                }
                $transaction = transactions::create([
                    "balance_before"         => $creditCustomer['charge_balance_before'],
                    "amount"                 => $customerCharge,
                    "company_amount"         => $companyCharge,
                    "balance_after"          => $creditCustomer['charge_balance_after'],
                    'company_balance_before' => $creditCustomer['company_charge_balance_before'],
                    'company_balance_after'  => $creditCustomer['company_charge_balance_after'],
                    "description"            => "Charge from payment received",
                    "company_id"             => $companyId,
                    "branch_id"              => $branchId,
                    "account_id"             => $accountId,
                    "parent_transaction_id"  => $transaction->id,
                    "transaction_reference"  => $transactionId,
                    "transaction_remarks_id" => 2,
                    "transaction_type_id"    => 27,
                    "credit_debit_type_id"   => 2,
                    "created_date"           => date('Y-m-d'),
                    "created_by"             => 1,
                    "ledger_cr"              => $ledger_cr,
                    "ledger_dr"              => $ledger_dr,
                ]);
            } else {
                if ($companyCharge > 0) {
                    $ledger_cr       = null;
                    $ledger_dr       = null;
                    $ledger_Settings = get_CR_DR_TXN_Type_Ledger(27, $companyId);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $transaction = transactions::create([
                        "balance_before"         => $creditCustomer['company_charge_balance_before'],
                        "company_balance_before" => $creditCustomer['company_charge_balance_before'],
                        "amount"                 => $companyCharge,
                        "company_amount"         => $companyCharge,
                        "balance_after"          => $creditCustomer['company_charge_balance_after'],
                        "company_balance_after"  => $creditCustomer['company_charge_balance_after'],
                        "description"            => "Charge from Terminal payment",
                        "company_id"             => $companyId,
                        "branch_id"              => $branchId,
                        "account_id"             => $accountId,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 27,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => 1,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);
                }
            }

            $balanceAfter = $creditCustomer['charge_balance_after'];
            $desc         = "You recieved payment from POS Terminal";
            $currentDate  = Carbon::now();

            $message = "Debit Amt:$originalAmount Desc: $desc Avail Bal:$balanceAfter Date:$currentDate Ref:$request->settlementId";

            $smsstate = sendSMS($accountId, $message, true, true, 'Credit Transaction');

            return response()->json(['status' => true, 'message' => "Webhook Handled successfully"]);
        } catch (Exception $e) {
            logInfo($e->getMessage(), "lavel error");
            return response()->json(['status' => false, 'message' => "Failed to handle webhook"], 400);
        }
    }

    public function monnifyWebhook(Request $request)
    {

        $monnifyController = new MonnifyController();
        $verifyWebhook     = $monnifyController->verifyIncomingWebhook($request);

        if (! $verifyWebhook['status']) {
            response()->json($verifyWebhook, 400);
        }

        //Verify Transaction status from monnify
        $transactionStatus = $monnifyController->getTransactionStatus($request->transactionReference);
        if ($transactionStatus['status'] == "00") {
            $transactionStatus['status'] = false;
            return response()->json($transactionStatus, 400);
        }

        $monnifyController->transactionReference = $request->reference;
        $monnifyController->wbaCharge            = getWbaTransferCharges(10, $transactionStatus['data']['amount_paid']);
        $monnifyController->virtualAccount       = $request->account_number;
        $monnifyController->amount               = $transactionStatus['data']['amount_paid'];
        $monnifyController->webhookType          = 'monnify';
        $monnifyController->description          = "You received Funds from " . $request->accountDetails['accountName'];
        $monnifyController->transactionDate      = date('Y-m-d');
        $monnifyController->accountReference     = $request->product['reference'];
        $monnifyController->request              = $request;
        $monnifyController->fromTempWebhook      = 'false';

        $handleWebhook = $monnifyController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function cgateWebhook(Request $request)
    {

        $cgateController = new CgateController();
        $verifyWebhook   = $cgateController->verifyIncomingWebhook($request);

        $newResponse = $verifyWebhook;

        if ($verifyWebhook['status'] != "00") {
            return response()->json(['ResponseCode' => "01", 'ResponseMessage' => $newResponse['message']], 400);
        }

        //Verify Transaction status from monnify
        $transactionStatus = $cgateController->verifyTransaction($request->paymentReference);
        // if (!$transactionStatus['status'] != '00') {
        //     return response()->json($transactionStatus, 400);
        // }

        $cgateController->transactionReference = $request->paymentReference;
        $cgateController->wbaCharge            = getWbaTransferCharges(28, $request->amount);
        $cgateController->virtualAccount       = $request->customerRef;
        $cgateController->amount               = $request->amount;
        $cgateController->webhookType          = 'cgate';
        $cgateController->description          = "You received the sum of $request->amount via USSD, Sender: " . $request->mobileNumber . " with reference $request->paymentReference";
        $cgateController->transactionDate      = $request->transactionDate;
        $cgateController->request              = $request;
        $cgateController->fromTempWebhook      = 'false';

        $handleWebhook = $cgateController->handleWebhook();

        $newResponse = $handleWebhook;

        if ($handleWebhook['status'] == "00") {
            $newResponse = ['ResponseCode' => "00", 'ResponseMessage' => 'Successful'];
            $httpCode    = isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 200;
        } else {
            $newResponse = ['ResponseCode' => "01", 'ResponseMessage' => $newResponse['message']];
            $httpCode    = isset($handleWebhook['httpCode']) ? $handleWebhook['httpCode'] : 400;
        }

        return response()->json($newResponse, $httpCode);
    }

    public function assetMatrixWebhook(Request $request)
    {

        $assetMatrixController = new AssetMatrixController();
        $assetMatrixController->insertIntoWebhookTable($request);

        $verifyWebhook = $assetMatrixController->verifyTransaction($request->payment_reference);

        if (! $verifyWebhook['status']) {
            return response()->json($verifyWebhook, 400);
        }

        $assetMatrixController->transactionReference = $request->sessionId;
        $assetMatrixController->wbaCharge            = getWbaTransferCharges(6, $request->amount);
        $assetMatrixController->virtualAccount       = $request->sourceAccountNumber;
        $assetMatrixController->amount               = $request->amount;
        $assetMatrixController->webhookType          = 'assetmatrix';
        $assetMatrixController->description          = "You received the sum of $request->amount from " . $request->sourceAccountName;
        $assetMatrixController->transactionDate      = date("Y-m-d");
        $assetMatrixController->request              = $request;
        $assetMatrixController->fromTempWebhook      = 'false';

        $handleWebhook = $assetMatrixController->handleWebhook();

        return response()->json($handleWebhook, $handleWebhook['httpCode']);
    }

    public function flutterwaveWebhook(Request $request)
    {

        $transactionId = generateTransactionReference();

        // If you specified a secret hash, check for the signature
        $secretHash = env('APP_MODE') == 'live' ? env('FLUTTERWAVE_SECRET_HASH_LIVE') : env('FLUTTERWAVE_SECRET_HASH_TEST');
        $signature  = $request->header('verif-hash');
        if (! $signature || ($signature !== $secretHash)) {
            // This request isn't from Flutterwave; discard
            abort(401);
        }
        $payload = $request->all();
        // It's a good idea to log all received events.

        if (isset($request->Type)) {

            $card = UserCard::where('card_reference', $request->CardId)->first();

            if ($card) {
                $phone = $card->owner->phone;

                if ($request->Type == "Notficiation") {
                    sendSMS($card->owner->user_id, "Your Transaction OTP is $request->Otp, Please do not share this OTP with anyone.", true, true, "Transaction OTP", '', 0, false);
                } else if ($request->Type == "Debit") {
                    sendSMS($card->owner->user_id, "Debit Amt:$request->Amount Desc: Virtual card Debit Avail Bal:$request->Balance Date:" . Date('Y-m-d') . " Ref:$request->TransactionId", false, true, 'Debit Transaction', '', 0, false);
                }
            }
        }

        return response(200);
    }

    public function checkDuplicateTransaction($transactionReference)
    {
        $transactionStatus = transactions::where(['transaction_reference' => $transactionReference, 'transaction_remarks_id' => 2])->first();

        if ($transactionStatus) {
            return response()->json(['status' => false, 'message' => "duplicate transaction"]);
        } else {
            return response()->json(['status' => true, 'message' => "No duplicate transaction found"]);
        }
    }

    public function getAllTransactions(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "start_date" => 'date',
            "end_date"   => 'date',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $transactions = transactions::where(['company_id' => $checkApiKey->company_id]);

        if ($request->has('start_date')) {
            $transactions = $transactions->whereDate('created_at', '>=', date_format(date_create($request->start_date), 'Y-m-d'));
        }

        if ($request->has('end_date')) {
            $transactions = $transactions->whereDate('created_at', '<=', date_format(date_create($request->end_date), 'Y-m-d'));
        }
        $transactions = $transactions->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function getUserTransactions(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "start_date"       => 'date',
            "end_date"         => 'date',
            "virtual_account"  => 'required|numeric|digits:10',
            "transaction_type" => 'required|string|in:credit,debit',
        ]);
        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $perPage = $request->input('limit', 10);
        if (! is_numeric($perPage)) {
            $perPage = 10;
        }

        $header = $request->header('ApiKey');

        $checkApiKey = ApiKey::where('api_key', $header)->first();

        $user = User::where(['virtual_account' => $request->virtual_account, 'company_id' => $checkApiKey->company_id])->first();

        if (! $user) {
            return response()->json(['status' => false, 'message' => 'Invalid Virtual Account passed']);
        }

        $transactions = transactions::where(['account_id' => $user->accounts[0]->id]);

        if ($request->has('start_date')) {
            $transactions = $transactions->whereDate('created_at', '>=', date_format(date_create($request->start_date), 'Y-m-d'));
        }

        if ($request->has('end_date')) {
            $transactions = $transactions->whereDate('created_at', '<=', date_format(date_create($request->end_date), 'Y-m-d'));
        }
        $transactions = $transactions->paginate($perPage);

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    public function checkTransactionExists($transactionReference)
    {
        $transactionStatus = transactions::where(['transaction_reference' => $transactionReference])->first();

        if ($transactionStatus) {
            return ['status' => false, 'message' => "Transaction already exists", 'data' => ['transaction' => $transactionStatus]];
        } else {
            return ['status' => true, 'message' => "No transaction found"];
        }
    }

    public function getExchangeRates(Request $request, $destinationCurrencyID, $sourceCurrencyID)
    {

        try {
            $destinationCurrency = $this->matchCurrency($destinationCurrencyID);
            $sourceCurrency      = $this->matchCurrency($sourceCurrencyID);
            $amount              = $request->final_amount;
            \logInfo("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount", "Get Exhange URL");
            $response = Http::get("https://v6.exchangerate-api.com/v6/8ad2a80d79f07a6f69fe4ead/pair/$sourceCurrency/$destinationCurrency/$amount");
            \logInfo(json_encode($response), "Get Exhange Response");

            return $response['conversion_result'];

        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Get Exhange Error");
            return response()->json(["status" => 0, 'message' => "Failed to Verify Account"], 400);
        }

    }

    private function matchCurrency($currency_id)
    {
        return match ($currency_id) {
            2       => "USD",
            3       => "CAD",
            4       => "GBP",
            5       => "ZAR",
            6       => "CNY",
            7       => "EUR",
            8       => "GHS",

            default => "NGN",
        };
    }
    /* public function getTerminalBalance(Request $request)
    {

        $currentRequest = $request->all();

        if (isset($currentRequest['data']['transaction']['type']) && $currentRequest['data']['transaction']['type'] == 'vact_transfer') {

            $amuchaResponse = $this->amuchaWebhook($request);
        }

        try {
            if (isset($currentRequest['action_type'])) {

                $userPOS = AgentTerminal::where('terminal_code', $currentRequest['data']['terminal']['serialNo'])->first();

                //  logInfo(json_encode($userPOS), "Agent Terminal response");

                if (! $userPOS) {
                    if ($currentRequest['action_type'] == "pre-balance-check-auth") {
                        return response()->json(["status" => false, 'message' => "Invalid POS terminal Code"]);
                    }

                    if ($currentRequest['action_type'] == "pre-payout-auth") {

                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '03',
                            ],
                        ]]);
                    }
                }

                $user = $userPOS->agent;

                if ($user->pin != $currentRequest['data']['terminal']['pin']) {

                    if ($currentRequest['action_type'] == "pre-balance-check-auth") {
                        return $response = response()->json(["status" => "INVALID_PIN", 'message' => "Invalid authentication"]);
                    }

                    if ($currentRequest['action_type'] == "pre-payout-auth") {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '05',
                            ],
                        ]]);
                    }
                }

                $customerAccount = $user->wb_account_details[0];

                if ($currentRequest['action_type'] == "pre-balance-check-auth") {

                    return response()->json([
                        "data" => [
                            "user" => [
                                "balance"  => number_format($userPOS->balance, 2, '.', ''),
                                "currency" => "NGN",
                            ],
                        ],
                    ]);
                } else if ($currentRequest['action_type'] == "pre-payout-auth") {
                    if ($userPOS->status == 0) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '02',
                            ],
                        ]]);
                    }

                    $companySettings = company_settings::where('company_id', $user->company_id)->first();

                    if ($companySettings->pos_setting == 2) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '02',
                            ],
                        ]]);
                    }

                    $transaction = transactions::where('transaction_reference', $currentRequest['data']['transaction']['rrn'])->first();

                    if ($transaction) {
                        return response()->json(['data' => [
                            'transaction' => [
                                'shouldActionProceed' => false,
                                'reasonCode'          => '01',
                            ],
                        ]]);
                    }

                    $charge = $currentRequest['data']['transaction']["type"] == "transfer" ? 25 : 25;

                    $customerAmount = $currentRequest['data']['transaction']['amount'];

                    $theCharge = $companySettings->min_bank_transfer_charge_or_percentage;

                    // $customerCharge = ($companySettings->min_bank_transfer_charge_or_percentage * 0.01) * $currentRequest['data']['transaction']['amount'];
                    $customerCharge = $companySettings->max_bank_transfer_charge;

                    if ($currentRequest['data']['transaction']['type'] == "topup" || $currentRequest['data']['transaction']['type'] == "data") {
                        $theCharge      = 0;
                        $charge         = 0;
                        $customerCharge = 0;
                    }

                    $debitCustomer = debitPOSTerminalWithCharge($userPOS->id, $currentRequest['data']['transaction']['amount'], $customerCharge, $charge, $user);

                    if (! $debitCustomer['status']) {

                        if ($debitCustomer['responseCode'] == 0) {
                            return response()->json(['data' => [
                                'transaction' => [
                                    'shouldActionProceed' => false,
                                    'reasonCode'          => '07',
                                ],
                            ]]);
                        } else if ($debitCustomer['responseCode'] == 1) {
                            return response()->json(['data' => [
                                'transaction' => [
                                    'shouldActionProceed' => false,
                                    'reasonCode'          => '01',
                                ],
                            ]]);
                        }
                    }

                    $description = "WirelessPay miniPos - You carried out " . $currentRequest['data']['transaction']["type"] . " " .
                        $currentRequest['data']['transaction']['customerBillerId'] . " " .
                        $currentRequest['data']['transaction']['productId'] . " " .
                        $currentRequest['data']['terminal']['serialNo'] . " " .
                        $currentRequest['data']['transaction']['rrn'] . " " .
                        $currentRequest['data']['transaction']['billerAccountName'];

                    transactions::create([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $debitCustomer['amount'],
                        "balance_after"          => $debitCustomer['balance_after'],
                        "account_id"             => $customerAccount->id,
                        "description"            => $description,
                        "branch_id"              => $user->branch_id,
                        "company_balance_before" => $debitCustomer['company_balance_before'],
                        "company_amount"         => $debitCustomer['company_amount'],
                        "company_balance_after"  => $debitCustomer['company_balance_after'],
                        "company_id"             => $user->company_id,
                        "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 124,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => $customerAccount->id,
                    ]);

                    return response()->json(['data' => [
                        'transaction' => [
                            'shouldActionProceed' => true,
                            'reasonCode'          => '00',
                        ],
                    ]]);
                }
            } else if (isset($currentRequest['event_type'])) {

                if ($currentRequest['event_type'] == "payment_success") {
                    if ($currentRequest['data']['transaction']['type'] == "withdrawal" || $currentRequest['data']['transaction']['type'] == "purchase") {

                        $userPOS = AgentTerminal::where('terminal_code', $currentRequest['data']['transaction']['terminalSerialNumber'])->first();

                        if (! $userPOS) {
                            return response()->json(['status' => '00', 'message' => 'Invalid POS Serial Number']);
                        }

                        $transaction = transactions::where('transaction_reference', $currentRequest['data']['transaction']['rrn'])->first();

                        if ($transaction) {
                            return response()->json(['status' => '00', 'message' => 'Transaction already exists']);
                        }

                        $user = $userPOS->agent;

                        $companySettings = company_settings::where('company_id', $user->company_id)->first();

                        $description = "WirelessPay miniPos - " . $currentRequest['data']['transaction']["transactionId"] . " " .
                            $currentRequest['data']['transaction']['type'] . " " .
                            $currentRequest['data']['transaction']['transactionAmount'] . " " .
                            $currentRequest['data']['transaction']['terminalSerialNumber'] . " " .
                            $currentRequest['data']['transaction']['rrn'];

                        // $posSetting = $terminal->agent->company->setting->pos_setting;

                        // logInfo($posSetting, "My POS Setting is...");

                        $originalAmount  = $currentRequest['data']['transaction']['transactionAmount'];
                        $customerAccount = $user->wb_account_details[0];
                        $accountId       = $customerAccount->id;
                        $branchId        = $user->branch->id;
                        $companyId       = $user->company_id;

                        $companyCharge = getWbaTransferCharges(39, $originalAmount);

                        if ($companySettings->pos_setting == 2) {

                            $posCreditDebitType = false;

                            $charge = 0;

                            $customerAmount = $currentRequest['data']['transaction']['transactionAmount'];

                            $theCharge = $companySettings->additional_pos_charge_percent;

                            $customerCharge  = ($companySettings->additional_pos_charge_percent / 100) * $customerAmount;
                            $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                            $customerCharge += $charge;
                            $customerAmount -= $customerCharge;

                            $creditCustomer = creditCustomerOnly($customerAccount->id, $originalAmount, $customerCharge);

                            logInfo($creditCustomer, "POS Credit customer Only");

                            if (! $creditCustomer['status']) {
                                return response()->json($creditCustomer, 400);
                            }

                            $transactionType = 109;

                            logInfo($creditCustomer, "POS Credit customer Only");

                            $transaction = transactions::create([
                                "branch_id"              => $branchId,
                                "account_id"             => $accountId,
                                "description"            => "You Received funds via POS Terminal: " . $userPOS->terminal_code,
                                "balance_before"         => $creditCustomer['balance_before'],
                                "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                                "amount"                 => $originalAmount + $customerCharge,
                                "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => $transactionType,
                                "credit_debit_type_id"   => 1,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => $customer->id,
                            ]);
                        } else {

                            $charge         = $currentRequest['data']['transaction']["type"] == "transfer" ? 0 : 0;
                            $customerAmount = $currentRequest['data']['transaction']['transactionAmount'];

                            $customerCharge  = ($companySettings->additional_pos_charge_percent / 100) * $customerAmount;
                            $customerCharge  = $initialCustomerCharge  = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                            $customerCharge += $charge;
                            $customerAmount -= $customerCharge;

                            $creditCustomer = creditPOSTerminal($userPOS->terminal_code, $originalAmount, $customerCharge, $companyCharge, $companyId);

                            logInfo($creditCustomer, "Credit terminal log");

                            $transaction = transactions::create([
                                "balance_before"         => $creditCustomer['pos_balance_before'],
                                "amount"                 => $creditCustomer['pos_amount'],
                                "balance_after"          => $creditCustomer['pos_charge_balance_after'],
                                "company_amount"         => $creditCustomer['company_amount'],
                                'company_balance_before' => $creditCustomer['company_balance_before'],
                                'company_balance_after'  => $creditCustomer['company_balance_after'],
                                "description"            => $description,
                                "branch_id"              => $branchId,
                                "company_id"             => $companyId,
                                "account_id"             => $accountId,
                                "system_description"     => $description,
                                "transaction_reference"  => $currentRequest['data']['transaction']['rrn'],
                                "transaction_remarks_id" => 2,
                                "transaction_type_id"    => 63,
                                "credit_debit_type_id"   => 1,
                                "created_date"           => date('Y-m-d'),
                                "created_by"             => $user->id,
                            ]);
                        }

                        // if ($companySettings->pos_setting == 2) {
                        //     return response()->json(['status' => '00', 'message' => 'Cannot process this transaction']);
                        // }

                        // $customerAccount = $user->wb_account_details[0];

                        // $charge = 0;

                        // $customerAmount = $currentRequest['data']['transaction']['amount'];

                        // $theCharge = $companySettings->additional_pos_charge_percent;

                        // $customerCharge = $companySettings->additional_pos_charge_percent * $request->Amount;
                        // $customerCharge = $initialCustomerCharge = $customerCharge > $companySettings->pos_charge_capped_at ? $companySettings->pos_charge_capped_at : $customerCharge;
                        // $customerCharge += $charge;
                        // $customerAmount -= $customerCharge;

                        // $debitCustomer = creditCustomerOnly($customerAccount->id, $currentRequest['data']['transaction']['transactionAmount'], $customerCharge);

                        // if (!$debitCustomer['status']) {
                        //     return response()->json($debitCustomer);
                        // }

                        // transactions::create([
                        //     "balance_before" => $debitCustomer['balance_before'],
                        //     "amount" => $debitCustomer['amount'],
                        //     "balance_after" => $debitCustomer['balance_after'],
                        //     "account_id" => $customerAccount->id,
                        //     "description" => $description,
                        //     "branch_id" => $user->branch_id,
                        //     "company_balance_before" => 0,
                        //     "company_amount" => 0,
                        //     "company_balance_after" => 0,
                        //     "transaction_reference" => $currentRequest['data']['transaction']['rrn'],
                        //     "transaction_remarks_id" => 2,
                        //     "transaction_type_id" => 63,
                        //     "credit_debit_type_id" => 1,
                        //     "created_date"  => date('Y-m-d'),
                        //     "created_by" => $customerAccount->id
                        // ]);

                        return response()->json(['status' => '00', 'message' => 'Webhook handled successfully']);
                    }
                }
            }
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }*/

    public function getTerminalBalance(Request $request)
    {
        try {
            $data = $request->all();

            // Handle VACT transfer
            if (data_get($data, 'data.transaction.type') === 'vact_transfer') {
                return $this->amuchaWebhook($request);
            }

            // Action-based flow
            if (isset($data['action_type'])) {
                return $this->handleAction($data);
            }

            // Event-based flow
            if (isset($data['event_type'])) {
                return $this->handleEvent($data);
            }

            return response()->json(['status' => false, 'message' => 'Invalid request'], 400);

        } catch (\Exception $e) {
            logInfo($e->getMessage(), "Conversion error");
            return response()->json(["status" => false, 'message' => "Failed"], 400);
        }
    }

    private function handleAction(array $data)
    {
        $terminal = $this->getTerminal($data);

        if (! $terminal) {
            return $this->invalidTerminalResponse($data['action_type']);
        }

        $user = $terminal->agent;

        if (! $this->isValidPin($user, $data)) {
            return $this->invalidPinResponse($data['action_type']);
        }

        return match ($data['action_type']) {
            'pre-balance-check-auth' => $this->handleBalanceCheck($terminal),
            'pre-payout-auth'        => $this->handlePayout($terminal, $user, $data),
            default                  => response()->json(['status' => false], 400),
        };
    }

    private function getTerminal($data)
    {
        $serial = data_get($data, 'data.terminal.serialNo');

        return AgentTerminal::where('terminal_code', $serial)->first();
    }

    private function invalidTerminalResponse($type)
    {
        return $type === "pre-payout-auth"
            ? response()->json(['data' => ['transaction' => ['shouldActionProceed' => false, 'reasonCode' => '03']]])
            : response()->json(["status" => false, 'message' => "Invalid POS terminal Code"]);
    }

    private function isValidPin($user, $data): bool
    {
        return $user->pin == data_get($data, 'data.terminal.pin');
    }

    private function handleBalanceCheck($terminal)
    {
        return response()->json([
            "data" => [
                "user" => [
                    "balance"  => number_format($terminal->balance, 2, '.', ''),
                    "currency" => "NGN",
                ],
            ],
        ]);
    }

    private function invalidPinResponse(string $actionType)
    {
        return match ($actionType) {

            'pre-balance-check-auth' => response()->json([
                "status"  => "INVALID_PIN",
                "message" => "Invalid authentication",
            ]),

            'pre-payout-auth'        => response()->json([
                'data' => [
                    'transaction' => [
                        'shouldActionProceed' => false,
                        'reasonCode'          => '05',
                    ],
                ],
            ]),

            default                  => response()->json([
                "status"  => false,
                "message" => "Invalid PIN",
            ], 400),
        };
    }

    private function handlePayout($terminal, $user, array $data)
    {
        // 1. Validate terminal status
        if ($terminal->status == 0) {
            return $this->payoutResponse(false, '02');
        }

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        if ($companySettings->pos_setting == 2) {
            return $this->payoutResponse(false, '02');
        }

        // 2. Prevent duplicate transaction
        $rrn = data_get($data, 'data.transaction.rrn');

        if (transactions::where('transaction_reference', $rrn)->exists()) {
            return $this->payoutResponse(false, '01');
        }

        // 3. Extract transaction data safely
        $amount = (float) data_get($data, 'data.transaction.amount');
        $type   = data_get($data, 'data.transaction.type');

        // 4. Calculate charges
        [$charge, $customerCharge] = $this->calculateCharges($companySettings, $amount, $type);

        // 5. Debit terminal (VERY IMPORTANT → wrap in DB transaction ideally)
        $debit = debitPOSTerminalWithCharge(
            $terminal->id,
            $amount,
            $customerCharge,
            $charge,
            $user
        );

        if (! $debit['status']) {
            return $this->handleDebitFailure($debit);
        }

        // 6. Save transaction
        $this->createTransaction($terminal, $user, $data, $debit);

        // 7. Success
        return $this->payoutResponse(true, '00');
    }

    private function payoutResponse(bool $status, string $code)
    {
        return response()->json([
            'data' => [
                'transaction' => [
                    'shouldActionProceed' => $status,
                    'reasonCode'          => $code,
                ],
            ],
        ]);
    }

    private function calculateCharges($settings, $amount, $type): array
    {
        $charge         = 25;
        $customerCharge = $settings->max_bank_transfer_charge;

        if (in_array($type, ['topup', 'data'])) {
            return [0, 0];
        }

        return [$charge, $customerCharge];
    }

    private function handleDebitFailure($debit)
    {
        return match ($debit['responseCode']) {
            0       => $this->payoutResponse(false, '07'),
            1       => $this->payoutResponse(false, '01'),
            default => $this->payoutResponse(false, '99'),
        };
    }

    private function createTransaction($terminal, $user, $data, $debit)
    {
        $account = $user->wb_account_details[0];

        $description = $this->buildDescription($data);

        transactions::create([
            "balance_before"         => $debit['balance_before'],
            "amount"                 => $debit['amount'],
            "balance_after"          => $debit['balance_after'],
            "account_id"             => $account->id,
            "description"            => $description,
            "branch_id"              => $user->branch_id,
            "company_balance_before" => $debit['company_balance_before'],
            "company_amount"         => $debit['company_amount'],
            "company_balance_after"  => $debit['company_balance_after'],
            "company_id"             => $user->company_id,
            "transaction_reference"  => data_get($data, 'data.transaction.rrn'),
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 124,
            "credit_debit_type_id"   => 2,
            "created_date"           => now()->toDateString(),
            "created_by"             => $account->id,
        ]);

    }

    private function buildDescription($data): string
    {
        return "WirelessPay miniPos - You carried out " .
        data_get($data, 'data.transaction.type') . " " .
        data_get($data, 'data.transaction.customerBillerId') . " " .
        data_get($data, 'data.transaction.productId') . " " .
        data_get($data, 'data.terminal.serialNo') . " " .
        data_get($data, 'data.transaction.rrn') . " " .
        data_get($data, 'data.transaction.billerAccountName');
    }
    private function handleEvent(array $data)
    {
        if ($data['event_type'] !== 'payment_success') {
            return response()->json(['status' => '00']);
        }

        $type = data_get($data, 'data.transaction.type');

        if (! in_array($type, ['withdrawal', 'purchase'])) {
            return response()->json(['status' => '00']);
        }

        return $this->handlePaymentSuccess($data);
    }

    private function handlePaymentSuccess(array $data)
    {
        $terminal = $this->getTerminal($data);

        if (! $terminal) {
            return response()->json(['status' => '00', 'message' => 'Invalid POS Serial Number']);
        }

        // 2. Prevent duplicate
        $rrn = data_get($data, 'data.transaction.rrn');

        if (transactions::where('transaction_reference', $rrn)->exists()) {
            return response()->json(['status' => '00', 'message' => 'Transaction already exists']);
        }

        $user     = $terminal->agent;
        $settings = company_settings::where('company_id', $user->company_id)->first();

        $amount = (float) data_get($data, 'data.transaction.transactionAmount');

        // 3. Prepare shared data
        $account   = $user->wb_account_details[0];
        $branchId  = $user->branch->id;
        $companyId = $user->company_id;

        // 4. Calculate charges
        [$customerCharge, $companyCharge] = $this->calculatePaymentCharges($settings, $amount);

        // 5. Process credit (wrapped in DB transaction ⚠️)
        return \DB::transaction(function () use (
            $settings,
            $terminal,
            $user,
            $account,
            $amount,
            $customerCharge,
            $companyCharge,
            $branchId,
            $companyId,
            $data,
            $rrn
        ) {

            if ($settings->pos_setting == 2) {
                $credit = $this->creditCustomerFlow($account, $amount, $customerCharge);

                $this->storeCustomerTransaction($credit, $account, $branchId, $rrn, $data);

            } else {
                $credit = $this->creditTerminalFlow($terminal, $amount, $customerCharge, $companyCharge, $companyId);

                $this->storeTerminalTransaction($credit, $terminal, $user, $branchId, $companyId, $rrn, $data);
            }

            return response()->json(['status' => '00', 'message' => 'Webhook handled successfully']);
        });

    }

    private function calculatePaymentCharges($settings, $amount): array
    {
        $customerCharge = ($settings->additional_pos_charge_percent / 100) * $amount;

        $customerCharge = min($customerCharge, $settings->pos_charge_capped_at);

        $companyCharge = getWbaTransferCharges(39, $amount);

        return [$customerCharge, $companyCharge];
    }

    private function creditCustomerFlow($account, $amount, $charge)
    {
        $credit = creditCustomerOnly($account->id, $amount, $charge);

        if (! $credit['status']) {
            throw new \Exception('Customer credit failed');
        }

        return $credit;
    }

    private function creditTerminalFlow($terminal, $amount, $charge, $companyCharge, $companyId)
    {
        $credit = creditPOSTerminal(
            $terminal->terminal_code,
            $amount,
            $charge,
            $companyCharge,
            $companyId
        );

        if (! $credit['status']) {
            throw new \Exception('Terminal credit failed');
        }

        return $credit;
    }

    private function storeCustomerTransaction($credit, $account, $branchId, $rrn, $data)
    {
        transactions::create([
            "branch_id"             => $branchId,
            "account_id"            => $account->id,
            "description"           => "You Received funds via POS Terminal",
            "balance_before"        => $credit['balance_before'],
            "balance_after"         => $credit['pos_charge_balance_after'],
            "amount"                => $credit['amount'],
            "transaction_reference" => $rrn,
            "transaction_type_id"   => 109,
            "credit_debit_type_id"  => 1,
            "created_date"          => now()->toDateString(),
            "created_by"            => $account->id,
        ]);
    }

    private function storeTerminalTransaction($credit, $terminal, $user, $branchId, $companyId, $rrn, $data)
    {
        $description = $this->buildWebhookDescription($data);

        transactions::create([
            "balance_before"         => $credit['pos_balance_before'],
            "amount"                 => $credit['pos_amount'],
            "balance_after"          => $credit['pos_charge_balance_after'],
            "company_amount"         => $credit['company_amount'],
            "company_balance_before" => $credit['company_balance_before'],
            "company_balance_after"  => $credit['company_balance_after'],
            "description"            => $description,
            "branch_id"              => $branchId,
            "company_id"             => $companyId,
            "account_id"             => $user->wb_account_details[0]->id,
            "transaction_reference"  => $rrn,
            "transaction_type_id"    => 63,
            "credit_debit_type_id"   => 1,
            "created_date"           => now()->toDateString(),
            "created_by"             => $user->id,
        ]);
    }

    private function buildWebhookDescription($data): string
    {
        return "WirelessPay miniPos - " .
        data_get($data, 'data.transaction.transactionId') . " " .
        data_get($data, 'data.transaction.type') . " " .
        data_get($data, 'data.transaction.transactionAmount') . " " .
        data_get($data, 'data.transaction.terminalSerialNumber') . " " .
        data_get($data, 'data.transaction.rrn');
    }

    public function processOrder(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "amount"         => 'required|numeric|gt:0',
            "card_exp_month" => 'required|numeric|gt:0',
            "card_exp_year"  => 'required|numeric|gt:0',
            "card_number"    => 'required|numeric|gt:0',
            "cvv"            => 'required|numeric|gt:0',
            "card_pin"       => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        try {

            $amuchaController = new AmuchaController();

            $processOrderResult = $amuchaController->processOrder($request->amount, $request->cvv, $request->card_exp_month, $request->card_exp_year, $request->card_number, $request->card_pin);

            if (! $processOrderResult['status']) {
                return response()->json($processOrderResult, 400);
            }

            return response()->json($processOrderResult);
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }

    public function verifyCardOtp(Request $request)
    {
        $validation = Validator::make($request->all(), [
            "order_reference" => 'required|string',
            "transaction_id"  => 'required|string',
            "otp"             => 'required|numeric',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        try {

            $amuchaController = new AmuchaController();

            $verifyCardPinResult = $amuchaController->verifyCardOtp($request->otp, $request->order_reference, $request->transaction_id);

            if (! $verifyCardPinResult['status']) {
                return response()->json($verifyCardPinResult, 400);
            }

            return response()->json($verifyCardPinResult);
        } catch (Exception $e) {
            \logInfo($e->getMessage(), "Convertion error");
            return response()->json(["status" => false, 'message' => "Failed to Verify Account"], 400);
        }
    }

    public function checkCustomerBalance($amount, $selectedwallet = null)
    {

        $response = [];

        if ($amount > 0) {
            $selectedwb_wallet = $selectedwallet ?? auth()->user()->accounts->first()->id;
            $userWallet        = user_wallet::where('wb_account_detail_id', $selectedwb_wallet)->first();

            \logInfo($userWallet->balance, "userWallet balance from $selectedwb_wallet");

            if ($userWallet->balance >= $amount) {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->accounts->first()->id, 'balance' => $userWallet->balance];
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function checkCustomerAndCompanyBalance($amount)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            \logInfo(auth()->user()->accounts->first()->id, "wb_account_detail_id");

            \logInfo($userWallet->balance, "userWallet balance from checkCustomerAndCompanyBalance");

            if ($userWallet->balance >= $amount) {

                $companyWallet = company_wallet::where('company_id', \auth()->user()->company_id)->first();

                if ($companyWallet->balance >= $amount) {

                    $response = [
                        "status"          => true,
                        'message'         => "Amount is Valid",
                        'account_id'      => auth()->user()->accounts->first()->id,
                        'balance'         => $userWallet->balance,
                        'company_balance' => $companyWallet->balance,
                    ];
                } else {
                    $response = ["status" => false, 'message' => "Switcher Error... Please contact support $companyWallet->balance $amount"];
                }
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function checkCustomerBalanceCrossborder($amount, $userWallet)
    {
        $response = [];

        if ($amount > 0) {

            \logInfo($userWallet->balance, "userWallet balance from checkCustomerAndCompanyBalance");

            if ($userWallet->balance >= $amount) {

                $response = [
                    "status"          => true,
                    'message'         => "Amount is Valid",
                    'account_id'      => auth()->user()->accounts->first()->id,
                    'balance'         => $userWallet->balance,
                    'company_balance' => $companyWallet->balance,
                ];
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function validateTransactionPin($transactionPin)
    {
        $response = [];

        $userWallet = User::where(['id' => auth()->user()->id, 'pin' => $transactionPin])->first();

        if ($userWallet) {
            $response = ["status" => true, 'message' => "Pin is Valid"];
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction pin"];
        }

        return $response;
    }

    public function verifyCgateDetails(Request $request)
    {

        $cgateController = new CgateController();

        $response = [];

        $verifyHeader = $cgateController->verifyCgateIncomingHeader($request);

        if ($verifyHeader['status'] != '00') {
            return response()->json($verifyHeader, 400);
        }

        if (strlen($request->customerRef) != 5 && strlen($request->customerRef) != 10) {
            return response()->json(["status" => "02", 'message' => "Invalid Customer Reference Format"], 400);
        }

        if (strlen($request->customerRef) == 5) {
            $accountCode = (int) $request->customerRef;

            $adminUser = company_wallet::where('company_id', $accountCode)->first();

            if (! $adminUser) {

                // $forwardRequest = forwardRequestToWirelesspay('user/ussd/create', $request->all());

                return $forwardRequest;
            }

            $response = [
                'responseCode'   => "00",
                "customerName"   => $adminUser->company->company_name,
                "displayMessage" => $adminUser->company->company_name,
            ];
        } else {
            $loanRecord = loan::where('ref', $request->customerRef)->first();

            if (! $loanRecord) {

                $adminUser = wb_account_details::where('virtual_accnum', $request->customerRef)->first();

                if (! $adminUser) {
                    return response()->json(["status" => "01", 'message' => "Invalid Customer Reference"], 400);
                }

                return response()->json([
                    'responseCode'   => "00",
                    "customerName"   => $adminUser->user->first_name . " " . $adminUser->user->last_name,
                    "displayMessage" => $adminUser->user->first_name . " " . $adminUser->user->last_name,
                ]);
            }

            $amount = $loanRecord->loan_status_id == 10 ? 0.0 : ($loanRecord->EachRepayment <= $loanRecord->loanBalance ? $loanRecord->EachRepayment : $loanRecord->loanBalance);

            $response = [
                'responseCode'      => "00",
                "customerName"      => "Loan with " . $loanRecord->user->company->company_name,
                "displayMessage"    => "Loan with " . $loanRecord->user->company->company_name,
                "amount"            => $amount . "",
                "subMerchantName"   => null,
                "passBackReference" => null,
                "billerCode"        => null,
                "billerPrefix"      => null,
                "traceId"           => null,
            ];
        }

        return response()->json($response);
    }

    public function getCustomerBalance()
    {

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

        return $userWallet->balance;
    }

    public function randomNumber($length)
    {
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= mt_rand(1, 9);
        }

        return $result;
    }

    public function vtPassRequestId()
    {
        $result     = '';
        $dateString = \str_replace("-", "", Carbon::now()->format('Y-m-d H:i:s'));
        $dateString = \str_replace(" ", "", $dateString);
        $dateString = \str_replace(":", "", $dateString);
        // return $dateString . $this->randomNumber(8);
        return $dateString;
    }

    public function updateTransactionAsSuccessful($transactionId, $balance = 0, $updateBalance = true)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function updateCustomerAndCompanyTransactionAsSuccessful($transactionId, $customerBalance, $companyBalance, $updateBalance = true)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after         = $customerBalance;
            $transaction->company_balance_after = $companyBalance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = auth()->user()->id;
        $transaction->save();
    }

    public function debitCustomer($request)
    {

        $amount = $request->final_amount;

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();

            if ($userWallet->balance >= $amount) {

                if ($request->user()->company->wallet->balance >= $amount) {

                    $userWallet->balance    -= $amount;
                    $userWallet->updated_at  = Carbon::now();
                    $userWallet->save();

                    $request->user()->company->wallet->balance    -= $amount;
                    $request->user()->company->wallet->updated_at  = Carbon::now();
                    $request->user()->company->wallet->save();

                    $response = ["status" => true, 'message' => "Customer Debited", 'balance' => $userWallet->balance];
                } else {
                    $response = ["status" => false, 'message' => "Switcher Error... Please contact support for assistance"];
                }
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function validateTransfer($amount)
    {
        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();
        $accountTier     = auth()->user()->tier;
        $wbaAccount      = auth()->user()->accounts->first();

        if (! $companySettings) {
            return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', auth()->user()->accounts->first()->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        if ($amount < $accountTier->minimum_transfer) {
            return ["status" => false, 'message' => "Minimum amount allowed is NGN$accountTier->minimum_transfer"];
        }

        // $sum = transactions::where(['transaction_remarks_id' => '2', 'account_id' => $wbAccount->id])->whereDate('created_at', Carbon::today())->sum('amount');
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => auth()->user()->accounts->first()->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
        $dailySum = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->sum('amount');

        $lastTransaction = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $wbaAccount->id])->whereDate('created_at', Carbon::today())->orderBy('id', 'DESC')->first();

        if ($lastTransaction) {
            if ($lastTransaction->created_at > Carbon::now()->subSeconds(60)) {
                return ["status" => false, 'message' => "Cannot make multiple transactions within 1 minute"];
            }

            if ($dailySum + $amount > $accountTier->daily_transaction_limit) {
                return ["status" => false, 'message' => "Daily Transaction Limit Exceeded"];
            }
            if ($sum >= $accountTier->daily_transaction_count) {
                return ["status" => false, 'message' => "Daily Transaction Count Exceeded"];
            }
        }

        return ["status" => \true, 'message' => "Transaction Validated"];
    }

    public function accountProductCharges($amount)
    {
        $wbaAccount = auth('api')->user()->accounts->first();

        logInfo(auth('api')->user()->id, "wbaAccount account id");

        logInfo($wbaAccount->account_product_id, "wbaAccount accountProductCharges");

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        logInfo($accoountProduct->id, "wbaAccount accountProduct");

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $wbaAccount->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($accoountProduct->minimum_balance, "accountProduct minimum_balance");

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        logInfo($amountLeft, "Amount left after Deduction");
        logInfo($accoountProduct->minimum_balance, "Amount left after Deduction");

        if ($amount < $accoountProduct->minimum_withdraw) {
            return ["status" => false, 'message' => "Transfer Amount cannot be less than $accoountProduct->minimum_withdraw"];
        }

        if ($amount > $accoountProduct->max_withdraw) {
            return ["status" => false, 'message' => "Transfer Amount cannot exceed $accoountProduct->max_withdraw"];
        }

        if ($accoountProduct->bank_transfer_charge_type == 1) {
            $charge = $amount * ($accoountProduct->percentage_transfer_charge / 100);

            $charge = $charge > $accoountProduct->max_bank_transfer_charge ? $accoountProduct->max_bank_transfer_charge : $charge;
        } else {
            $charge = $accoountProduct->max_bank_transfer_charge;
        }

        return ["status" => true, 'message' => "Savings products validated", 'data' => ['charge' => $charge]];
    }

#[OA\Post(
    path: "/api/v1/transactions/buy-airtime",
    summary: "Purchase airtime",
    description: "Allows an authenticated user to purchase airtime using their account balance. The transaction validates the user's transaction PIN, account restrictions, customer and company balances, service provider balance, and processes the purchase through the configured airtime provider.",
    tags: ["Airtime"],
    security: [["bearerAuth" => []]],

    requestBody: new OA\RequestBody(
        required: true,
        description: "Airtime purchase details",
        content: new OA\JsonContent(
            required: [
                "final_amount",
                "phone_number",
                "network_provider",
                "transaction_pin"
            ],

            properties: [
                new OA\Property(
                    property: "final_amount",
                    type: "number",
                    format: "float",
                    example: 1000,
                    description: "Amount of airtime to purchase. Maximum allowed amount is 50,000."
                ),

                new OA\Property(
                    property: "phone_number",
                    type: "string",
                    example: "08012345678",
                    description: "Phone number to receive the airtime."
                ),

                new OA\Property(
                    property: "network_provider",
                    type: "string",
                    example: "mtn",
                    description: "Mobile network provider."
                ),

                new OA\Property(
                    property: "transaction_pin",
                    type: "string",
                    example: "1234",
                    description: "Transaction PIN. Must contain between 4 and 6 digits."
                ),
            ]
        )
    ),

    responses: [
        new OA\Response(
            response: 200,
            description: "Airtime purchase successful",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Airtime Purchase Successful"
                    ),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "balance",
                                type: "number",
                                format: "float",
                                example: 9500
                            )
                        ]
                    )
                ]
            )
        ),

        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),

        new OA\Response(
            response: 406,
            description: "Validation failed",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "The final amount field is required."
                    )
                ]
            )
        ),

        new OA\Response(
            response: 400,
            description: "Transaction failed",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Insufficient balance"
                    )
                ]
            )
        ),

        new OA\Response(
            response: 409,
            description: "Transaction conflict",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Transaction has already been completed"
                    )
                ]
            )
        ),

        new OA\Response(
            response: 500,
            description: "Internal server error"
        )
    ]
)]
    public function buyAirtime(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required|string",
            "transaction_pin"  => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        if ($request->final_amount > 50000) {
            return \response()->json(['status' => false, 'message' => 'Airtime purchase cannot be greater 50,000']);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $accountId = auth()->user()->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        /* if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $checkCustomerAndCompanyBalanceBeforeBuyAirtime = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0, 0.01);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyAirtime['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyAirtime['message'], 400);
        }

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        //   $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

     $company_debit_bankcode = $this->getCompanyBankCode();

      if ($company_debit_bankcode == 90286) {

      // This company uses SafeHaven instead of VTPass
        $checkVTPASSBalance = [
        'status'  => true,
        'message' => 'VTPass balance check skipped',
        ];

      } else {

      $checkVTPASSBalance = $this->checkVTPASSBalance(
        $request->final_amount
      );
       }

      logInfo(
       $checkVTPASSBalance,
       "VTPass Balance Check Before Airtime Purchase"
        );

      if (!$checkVTPASSBalance['status']) {

       /*
      * If VTPass itself has insufficient balance,
      * return a more useful message.
      */
       if (
        isset($checkVTPASSBalance['reason']) &&
        $checkVTPASSBalance['reason'] === 'insufficient_balance'
       ) {
        return response()->json([
            'status'  => false,
            'message' => 'Airtime service is temporarily unavailable because the service provider balance is insufficient. Please try again later.',
        ], 400);
       }

       /*
       * VTPass balance API failed
       */
        return response()->json([
        'status'  => false,
        'message' => $checkVTPASSBalance['message']
            ?? 'Unable to verify service provider balance. Please try again later.',
        ], 400);
     }

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 11, "Buy Airtime Initialized");

        //($customerAccountId, $(int) $company_id;companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
        // $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.05, 0.015);

        logInfo($company_debit_bankcode, "company_debit_bankcode");

        logInfo([
     'company_id' => auth()->user()->company_id,
     'company_debit_bankcode' => $company_debit_bankcode,
     'amount' => $request->final_amount,
     ], 'BEFORE AIRTIME DEBIT');

        // $debitCustomer = match ($company_debit_bankcode) {

        //     90286 => \debitCustomerAndCompanyWithCompanyCashbackServiceWallet(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.005, 0.015), //7000299948
        //     default
        //           => \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $request->final_amount, 0.005, 0.015),
        // };

        // if (! $debitCustomer['status']) {
        //     return response()->json($debitCustomer, 400);
        // }

        // logInfo($debitCustomer, 'AFTER AIRTIME DEBIT');

        $buyAirtime = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyAirTime', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyAirTime', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        if ($buyAirtime["status"] != false) {

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(11, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }*/

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $request->final_amount,
                        "account_id"             => $accountId,
                        "balance_after"          => $debitCustomer["balance_after"],
                        "company_balance_before" => $debitCustomer["company_balance_before"],
                        "company_balance_after"  => $debitCustomer["company_balance_after"],
                        "company_amount"         => $debitCustomer["company_amount"],
                        "company_id"             => auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "account_id"             => $transactionPinValidation['account_id'],
                        "description"            => "Purchase of Airtime worth $request->final_amount for $request->phone_number",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 11,
                        "credit_debit_type_id"   => 2,

                    ]);

                    $cashbackTransactionId = \generate_txnID();
                    $ledger_cr             = null;
                    $ledger_dr             = null;
                    $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $transaction = transactions::create([
                        "balance_before"         => $debitCustomer['cashback_balance_before'],
                        "amount"                 => $debitCustomer["customer_cashback"],
                        "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $accountId : null,
                        "balance_after"          => $debitCustomer["cashback_balance_after"],
                        "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
                        "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
                        "company_amount"         => $debitCustomer["company_cashback"],
                        "description"            => "Cashback on Airtime Purcahse",
                        "branch_id"              => auth()->user()->branch_id,
                        "company_id"             => auth()->user()->company_id,
                        "transaction_reference"  => $cashbackTransactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 66,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer["balance_after"]);

                    $subtext = "Airtime Purchase Successful";
                    $reason  = "";
                    $msg     = "Purchase of Airtime worth $request->final_amount for $request->phone_number";
                    $user    = auth()->user();
                    $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
                    //  spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json(
                        [
                            "status"  => true,
                            'message' => "Airtime Purchase Successful",
                            'data'    => [
                                'balance' => $debitCustomer['balance_after'],
                            ],
                        ]
                    );
                }
            }

        } else {
            return response()->json(["status" => false, 'message' => "Failed to purchase airtime. Kindly contact admin for assistance if you have been debited", 'data' => $transactionId], 400);
        }
    }

    public function getCompanyBankCode()
    {
        $company_bank_id         = company_wallet::where('company_id', auth()->user()->company_id)->first()->bank_id;
        $bank                    = banks::find($company_bank_id);
        $company_debit_bank_code = (int) $bank->bank_code;
        return $company_debit_bank_code;
    }


#[OA\Post(
    path: "/api/v1/transactions/buy-data-bundle",
    summary: "Purchase data bundle",
    description: "Allows an authenticated user to purchase a data bundle. The request validates the user's transaction PIN, account restrictions, account and company balances, service provider balance, network provider, and selected data plan before processing the transaction.",
    tags: ["Data"],
    security: [["bearerAuth" => []]],

    requestBody: new OA\RequestBody(
        required: true,
        description: "Data bundle purchase details",
        content: new OA\JsonContent(
            required: [
                "phone_number",
                "network_provider",
                "data_plan",
                "transaction_pin"
            ],

            properties: [
                new OA\Property(
                    property: "phone_number",
                    type: "string",
                    example: "08012345678",
                    description: "Phone number that will receive the data bundle."
                ),

                new OA\Property(
                    property: "network_provider",
                    type: "string",
                    example: "mtn-data",
                    description: "Mobile network provider/service ID."
                ),

                new OA\Property(
                    property: "data_plan",
                    type: "string",
                    example: "MTN_1GB",
                    description: "Variation code of the data bundle to purchase. The variation code must be available for the selected network provider."
                ),

                new OA\Property(
                    property: "transaction_pin",
                    type: "string",
                    example: "1234",
                    description: "Transaction PIN. Must contain between 4 and 6 digits."
                ),
            ]
        )
    ),

    responses: [
        new OA\Response(
            response: 200,
            description: "Data bundle purchase successful",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),

                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Data Plan Purchase Successful"
                    ),

                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(
                                property: "balance",
                                type: "number",
                                format: "float",
                                example: 9500
                            )
                        ]
                    )
                ]
            )
        ),

        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),

        new OA\Response(
            response: 406,
            description: "Validation failed",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),

                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "The phone number field is required."
                    )
                ]
            )
        ),

        new OA\Response(
            response: 400,
            description: "Data bundle purchase failed",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),

                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Insufficient balance"
                    )
                ]
            )
        ),

        new OA\Response(
            response: 409,
            description: "Transaction conflict",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: false
                    ),

                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Transaction has already been completed"
                    )
                ]
            )
        ),

        new OA\Response(
            response: 500,
            description: "Internal server error"
        )
    ]
)]
    public function buyDataBundle(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "phone_number"     => "required|numeric|gt:0",
            "network_provider" => "required",
            "data_plan"        => "required",
            "transaction_pin"  => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, 1);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        /* if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        // $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

      $company_debit_bankcode = $this->getCompanyBankCode();

     if ($company_debit_bankcode == 90286) {

     // This company uses SafeHaven instead of VTPass
     $checkVTPASSBalance = [
        'status'  => true,
        'message' => 'VTPass balance check skipped',
     ];

      } else {

     $checkVTPASSBalance = $this->checkVTPASSBalance(
        $request->final_amount
     );
      }

     logInfo(
     $checkVTPASSBalance,
      "VTPass Balance Check Before Data Purchase"
     );

     if (!$checkVTPASSBalance['status']) {

     /*
     * If VTPass itself has insufficient balance,
     * return a more useful message.
     */
     if (
        isset($checkVTPASSBalance['reason']) &&
        $checkVTPASSBalance['reason'] === 'insufficient_balance'
     ) {
        return response()->json([
            'status'  => false,
            'message' => 'Data service is temporarily unavailable because the service provider balance is insufficient. Please try again later.',
        ], 400);
     }

     /*
     * VTPass balance API failed
     */
     return response()->json([
        'status'  => false,
        'message' => $checkVTPASSBalance['message']
            ?? 'Unable to verify service provider balance. Please try again later.',
     ], 400);
     }

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getDataBundlesList', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getDataBundlesList', [
                'request' => $request,

            ]),
        };

        if (! $dataBundles['status']) {
            return response()->json(["status" => false, 'message' => "Failed to get Data bundles"], 400);
        }

        $isDataPlanValid = false;

        $selectedDataBundle = [];

        foreach ($dataBundles['data'] as $value) {
            if ($value['variation_code'] == $request->data_plan) {
                $selectedDataBundle = $value;
                $isDataPlanValid    = true;
            }
        }
        if (! $isDataPlanValid) {
            return response()->json(["status" => false, 'message' => "Invalid Data bundle"], 400);
        }

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->network_provider,
            "variation_code" => $request->data_plan,
            "amount"         => $selectedDataBundle['variation_amount'],
            "phone"          => $request->phone_number,
        ];

        $checkCustomerAndCompanyBalanceBeforeBuyAirtime = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0, 0.01);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyAirtime['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyAirtime['message'], 400);
        }

        $endpoint = "pay";

        $selectedDataBundleDescription = $selectedDataBundle['variation_amount'];
        $initialdescriptionCTUP        = "Buy Data Bundle Initialized - $selectedDataBundleDescription";

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 12, $initialdescriptionCTUP);

        //($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
        // $debitCustomer = \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.05, 0.015);

        $debitCustomer = match ($company_debit_bankcode) {

            90286 => \debitCustomerAndCompanyWithCompanyCashbackServiceWallet(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.005, 0.015), //7000299948
            default
                  => \debitCustomerAndCompanyWithCompanyCashback(auth()->user()->accounts->first()->id, \auth()->user()->company_id, $selectedDataBundle['variation_amount'], 0.005, 0.015),
        };

        if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }

        $buyData = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyData', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyData', [
                'request'            => $request,
                'transactionId'      => $transactionId,
                'selectedDataBundle' => $selectedDataBundle,
            ]),
        };

        if ($buyData["status"]) {

     /*
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(12, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
     */
            if (! $debitCustomer['status']) {
                return response()->json($debitCustomer, 400);
            }

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $selectedDataBundle['variation_amount'],
                        "balance_after"          => $debitCustomer["balance_after"],
                        "company_balance_before" => $debitCustomer["company_balance_before"],
                        "company_balance_after"  => $debitCustomer["company_balance_after"],
                        "company_amount"         => $debitCustomer["company_amount"],
                        "company_id"             => auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "account_id"             => $transactionPinValidation['account_id'],
                        "description"            => "Purchase of Data worth " . $selectedDataBundle['variation_amount'] . " for $request->phone_number",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 12,
                        "credit_debit_type_id"   => 2,

                    ]);

                    $cashbackTransactionId = \generate_txnID();
                    $ledger_cr             = null;
                    $ledger_dr             = null;
                    $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    $cashbackTransaction = transactions::create([
                        "balance_before"         => $debitCustomer['cashback_balance_before'],
                        "amount"                 => $debitCustomer["customer_cashback"],
                        "account_id"             => $debitCustomer["customer_cashback"] > 0 ? $transactionPinValidation['account_id'] : null,
                        "balance_after"          => $debitCustomer["cashback_balance_after"],
                        "company_balance_before" => $debitCustomer["company_cashback_balance_before"],
                        "company_balance_after"  => $debitCustomer["company_cashback_balance_after"],
                        "company_amount"         => $debitCustomer["company_cashback"],
                        "description"            => "Cashback on Data Purchase",
                        "company_id"             => auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "parent_transaction_id"  => $transaction->id,
                        "transaction_reference"  => $cashbackTransactionId,
                        "transaction_remarks_id" => 2,
                        "transaction_type_id"    => 66,
                        "credit_debit_type_id"   => 1,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after']);

                    $subtext = "Data Purchase Successful";
                    $reason  = "";
                    $msg     = "Purchase of Data worth " . $selectedDataBundle['variation_amount'] . " for $request->phone_number";
                    $user    = auth()->user();
                    $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
                    // spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json(
                        [
                            "status"  => true,
                            'message' => "Data Plan Purchase Successful",
                            'data'    => [
                                'balance' => $debitCustomer['balance_after'],
                            ],
                        ]
                    );
                }

            }

        } else {
            return response()->json(["status" => false, 'message' => $buyData['message']], 400);
        }
    }

    public function debitCustomeFromApi(Request $request)
    {

        $transaction = transactions::where("transaction_reference", $request->transaction_reference)->first();

        if (! $transaction) {
            return response()->json(['status' => false, 'message' => 'Transaction record not found'], 400);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => false, 'message' => 'Transaction has already been reversed'], 400);
        }

        if ($transaction->transaction_type_id != 6) {
            return response()->json(['status' => false, 'message' => 'Transaction is not a deposit transaction'], 400);
        }

        $adminUser = User::find($transaction->created_by);

        if (! $adminUser) {
            return response()->json(['status' => false, 'message' => "Admin user not found $transaction->company_id"], 400);
        }

        logInfo($adminUser->branch_id);

        if ($transaction->account_id != null) {
            $debitCustomer = \debitCustomerAndCompanyWithCharge($transaction->account_id, $transaction->company_id, $transaction->amount, 50, 0, $adminUser);

            if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }

            $newtransaction = transactions::create([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $transaction->amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "account_id"             => $transaction->account_id,
                "company_id"             => $transaction->company_id,
                "branch_id"              => $adminUser->branch_id,
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Error Bank transaction Depot reversal",
                "transaction_reference"  => generateTransactionReference(),
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 27,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
            ]);
        } else {

            $debitCustomer = debitCompanyWithCharge($transaction->company_id, $transaction->amount, 50, $adminUser);

            if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }

            $newtransaction = transactions::create([
                "balance_before"         => 0,
                "amount"                 => 0,
                "balance_after"          => 0,
                "description"            => "Error  Bank transaction Depot reversal",
                "branch_id"              => $adminUser->branch_id,
                "company_id"             => $transaction->company_id,
                "company_balance_before" => $debitCustomer['balance_before'],
                "company_balance_after"  => $debitCustomer['balance_after'],
                "company_amount"         => $request->amount,
                "transaction_reference"  => generateTransactionReference(),
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 8,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $adminUser->id,
            ]);
        }

        $transaction->transaction_remarks_id = 5;
        $transaction->save();

        return \response()->json($newtransaction);
    }

    public function buyElectricity(Request $request)
    {
        // $transactionId = $this->randomNumber(12);
        logInfo(json_encode($request->all()), "buyElectricity Response");

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"      => "required|numeric|gt:0",
            "phone_number"      => "required|numeric|gt:0",
            "meter_number"      => "required|numeric|gt:0",
            "electricity_disco" => "required",
            "meter_type"        => "required",
            "transaction_pin"   => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(13, $request->final_amount);

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $accountId = auth()->user()->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $request->final_amount;

        /* $checkCompanyBalance = checkCompanyBalance($checkCompanyBalanceAmount);

        if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }*/

        $checkCustomerAndCompanyBalanceBeforeBuyElectricity = $this->checkCustomerAndCompanyBalanceBeforeBuyElectricity($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyElectricity['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyElectricity['message'], 400);
        }

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        //  $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

$company_debit_bankcode = $this->getCompanyBankCode();

if ($company_debit_bankcode == 90286) {

    // This company uses SafeHaven instead of VTPass
    $checkVTPASSBalance = [
        'status'  => true,
        'message' => 'VTPass balance check skipped',
    ];

} else {

    $checkVTPASSBalance = $this->checkVTPASSBalance(
        $request->final_amount
    );
}

logInfo(
    $checkVTPASSBalance,
    "VTPass Balance Check Before Electricity Purchase"
);

if (!$checkVTPASSBalance['status']) {

    /*
     * If VTPass itself has insufficient balance,
     * return a more useful message.
     */
    if (
        isset($checkVTPASSBalance['reason']) &&
        $checkVTPASSBalance['reason'] === 'insufficient_balance'
    ) {
        return response()->json([
            'status'  => false,
            'message' => 'Electricity service is temporarily unavailable because the service provider balance is insufficient. Please try again later.',
        ], 400);
    }

    /*
     * VTPass balance API failed
     */
    return response()->json([
        'status'  => false,
        'message' => $checkVTPASSBalance['message']
            ?? 'Unable to verify service provider balance. Please try again later.',
    ], 400);
}

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 14, "Buy Electricity Initialized");

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $request->electricity_disco,
            "variation_code" => $request->meter_type,
            "billersCode"    => $request->meter_number,
            "amount"         => $request->final_amount,
            "phone"          => $request->phone_number,
        ];

        /*   if (\auth()->user()->company_id == 80) {
            $remitaController = new RemittaController();

            $buyElectricity = $remitaController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number
            );

            logInfo($buyElectricity, "Remita Electricity Response");

        } else {

            $buyElectricity = match ($company_debit_bankcode) {

                90286 => $this->safeHavenControllerSercice('buyElectricity', [
                    'request'       => $request,
                    'transactionId' => $transactionId,
                ]), //7000299948
                default
                      => $this->vtPassControllerSercice('buyElectricity', [
                    'request'       => $request,
                    'transactionId' => $transactionId,
                ]),
            };

        }*/

        $buyElectricity = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('buyElectricity', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('buyElectricity', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        if (! $buyElectricity['status']) {
            return response()->json($buyElectricity, 400);
        } else {
            //     $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

            $debitCustomer = match ($company_debit_bankcode) {

                90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge), //7000299948
                default
                      => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge),
            };
            /*   if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }*/

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(14, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
                */
            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();
            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([
                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $request->final_amount,
                        "balance_after"          => $debitCustomer["balance_after"],
                        "account_id"             => $transactionPinValidation['account_id'],
                        "company_id"             => \auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "company_balance_before" => $debitCustomer["company_balance_before"],
                        "company_amount"         => $debitCustomer["company_amount"],
                        "company_balance_after"  => $debitCustomer["company_balance_after"],
                        "description"            => "Purchase of Electricity worth $request->final_amount",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 14,
                        "credit_debit_type_id"   => 2,

                    ]);
                    $transactionChargeId = \generate_txnID();
                    $ledger_cr           = null;
                    $ledger_dr           = null;
                    $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    // Save Charges to transaction table
                    $chargetransaction = transactions::create([
                        "balance_before"         => $debitCustomer['charge_balance_before'],
                        "amount"                 => $companySettings->bills_payment_charge,
                        "balance_after"          => $debitCustomer["charge_balance_after"],
                        "account_id"             => $transactionPinValidation['account_id'],
                        "company_id"             => \auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "company_balance_before" => $debitCustomer["company_charge_balance_before"],
                        "company_amount"         => $wbaCharge,
                        "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
                        "description"            => "Charge for Electricity Purchase",
                        "transaction_reference"  => $transactionChargeId,
                        "parent_transaction_id"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 51,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_cr"              => $ledger_cr,
                        "ledger_dr"              => $ledger_dr,
                    ]);

                    $electricityToken = $buyElectricity['data']['token'];

                    $transaction->transaction_remarks_id = 2;
                    $transaction->description            = "Purchased Electricity Token: $electricityToken Amount: $request->final_amount";
                    $transaction->save();

                    $buyElectricity['data']['balance'] = $debitCustomer["charge_balance_after"];

                    sendSMS(auth()->user()->id, "Purchased Electricity Token $electricityToken, Amount: $request->final_amount, Avail Bal: " . $debitCustomer["charge_balance_after"], true, true, 'Electricity Token');
//spendSaveRecordTransaction($debitCustomer, $transaction);
                    return response()->json($buyElectricity);
                }
            }

        }

    }

    public function subscribeCableTV(Request $request)
    {

        $transactionId = $this->vtPassRequestId();

        $validation = Validator::make($request->all(), [
            "final_amount"      => "required|numeric|gt:0",
            "phone_number"      => "required|numeric|gt:0",
            "smart_card_number" => "required|numeric|gt:0",
            "subscription_plan" => "required|string",
            "cable_tv_type"     => "required|string",
            "transaction_pin"   => "required|numeric|gt:0|digits_between:4,6",
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $transactionPinValidation = validatePinCheckCustomerBalance($request->transaction_pin, $request->final_amount);
        if (! $transactionPinValidation['status']) {
            return response()->json($transactionPinValidation, 400);
        }

        $checkAllowedTransaction = checkCompanyAllowedTransactionStatus(auth()->user()->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return response()->json($checkAllowedTransaction);
        }

        if (\checkcustomerrestriction(\auth()->user()->id, '2')) {
            return response()->json(["status" => false, 'message' => "Your account has been restricted. please contact support"], 400);
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(12, ($request->amount / 100));

        $companySettings = company_settings::where('company_id', \auth()->user()->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($request->final_amount);

        if (! $validateTransferAmount['status']) {
            return \response()->json($validateTransferAmount, 400);
        }

        $wb_account_details = wb_account_details::where('user_id', auth()->user()->id)->where('acc_reference', auth()->user()->account_reference)->first();

        /*  $checkCompanyBalance = checkCompanyBalance($request->final_amount);

        if (! $checkCompanyBalance['status']) {
            return \response()->json($checkCompanyBalance, 400);
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $request->final_amount);

        if (! $checkCustomerBalance['status']) {
            return \response()->json($checkCustomerBalancee, 400);
        }
            */

        $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

$company_debit_bankcode = $this->getCompanyBankCode();

if ($company_debit_bankcode == 90286) {

    // This company uses SafeHaven instead of VTPass
    $checkVTPASSBalance = [
        'status'  => true,
        'message' => 'VTPass balance check skipped',
    ];

} else {

    $checkVTPASSBalance = $this->checkVTPASSBalance(
        $request->final_amount
    );
}

logInfo(
    $checkVTPASSBalance,
    "VTPass Balance Check Before Cable TV Purchase"
);

if (!$checkVTPASSBalance['status']) {

    /*
     * If VTPass itself has insufficient balance,
     * return a more useful message.
     */
    if (
        isset($checkVTPASSBalance['reason']) &&
        $checkVTPASSBalance['reason'] === 'insufficient_balance'
    ) {
        return response()->json([
            'status'  => false,
            'message' => 'Cable TV service is temporarily unavailable because the service provider balance is insufficient. Please try again later.',
        ], 400);
    }

    /*
     * VTPass balance API failed
     */
    return response()->json([
        'status'  => false,
        'message' => $checkVTPASSBalance['message']
            ?? 'Unable to verify service provider balance. Please try again later.',
    ], 400);
}

        $this->createTransanctionforUtilityPayment($request, $transactionId, $customerBalance, 13, "Subscribe TV Initialized");

        //  $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);

        $debitCustomer = match ($company_debit_bankcode) {

            90286 => \debitCustomerAndCompanyWithChargeServiceWallet($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge), //7000299948
            default
                  => \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge),
        };

        if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }

        $subscribeCableTV = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('subscribeCableTV', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('subscribeCableTV', [
                'request'       => $request,
                'transactionId' => $transactionId,
            ]),
        };

        logInfo($subscribeCableTV, "VTPass Subscribe Cable TV Log");

        if ($subscribeCableTV['status']) {

            /*  $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(13, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }*/

            $transaction = transactions::lockForUpdate()->where(['transaction_reference' => $transactionId, 'amount' => $request->final_amount])->first();

            if ($transaction) {
                if ($transaction->transaction_remarks_id == 2) {
                    return response()->json(["status" => false, 'message' => "Transaction has already been completed"], 409);
                } else if ($transaction->transaction_remarks_id == 3) {
                    return response()->json(["status" => false, 'message' => "Transaction has been processed and failed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 5) {
                    return response()->json(["status" => false, 'message' => "Transaction failed and has already been reversed. Kindly try another transaction"], 409);
                } else if ($transaction->transaction_remarks_id == 1) {
                    return response()->json(["status" => false, 'message' => "Duplicate Transaction Reference. Please try again later"], 409);
                } else {
                    $transaction->update([

                        "balance_before"         => $debitCustomer['balance_before'],
                        "amount"                 => $request->final_amount,
                        "balance_after"          => $debitCustomer["balance_after"],
                        "account_id"             => $transactionPinValidation['account_id'],
                        "company_id"             => \auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "company_balance_before" => $debitCustomer["company_balance_before"],
                        "company_amount"         => $debitCustomer["company_amount"],
                        "company_balance_after"  => $debitCustomer["company_balance_after"],
                        "description"            => "Cable TV Subscription worth $request->final_amount",
                        "transaction_reference"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 13,
                        "credit_debit_type_id"   => 2,

                    ]);
                    $transactionChargeId = \generate_txnID();
                    $ledger_cr           = null;
                    $ledger_dr           = null;
                    $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, auth()->user()->company_id);
                    if ($ledger_Settings) {
                        $ledger_cr = $ledger_Settings->ledger_cr;
                        $ledger_dr = $ledger_Settings->ledger_dr;
                    }
                    // Save Charges to transaction table
                    $transaction = transactions::create([
                        "balance_before"         => $debitCustomer['charge_balance_before'],
                        "amount"                 => $companySettings->bills_payment_charge,
                        "balance_after"          => $debitCustomer["charge_balance_after"],
                        "account_id"             => $transactionPinValidation['account_id'],
                        "company_id"             => \auth()->user()->company_id,
                        "branch_id"              => auth()->user()->branch_id,
                        "company_balance_before" => $debitCustomer["company_charge_balance_before"],
                        "company_amount"         => $wbaCharge,
                        "company_balance_after"  => $debitCustomer["company_charge_balance_after"],
                        "description"            => "Charge for Cable TV Subscription",
                        "transaction_reference"  => $transactionChargeId,
                        "parent_transaction_id"  => $transactionId,
                        "transaction_remarks_id" => 1,
                        "transaction_type_id"    => 51,
                        "credit_debit_type_id"   => 2,
                        "created_date"           => date('Y-m-d'),
                        "created_by"             => auth()->user()->id,
                        "ledger_dr"              => $ledger_dr,
                        "ledger_cr"              => $ledger_cr,
                    ]);

                    $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_before'], false);

                    $subscribeCableTV['data']['balance'] = $debitCustomer['charge_balance_after'];

                    //      spendSaveRecordTransaction($debitCustomer, $transaction);

                    return response()->json($subscribeCableTV);
                }

            }

        } else {
            return response()->json($subscribeCableTV, 400);
        }
    }

#[OA\Post(
    path: "/api/v1/verify-bvn",
    summary: "Verify BVN / Identity",
    description: "Verifies a customer's BVN, date of birth, or NIN. The verification service used depends on the company's configured bank.",
    tags: ["User - General Operations"],
    security: [["bearerAuth" => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "bvn",
                    type: "string",
                    example: "22123456789",
                    description: "Bank Verification Number (BVN)."
                ),
                new OA\Property(
                    property: "dob",
                    type: "string",
                    example: "1995-08-15",
                    description: "Customer's date of birth."
                ),
                new OA\Property(
                    property: "nin",
                    type: "string",
                    example: "12345678901",
                    description: "National Identification Number (NIN)."
                ),
                new OA\Property(
                    property: "company_id",
                    type: "integer",
                    example: 46,
                    description: "Company ID. Used when the authenticated user's company ID is unavailable."
                )
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Identity verification request processed successfully."
        ),
        new OA\Response(
            response: 400,
            description: "Identity verification failed."
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated."
        ),
        new OA\Response(
            response: 406,
            description: "Validation failed. BVN and/or date of birth information is invalid or missing."
        ),
        new OA\Response(
            response: 500,
            description: "Internal server error."
        )
    ]
)]
public function verifyBVN(Request $request)
{
    $validation = Validator::make($request->all(), [
        "bvn" => 'sometimes|numeric|gt:0',
        "dob"  => 'sometimes',
        'nin'  => 'sometimes',
    ]);

    if ($validation->fails()) {
        $ra = [
            "status" => false,
            "message" => "BVN and Date of birth is required"
        ];

        return response()->json($ra, 406);
    }

    logInfo($request->all(), "Request body");

    $companyId = auth()->user()->company->id ?? $request->company_id;

    logInfo($companyId, "verifyBVN company id");

    $wallet = company_wallet::where('company_id', $companyId)->first();

    if (!$wallet) {
        return response()->json([
            'status' => false,
            'message' => 'Company wallet not found'
        ], 400);
    }

    $bankID = $wallet->bank_id;

    logInfo($bankID, "verifyBVN bank id");

    $company_bank_id = (int) $bankID;

    return match ($company_bank_id) {
        190 => $this->verifyIdentityWithSafeHaven($request),
        default => $this->debitCompanyWithSubscriptionBVN($request, $companyId),
    };
}

#[OA\Post(
    path: "/api/v1/verify-bvn/otp",

    summary: "Complete BVN Verification with OTP",

    description: "Completes a SafeHaven BVN verification initiated through the /verify-bvn endpoint. The identityId returned from the initial verification request and the OTP sent to the customer's registered phone number are submitted to complete the verification.",

    tags: ["User - General Operations"],

    security: [["bearerAuth" => []]],

    requestBody: new OA\RequestBody(
        required: true,

        content: new OA\JsonContent(
            type: "object",

            properties: [

                new OA\Property(
                    property: "identityId",
                    type: "string",
                    example: "6a044cc20f96d130c00975d5",
                    description: "SafeHaven identity ID returned from the initial /verify-bvn verification request."
                ),

                new OA\Property(
                    property: "otp",
                    type: "string",
                    example: "123456",
                    description: "OTP sent to the customer's registered phone number by SafeHaven."
                ),

                new OA\Property(
                    property: "company_id",
                    type: "integer",
                    example: 92,
                    description: "Company ID. Used when the authenticated user's company ID is unavailable."
                ),

                new OA\Property(
                    property: "dob",
                    type: "string",
                    example: "1996-09-29",
                    description: "Customer's date of birth. Used to optionally compare the submitted date of birth with the verified identity details."
                )

            ]
        )
    ),

    responses: [

        new OA\Response(
            response: 200,
            description: "BVN verification completed successfully."
        ),

        new OA\Response(
            response: 400,
            description: "BVN OTP verification failed."
        ),

        new OA\Response(
            response: 401,
            description: "Unauthenticated."
        ),

        new OA\Response(
            response: 406,
            description: "Validation failed. Identity ID, OTP, or required company information is missing or invalid."
        ),

        new OA\Response(
            response: 500,
            description: "Internal server error."
        )

    ]
)]
public function verifyBVNOtp(Request $request)
{
    $validation = Validator::make($request->all(), [
        'identityId' => 'required|string',
        'otp'        => 'required|string',
        'company_id' => 'required',
        'dob'        => 'sometimes|date',
    ]);

    if ($validation->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $validation->errors()->first(),
        ], 406);
    }

    logInfo([
        'identityId' => $request->identityId,
        'otp'        => '******',
        'company_id' => $request->company_id,
        'dob'        => $request->dob,
    ], "Verify BVN OTP request");

    $companyId = auth()->user()->company->id ?? $request->company_id;

    $wallet = company_wallet::where('company_id', $companyId)->first();

    if (!$wallet) {
        return response()->json([
            'status'  => false,
            'message' => 'Company wallet not found',
        ], 400);
    }

    $bankID = (int) $wallet->bank_id;

    if ($bankID !== 190) {
        return response()->json([
            'status'  => false,
            'message' => 'SafeHaven BVN verification is not configured for this company.',
        ], 400);
    }

    try {

        $safehavenController = new SafehavenController();

        $identityId = $request->identityId;

        $response = $safehavenController->verifyBVNOtp(
            $identityId,
            $request->otp,
            $companyId
        );

        if (!($response['status'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => $response['message']
                    ?? 'Failed to verify BVN',
                'data'    => $response['data'] ?? [],
            ], 400);
        }

        $data = $response['data'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Optional DOB verification
        |--------------------------------------------------------------------------
        */

        if ($request->filled('dob')) {

            $returnedDob =
                $data['dateOfBirth']
                ?? $data['dob']
                ?? null;

            if ($returnedDob) {

                try {

                    $requestedDob = \Carbon\Carbon::parse(
                        $request->dob
                    )->format('Y-m-d');

                    $verifiedDob = \Carbon\Carbon::parse(
                        $returnedDob
                    )->format('Y-m-d');

                    if ($requestedDob !== $verifiedDob) {

                        return response()->json([
                            'status'  => false,
                            'message' => 'BVN verified but date of birth does not match.',
                            'data'    => $data,
                        ], 400);
                    }

                } catch (\Exception $e) {

                    logInfo(
                        $e->getMessage(),
                        "Safehaven BVN DOB comparison error"
                    );
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | OTP verification successful
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Return the identityId that was used for the successful verification.
        | Do not depend on SafeHaven returning it again.
        |
        */

        return response()->json([
            'status'     => true,
            'message'    => 'Account Verified Successfully',
            'identityId' => $identityId,
            'data'       => array_merge(
                $data,
                [
                    'identityId' => $identityId,
                ]
            ),
        ], 200);

    } catch (\Exception $e) {

        logInfo(
            $e->getMessage(),
            "Verify BVN OTP Controller Error"
        );

        return response()->json([
            'status'  => false,
            'message' => 'Error verifying account',
        ], 400);
    }
}

    public function debitBVNFromCompany($debitCompanyWithCharge, $companyId, $charge)
    {
        $companyBalanceBefore       = $debitCompanyWithCharge['balance_before'];
        $companyBalanceAfter        = $debitCompanyWithCharge['balance_after'];
        $companyChargeBalanceBefore = $debitCompanyWithCharge['charge_balance_before'];
        $companyChargeBalanceAfter  = $debitCompanyWithCharge['charge_balance_after'];

        $transactionRef2 = generateTransactionReference();

        transactions::create([
            "company_balance_before" => $companyChargeBalanceBefore,
            "company_amount"         => 0,
            "account_id"             => null,
            "company_balance_after"  => $companyChargeBalanceAfter,
            "description"            => "Charge for BVN Check",
            "transaction_reference"  => $transactionRef2,
            "balance_before"         => $companyBalanceBefore,
            "amount"                 => $charge,
            "balance_after"          => $companyBalanceAfter,
            "company_id"             => $companyId,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 43,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $companyId,

        ]);

    }

public function verifyWithIdentityPass(Request $request)
{
    $identityPassController = new IdentityPassController();

    logInfo([
        'bvn' => substr((string) $request->bvn, 0, 3)
            . '******'
            . substr((string) $request->bvn, -2),
        'dob' => $request->dob,
    ], "Starting Prembly BVN verification");


    $bvnResponse = $identityPassController->verifyBVNMobile(
        $request->bvn
    );


    /*
    |--------------------------------------------------------------------------
    | Convert object response to array if necessary
    |--------------------------------------------------------------------------
    */

    if (is_object($bvnResponse)) {

        $bvnResponse = json_decode(
            json_encode($bvnResponse),
            true
        );
    }


    logInfo([
        'status' => $bvnResponse['status'] ?? null,
        'message' => $bvnResponse['message'] ?? null,
        'response_code' => $bvnResponse['response_code'] ?? null,
    ], "Prembly BVN verification result");


    /*
    |--------------------------------------------------------------------------
    | Provider verification failed
    |--------------------------------------------------------------------------
    */

    $status = $bvnResponse['status'] ?? false;

    if ($status !== true && $status !== 'true') {

        return response()->json([
            'status' => false,
            'message' => $bvnResponse['message']
                ?? 'Failed to verify BVN',

            'response_code' => $bvnResponse['response_code']
                ?? null,
        ], 400);
    }


    /*
    |--------------------------------------------------------------------------
    | Provider verification succeeded
    |--------------------------------------------------------------------------
    */

    $apiDataBlock = $bvnResponse['data']['data'] ?? null;


    if (!$apiDataBlock) {

        logInfo([
            'response' => $bvnResponse,
        ], "Prembly BVN successful response missing data");

        return response()->json([
            'status' => false,
            'message' => "Internal payload data array is missing.",
        ], 422);
    }


    $firstName = $apiDataBlock["firstName"] ?? null;

    $lastName = $apiDataBlock["lastName"] ?? null;

    $dob = $apiDataBlock["dateOfBirth"]
        ?? $apiDataBlock["dob"]
        ?? null;

    $bvnFromResponse = $request->bvn;

    $phone = $apiDataBlock["phoneNumber"] ?? null;

    $address = $apiDataBlock["residentialAddress"] ?? null;


    /*
    |--------------------------------------------------------------------------
    | Make sure provider returned the required identity information
    |--------------------------------------------------------------------------
    */

    if (!$firstName || !$lastName || !$dob) {

        logInfo([
            'data' => $apiDataBlock,
        ], "Prembly BVN incomplete response");

        return response()->json([
            'status' => false,
            'message' => "Provider returned invalid or incomplete data structure.",
        ], 422);
    }


    /*
    |--------------------------------------------------------------------------
    | Compare DOB
    |--------------------------------------------------------------------------
    */

    try {

        $inputDateStandardized = \Carbon\Carbon::parse(
            $request->dob
        )->format('Y-m-d');

        $bvnDateStandardized = \Carbon\Carbon::parse(
            $dob
        )->format('Y-m-d');

    } catch (\Throwable $e) {

        logInfo([
            'message' => $e->getMessage(),
            'request_dob' => $request->dob,
            'provider_dob' => $dob,
        ], "Prembly BVN DOB parsing error");

        return response()->json([
            'status' => false,
            'message' => 'Unable to validate date of birth.',
        ], 400);
    }


    /*
    |--------------------------------------------------------------------------
    | DOB mismatch = verification failure
    |--------------------------------------------------------------------------
    | Because debit happens AFTER this function succeeds, the company
    | will NOT be charged here.
    |--------------------------------------------------------------------------
    */

    if ($inputDateStandardized !== $bvnDateStandardized) {

        logInfo([
            'requested_dob' => $inputDateStandardized,
            'verified_dob' => $bvnDateStandardized,
        ], "Prembly BVN DOB mismatch");

        return response()->json([
            'status' => false,
            'message' => 'BVN verified but date of birth does not match.',
        ], 400);
    }


    /*
    |--------------------------------------------------------------------------
    | Everything passed
    |--------------------------------------------------------------------------
    */

    return response()->json([
        'status' => true,
        'message' => "Account Verified Successfully",

        'data' => [
            'first_name' => trim($firstName),

            'last_name' => trim($lastName),

            'dob' => $inputDateStandardized,

            'bvn' => trim($bvnFromResponse),

            'phone' => $phone
                ? trim($phone)
                : null,

            'address' => $address
                ? trim($address)
                : null,
        ],
    ], 200);
}




public function debitCompanyWithSubscriptionBVN(Request $request, $companyId)
{
    try {

        logInfo($companyId, "Debit company id");

        /*
        |--------------------------------------------------------------------------
        | STEP 1: VERIFY BVN FIRST
        |--------------------------------------------------------------------------
        | Do NOT debit the company before the external verification succeeds.
        */

        logInfo([
            'company_id' => $companyId,
            'bvn' => substr((string) $request->bvn, 0, 3) . '******' . substr((string) $request->bvn, -2),
        ], "BVN verification before company debit");


        $verificationResponse = $this->verifyWithIdentityPass($request);


        /*
        |--------------------------------------------------------------------------
        | Convert JsonResponse to array so we can inspect the result.
        |--------------------------------------------------------------------------
        */

        if ($verificationResponse instanceof \Illuminate\Http\JsonResponse) {

            $verificationData = $verificationResponse->getData(true);

        } elseif (is_array($verificationResponse)) {

            $verificationData = $verificationResponse;

        } else {

            logInfo([
                'response_type' => gettype($verificationResponse),
            ], "Unexpected BVN verification response");

            return response()->json([
                'status' => false,
                'message' => 'Failed to verify BVN',
            ], 400);
        }


        logInfo([
            'status' => $verificationData['status'] ?? null,
            'message' => $verificationData['message'] ?? null,
        ], "BVN verification result before debit");


        /*
        |--------------------------------------------------------------------------
        | STEP 2: IF VERIFICATION FAILED, DO NOT CHARGE
        |--------------------------------------------------------------------------
        */

        if (($verificationData['status'] ?? false) !== true) {

            logInfo([
                'company_id' => $companyId,
                'message' => $verificationData['message'] ?? 'BVN verification failed',
            ], "BVN verification failed - company NOT debited");

            return response()->json([
                'status' => false,
                'message' => $verificationData['message']
                    ?? 'Failed to verify BVN',
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | STEP 3: BVN VERIFIED SUCCESSFULLY
        |--------------------------------------------------------------------------
        | Only now do we charge the company.
        */

        $charge = 50;

        logInfo([
            'company_id' => $companyId,
            'charge' => $charge,
        ], "BVN verified - proceeding with company debit");


        $debitCompany = debitCompanyWithSubscriptionBalance(
            $companyId,
            $charge,
            0
        );


        /*
        |--------------------------------------------------------------------------
        | STEP 4: MAKE SURE THE COMPANY HAS ENOUGH BALANCE
        |--------------------------------------------------------------------------
        */

        if (!$debitCompany['status']) {

            logInfo([
                'company_id' => $companyId,
                'message' => $debitCompany['message'] ?? null,
            ], "BVN verification succeeded but company debit failed");

            return response()->json([
                'status' => false,
                'message' => $debitCompany['message']
                    ?? 'Unable to process BVN verification charge',
            ], 400);
        }


        /*
        |--------------------------------------------------------------------------
        | STEP 5: RECORD THE BVN CHARGE
        |--------------------------------------------------------------------------
        */

        $this->debitBVNFromCompany(
            $debitCompany,
            $companyId,
            $charge
        );


        logInfo([
            'company_id' => $companyId,
            'charge' => $charge,
        ], "BVN company debit successful");


        /*
        |--------------------------------------------------------------------------
        | STEP 6: RETURN THE SUCCESSFUL VERIFICATION RESPONSE
        |--------------------------------------------------------------------------
        */

        return $verificationResponse;

    } catch (\Throwable $e) {

        logInfo([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], "debitCompanyWithSubscriptionBVN Error");

        return response()->json([
            'status' => false,
            'message' => 'Failed to process BVN verification',
        ], 400);
    }
}

public function verifyIdentityWithSafeHaven(Request $request)
{
    return $this->safeHavenIdentityCheckResponse($request);
}

public function safeHavenIdentityCheckResponse(Request $request)
{
    $safehavenController = new SafehavenController();

    if ($request->filled('nin')) {

        $response = $safehavenController->verifyNIN(
            $request->nin,
            $request->company_id
        );

    } else {

        $response = $safehavenController->verifyBVN(
            $request->bvn,
            $request->dob,
            $request->company_id
        );
    }

    logInfo($response, 'Safe Haven Verify BVN Response From Identiy Check Response');

    if (!($response['status'] ?? false)) {
        return response()->json([
            'status' => false,
            'message' => $response['message']
                ?? 'Failed to verify identity',
        ], 400);
    }

    if (($response['verification_pending'] ?? false) === true) {

    $identityId = $response['_id'] ?? $response['identityId'] ?? null;

    if (empty($identityId)) {
        \logInfo([
            'company_id' => $request->input('company_id'),
            'has_identity_id' => false,
        ], 'SafeHaven identity ID missing from verification response');

        return response()->json([
            'status' => false,
            'message' => 'SafeHaven did not return an identity ID. Please retry verification.',
        ], 502);
    }

    return response()->json([
        'status' => true,
        'message' => $response['message'] ?? 'OTP sent successfully',
        'data' => [
            'otp_required' => true,
            'identityId' => $identityId,
            'otpId' => $response['otpId'] ?? null,
            'phoneAndDobVerified' => $response['phoneAndDobVerified'] ?? false,
        ],
    ], 200);
 }

    return response()->json([
        'status' => true,
        'message' => 'Account Verified Successfully',

        'data' => [
            'firstName' =>
                $response['data']['firstName'] ?? null,

            'lastName' =>
                $response['data']['lastName'] ?? null,

            'middleName' =>
                $response['data']['middleName'] ?? null,

            'dateOfBirth' =>
                $response['data']['dateOfBirth'] ?? null,

            'phoneNumber' =>
                $response['data']['phoneNumber'] ?? null,

            'watchListed' =>
                $response['data']['watchListed'] ?? false,

            'watchListMessage' =>
                $response['data']['watchListMessage'] ?? '',
        ],
    ], 200);
}

    public function verifyBVNAlt(Request $request, $bvn)
    {
        if (! $bvn) {
            $ra = ["status" => false, "message" => "BVN is required"];
            return response()->json($ra, 406);
        }

        $safeHavenController = new SafehavenController();

        $result = $safeHavenController->verifyBVN($bvn);

        return response()->json($result, $result['status'] ? 200 : 400);
    }

    public function verifyMeterNumber(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "meter_number"      => 'required',
            "electricity_disco" => 'required',
            "type"              => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $company_debit_bankcode = $this->getCompanyBankCode();

        $verifyMeterResult = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('verifyMeterNumber', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('verifyMeterNumber', [
                'request' => $request,

            ]),
        };

        if (! $verifyMeterResult['status']) {
            return response()->json($verifyMeterResult, 400);
        }
        return response()->json($verifyMeterResult);
    }

    public function verifySmartCard(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "smart_card_number" => 'required|numeric|gt:0',
            "cable_tv_type"     => 'required',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        $company_debit_bankcode = $this->getCompanyBankCode();

        $verifyCableTV = match ($company_debit_bankcode) {

            90286 => $this->safeHavenControllerSercice('verifyCableTV', [
                'request' => $request,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('verifyCableTV', [
                'request' => $request,

            ]),
        };

        if (! $verifyCableTV["status"]) {
            return response()->json($verifyCableTV, 400);
        }
        return response()->json($verifyCableTV);
    }

#[OA\Get(
    path: "/get-banks",
    summary: "Get all available banks",
    description: "Retrieves the list of banks available for bank transfers. The optional type parameter controls the format of the bank list returned.",
    tags: ["Transfer - Wallet To Bank"],
    parameters: [
        new OA\Parameter(
            name: "type",
            in: "query",
            required: false,
            description: "Bank list format. Defaults to long.",
            schema: new OA\Schema(type: "string", default: "long", example: "long")
        )
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Banks fetched successfully",
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Banks fetched successfully"),
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "bank_name", type: "string", example: "Example Bank"),
                                new OA\Property(property: "bank_code", type: "string", example: "000001"),
                                new OA\Property(property: "short_code", type: "string", nullable: true, example: "EXB")
                            ]
                        )
                    )
                ]
            )
        )
    ]
)]
public function getAllBanks(Request $request)
{
    $type = $request->has('type') ? $request->type : 'long';

    $banks = \getBanksFromDB($type);

    return response()->json([
        'status' => true,
        'message' => 'Banks fetched successfully',
        'data' => $banks
    ]);
}

    public function transactionHistory(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 100;

        $transactions = transactions::where(['account_id' => $request->user()->accounts->first()->id]);
        // exclude bank transfer,sms charges,registration,
        $hidecharge = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? '1';

        $hideChargeLog = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? "1";

        \logInfo($hideChargeLog, "Hide Charge ");

        if ($hidecharge == '2') {
            // $get_list_of_excluded_transaction_ids = getTransactionIdByName(['USSD Session Charge','Physical Card Funding Charge','Virtual Card Funding Charge','USSD Wallet Funding Charge','Customer Whatsapp Charge','Company Whatsapp Charge','POS Credit Charge']);
            //$get_list_of_excluded_transaction_ids = [108, 107, 100, 99, 92, 91, 76, 62, 58, 55, 54, 51, 42, 41, 39, 38, 33, 27, 6];
            $get_list_of_excluded_transaction_ids = [125, 108, 107, 100, 99, 92, 91, 76, 55, 51, 27];
            $transactions                         = $transactions->whereNotIn('transaction_type_id', $get_list_of_excluded_transaction_ids);
        }
        if ($request->from != null && $request->to != null) {
            $transactions = $transactions->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to);

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();

            $transactions = $transactions->map(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; // 👈 add your parameter
                return $transaction;
            });
        } else {
            $transactions = $transactions->orderBy('created_at', 'DESC')->paginate($pageLimit);
            $transactions->getCollection()->transform(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; //add your parameter
                return $transaction;
            });
        }

//ransactionResource::collection($transactions)
        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions], 200);
    }

    public function transactionHistoryMobile(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 100;

        $transactions = transactions::where(['account_id' => $request->user()->accounts->first()->id]);
        // exclude bank transfer,sms charges,registration,
        $hidecharge = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? '1';

        $hideChargeLog = auth()->user()->company->Transactionsettings->hide_charge_on_statement ?? "1";

        \logInfo($hideChargeLog, "Hide Charge ");

        if ($hidecharge == '2') {
            // $get_list_of_excluded_transaction_ids = getTransactionIdByName(['USSD Session Charge','Physical Card Funding Charge','Virtual Card Funding Charge','USSD Wallet Funding Charge','Customer Whatsapp Charge','Company Whatsapp Charge','POS Credit Charge']);
            //$get_list_of_excluded_transaction_ids = [108, 107, 100, 99, 92, 91, 76, 62, 58, 55, 54, 51, 42, 41, 39, 38, 33, 27, 6];
            $get_list_of_excluded_transaction_ids = [125, 108, 107, 100, 99, 92, 91, 76, 55, 51, 27];
            $transactions                         = $transactions->whereNotIn('transaction_type_id', $get_list_of_excluded_transaction_ids);
        }
        if ($request->from != null && $request->to != null) {
            $transactions = $transactions->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to);

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();

            $transactions = $transactions->map(function ($transaction) {
                $transaction_remark              = transaction_remarks::where('id', $transaction->transaction_remarks_id)->first();
                $transaction->transaction_status = $transaction_remark->transaction_remark; // 👈 add your parameter
                return $transaction;
            });
        } else {
            return response()->json(['status' => true, 'message' => 'Transactions fetched failed', 'data' => "error"], 400);
        }

//ransactionResource::collection($transactions)
        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions], 200);
    }

    public function posTransactionHistory(Request $request)
    {

        $pageLimit = isset($request->limit) ? $request->limit : 20;

        $userId = auth()->user()->id;

        if ($request->from != null && $request->to != null) {
            $transactions = transactions::where('description', 'like', '%WirelessPay miniPos%')
                ->where('created_at', '>=', $request->from)
                ->where('created_at', '<=', $request->to)
                ->where(function ($query) use ($userId) {
                    $query->where('created_by', $userId)
                        ->orWhere('account_id', $userId);
                });

            $transactions = $transactions->orderBy('created_at', 'DESC')->get();
        } else {

            $transactions = transactions::where('description', 'like', '%WirelessPay miniPos%')
                ->where(function ($query) use ($userId) {
                    $query->where('created_by', $userId)
                        ->orWhere('account_id', $userId);
                })->orderBy('created_at', 'DESC')->paginate($pageLimit);
        }

        return response()->json(['status' => true, 'message' => 'Transactions fetched successfully', 'data' => $transactions]);
    }

    #[OA\Get(
    path: "/api/v1/get-data-bundles/{networkProvider}",
    summary: "Get Data Bundles",
    description: "Returns the available data bundles for the specified network provider. The network provider is passed as a path parameter and the bundles are fetched through the VTpass service.",
    tags: ["Data"],
    security: [["bearerAuth" => []]],
    parameters: [
        new OA\Parameter(
            name: "networkProvider",
            description: "Network provider for which data bundles should be retrieved",
            in: "path",
            required: true,
            schema: new OA\Schema(
                type: "string",
                example: "mtn"
            )
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: "Data Bundles fetched successfully",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: "status",
                        type: "boolean",
                        example: true
                    ),
                    new OA\Property(
                        property: "message",
                        type: "string",
                        example: "Data Bundles fetched successfully"
                    ),
                    new OA\Property(
                        property: "data",
                        type: "array",
                        items: new OA\Items(
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "code",
                                    type: "string",
                                    example: "mtn-1000"
                                ),
                                new OA\Property(
                                    property: "name",
                                    type: "string",
                                    example: "MTN 1GB Data Bundle"
                                ),
                                new OA\Property(
                                    property: "amount",
                                    type: "number",
                                    format: "float",
                                    example: 1000.00
                                ),
                            ]
                        )
                    ),
                ],
                type: "object"
            )
        ),
        new OA\Response(
            response: 401,
            description: "Unauthenticated"
        ),
        new OA\Response(
            response: 400,
            description: "Invalid or missing network provider"
        ),
        new OA\Response(
            response: 500,
            description: "Failed to fetch Data Bundles"
        )
    ]
)]
public function getDataBundles($networkProvider)
{
    $dataBundles = $this->vtPassControllerSercice('getDataBundles', [
        'networkProvider' => $networkProvider,
    ]);

    logInfo($dataBundles, "Get Data Bundles Service Response");

    if ($dataBundles['status']) {
        return [
            'status'  => true,
            'message' => 'Data Bundles fetched successfully',
            'data'    => $dataBundles['data']
        ];
    }

    return [
        'status'  => false,
        'message' => $dataBundles['message'] ?? 'Failed to fetch Data bundles',
        'data'    => $dataBundles['data'] ?? []
    ];
}

    public function getDataBundlesList($networkProvider)
    {
        $company_debit_bankcode = $this->getCompanyBankCode();

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getDataBundles', [
                'networkProvider' => $networkProvider,

            ]),
        };

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function getSavingsProducts()
    {
        $savingsProduct = savings_product::where('company_id', auth()->user()->company_id)->get();

        if ($savingsProduct) {
            return ['status' => true, 'message' => 'Savings Products fetched successfully', 'data' => $savingsProduct];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Savings', 'data' => []];
        }
    }

    public function getSubscriptions(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "cableTvType" => 'nullable',
        ]);

        if ($validation->fails()) {
            $ra = ["status" => false, "message" => $validation->errors()->all()[0]];
            return response()->json($ra, 406);
        }

        logInfo($request['cableTvType'], "VTPass Subscribe tv request 1");

        logInfo($request->input('cableTvType'), "VTPass Subscribe tv request 2");

        $cableTV = $request['cableTvType'] ?? $request->query('cableTvType');

        $company_debit_bankcode = $this->getCompanyBankCode();

        $dataBundles = match ($company_debit_bankcode) {

            90286 => $this->vtPassControllerSercice('getSubscriptions', [
                'cableTV' => $cableTV,

            ]), //7000299948
            default
                  => $this->vtPassControllerSercice('getSubscriptions', [
                'cableTV' => $cableTV,

            ]),
        };

        if (! $dataBundles['status']) {
            return response()->json($dataBundles, 400);
        }

        return response()->json($dataBundles);
    }

    public function getBettingCompanies(Request $request)
    {

        $cableTV = $request['cableTvType'];

        $endpoint = "betting";

        return response()->json(['status' => false, 'message' => 'This service is unavailable at the moment'], 400);

        $isDemo         = env('APP_MODE');
        $apiExchangeKey = env("API_SERVER_EXCHANGE_KEY");

        $bettings = \getRequest([], [], "/get_subscriptions.php?is_demo=$isDemo&api_exchange_key=$apiExchangeKey");

        if ($bettings['success']) {

            $providers = [];

            foreach ($bettings['data'] as $key => $bettingCompany) {
                $providers[] = ['providers' => $bettingCompany['provider']];
            }

            return response()->json(['status' => true, 'message' => 'Bettings fetched Successfuly', 'data' => $providers]);
        } else {
            return response()->json(['status' => false, 'message' => 'Failed to fetch bettings', 'data' => $bettings], 400);
        }
    }

    public function forwardToWebhookUrlAndSweepAccount($incomingRequest, $companyId)
    {

        $sweepAccount = false;

        try {

            $checkWebhook = ApiKey::where('company_id', $companyId)->first();

            if ($checkWebhook) {
                if ($checkWebhook->send_notification == 1) {
                    $sweepAccount = true;

                    $body = [
                        "account_number"        => $incomingRequest['nuban'],
                        "amount"                => $incomingRequest['amount'],
                        "transaction_reference" => $incomingRequest['account_reference'],
                        "narration"             => "Funds received from " . $incomingRequest['source_account_name'],
                        "status"                => $incomingRequest['status'],
                        "transaction_date"      => $incomingRequest['transaction_time'],
                    ];

                    $header = [
                        'Content-Type' => 'application/json',
                        'ApiKey'       => $checkWebhook->api_key,
                    ];
                    $response = Http::withHeaders($header)->post($checkWebhook->webhook_url, $body);

                    \logInfo($response->json(), "Forwarded webhook Response");
                } else {
                    \logInfo("Not sending wehook from settings");
                }
            } else {
                \logInfo("Not sending wehook");
            }
        } catch (Exception $e) {

            \logInfo($e->getMessage(), "Forward Webhook Error");
        }
        return $sweepAccount;
    }

    public function verifyBankAccountNumber($bankCode, $accountNumber)
    {

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => 'https://api.woven.finance/v2/api/nuban/enquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => "account_number=$accountNumber&bank_code=$bankCode",
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
                'api_secret:  00',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }
    public function getMeterType()
    {

        return json_encode(
            [
                ["id" => "prepaid", "name" => "Prepaid"],
                ["id" => "postpaid", "name" => "Postpaid"],
            ]
        );
    }
    public function getMeterService()
    {
        return json_encode(
            [
                ["id" => "aba-electric", "name" => "ABA Electricity"], ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "benin-electric", "name" => "Benin Electricity"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "kaduna-electric", "name" => "Kaduna Electricity"], ["id" => "yola-electric", "name" => "YOLA Electricity"],

            ]
        );
    }
    public function getMeterServiceType()
    {

        return response()->json(
            [
                'status'  => true,
                'message' =>
                'Response Successful',
                'data'    => [
                    'service' => [
                        ["id" => "aba-electric", "name" => "ABA Electricity"], ["id" => "abuja-electric", "name" => "Abuja Prepaid"], ["id" => "benin-electric", "name" => "Benin Electricity"], ["id" => "enugu-electric", "name" => "Enugu Prepaid"], ["id" => "ibadan-electric", "name" => "Ibadan Prepaid"], ["id" => "eko-electric", "name" => "Eko Prepaid"], ["id" => "ikeja-electric", "name" => "Ikeja Prepaid"], ["id" => "portharcourt-electric", "name" => "Port Harcourt Prepaid"], ["id" => "jos-electric", "name" => "Jos Prepaid"], ["id" => "kano-electric", "name" => "Kano Prepaid"], ["id" => "kaduna-electric", "name" => "Kaduna Electricity"], ["id" => "yola-electric", "name" => "YOLA Electricity"],

                    ], 'type' => [
                        ["id" => "prepaid", "name" => "Prepaid"],
                        ["id" => "postpaid", "name" => "Postpaid"],
                    ],

                ],
            ]
        );
    }

    public function getBets(Request $request)
    {

        $kudaController = new KudaController();

        $kudaController->transactionReference = "";
        $kudaController->wbaCharge            = "";
        $kudaController->virtualAccount       = "";
        $kudaController->amount               = "";
        $kudaController->webhookType          = "";
        $kudaController->description          = "";
        $kudaController->transactionDate      = "";
        $kudaController->request              = $request;
        $kudaController->fromTempWebhook      = "";

        $getBetsResult = $kudaController->getBet();

        if (! $getBetsResult['status']) {
            return response()->json($getBetsResult, 400);
        }
        return response()->json($getBetsResult);
    }

    public function getNetworks()
    {
        $network = [
            'network' => [
                ["id" => "mtn", "name" => "MTN"],
                ["id" => "glo", "name" => "GLO"],
                ["id" => "airtel", "name" => "Airtel"],

                ["id" => "etisalat", "name" => "9mobile"],
            ],
            'data'    => [
                "mtn"      => [
                    ["id" => "500", "name" => "500mb/30days - 350.00", "amount" => 350],
                    ["id" => "1000", "name" => "1.0GB/30days - 550.00", "amount" => 550],
                    ["id" => "2000", "name" => "2.0GB/30days - 750.00", "amount" => 750],
                    ["id" => "5000", "name" => "5.0GB/30days - 2,700.00", "amount" => 2700],
                    ["id" => "10000.01", "name" => "10.0GB/30days - 5,000.00", "amount" => 5000],
                ],
                "glo"      => [

                    ["id" => "1050.01", "name" => "1GB for 30Days - 1,100.00", "amount" => 1100],
                    ["id" => "2500.01", "name" => "2.5GB for 30Days - 1,500.00", "amount" => 1500],

                    ["id" => "5800.01", "name" => "5.8GB for 30Days - 2,500.00", "amount" => 2500],
                    ["id" => "10000.01", "name" => "10GB for 30Days - 3,500.00", "amount" => 3500],
                ],
                "airtel"   => [
                    ["id" => "999.00", "name" => "1.5GB/30days - 1,200.00", "amount" => 1200],
                    ["id" => "1499.01", "name" => "3.5GB/30days - 1,650.00", "amount" => 1650],
                    ["id" => "1999.00", "name" => "4.5GB/30days - 2,050.00 ", "amount" => 2050],

                    ["id" => "2499.01", "name" => "6.0GB/30days - 2,499.01", "amount" => 2550],
                    ["id" => "2999.02", "name" => "8.0GB/30days - 3,500", "amount" => 3500],

                ],
                "etisalat" => [
                    ["id" => "500.01", "name" => "500MB/30days - 550", "amount" => 550],
                    ["id" => "1500.01", "name" => "1.5GB/30days - 1,500", "amount" => 1500],
                    ["id" => "4500.01", "name" => "4.5GB/30days - 2,500", "amount" => 2500],

                    ["id" => "15000.01", "name" => "15GB/30days - 7,000", "amount" => 7000],

                    ["id" => "40000.01", "name" => "40.0GB/30days - 14,500", "amount" => 14500],
                ],
            ],
        ];
        return response()->json($network);
    }
    public function txnInfo(Request $request)
    {
        if ($request->is_agent != null) {
            $transaction = transactions::whereHas('beneficiary', function ($q) {
                $q->whereHas('user', function ($e) {
                    $e->where(['agent_id' => auth()->user()->id]);
                });
            })->where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->with('beneficiary')->first();
        } else {
            $transaction = transactions::whereHas('beneficiary', function ($q) {
                $q->where(['user_id' => auth()->user()->id]);
            })->where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->with('beneficiary')->first();
        }

        if ($transaction) {
            $transaction->id                     = 0;
            $transaction->account_id             = 0;
            $transaction->account_id             = 0;
            $transaction->branch_id              = null;
            $transaction->parent_transaction_id  = null;
            $transaction->company_id             = null;
            $transaction->company_balance_before = null;
            $transaction->company_amount         = null;
            $transaction->company_balance_after  = null;
            $transaction->transaction_type_id    = null;
            $transaction->created_by             = null;
            $transaction->modified_by            = null;
            return response()->json(['status' => true, 'message' => 'Transaction details fetched successfully', 'data' => $transaction], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid transaction reference'], 400);
        }
    }
    public function txnInfoTwo(Request $request)
    {
        $transaction = transactions::where(['transaction_reference' => $request->transaction_reference])->with('type')->with('remark')->with('creditDebitType')->first();
        if ($transaction) {
            $transaction->id                     = 0;
            $transaction->account_id             = 0;
            $transaction->account_id             = 0;
            $transaction->branch_id              = null;
            $transaction->parent_transaction_id  = null;
            $transaction->company_id             = null;
            $transaction->company_balance_before = null;
            $transaction->company_amount         = null;
            $transaction->company_balance_after  = null;
            $transaction->transaction_type_id    = null;
            $transaction->created_by             = null;
            $transaction->modified_by            = null;
            return response()->json(['status' => true, 'message' => 'Transaction details fetched successfully', 'data' => $transaction], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Invalid transaction reference'], 400);
        }
    }

    public function buyAirtimeTest(Request $request)
    {
        $type           = "electricity";
        $network        = $request->network;
        $accountName    = auth()->user()->first_name;
        $originalAmount = $request->amount;
        return $this->checkVTPASSBalance($originalAmount);
        $kudaController = new KudaController();
        return $kudaController->getBiller($type, $network);
    }

    public function twilioWebhook(Request $request)
    {
        \logInfo($request->all(), "Twilio Response Message");
        $twilioWebHook = new TwilioWhatsapp();
        return $twilioWebHook->getUpdates($request);
    }

    public function finovaWebhook(Request $request)
    {

        $secret         = 'finova-my-secret';
        $receivedSecret = $request->header('Signature');

        // Optional: log the full webhook
        \logInfo($request->all(), "Finova Received Webhook");

        // Example logic
        if ($request->has('data')) {
            // Process the payload
            $data = $request->input('data');

            \logInfo($data, "Finova ebhook Data Received:");
        }

        return response()->json(['status' => 'ok'], 200);
    }

    public function testAPIExternal(Request $request)
    {
        $remitaController = new RemittaController();

        //   return $remitaController->getElectricityCategory();
        $electrictyDisco = null;
        $transactionId   = generateTransactionReference();
        $meterType       = "prepaid";
        $meterNumber     = "45702489712";
        $amount          = 500;
        $phoneNumber     = "08067058555";
        return $remitaController->buyElectricity($transactionId, $electrictyDisco, $meterType, $meterNumber, $amount, $phoneNumber);

    }

    public function finova4tsWebhook(Request $request)
    {

        $secret         = 'finova-my-secret';
        $receivedSecret = $request->header('x-fortify-secret');

        if ($receivedSecret != null) {
            // Optional: log the full webhook
            \logInfo($request->all(), "4ts Finova Received Webhook");
            $amount               = $request->amount;
            $virtualAccount       = $request->account_number;
            $wb_account_detail_id = wb_account_details::where('virtual_accnum', $virtualAccount)->first();
            $acoountID            = $wb_account_detail_id->id;
            $user                 = User::where('id', $wb_account_detail_id->user_id)->first();
            $transactionService   = new fortifyTransactionService();
            $debitCustomer        = $transactionService->directDebitMandate($amount, $user);
            if (! $debitCustomer['status']) {
                response()->json("Could not debit cuustomer", 400);
            } else {

                return response()->json(['status' => 'ok', "message" => $debitCustomer], 200);
            }

        }

    }

    public function bridgeWebhook(Request $request)
    {

        $bridgeController = new BridgeController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        $handleWebhook = $bridgeController->handleBridgeWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Sudo result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);

    }

    public function ninetyPSBWebhook(Request $request)
    {

        $ninePSBController = new NinePSBController();
        // $verifyWebhook = $safeHavenController->verifyIncomingWebhook($request);

        if (! $request['type'] != "transfer") {
            response()->json(['status' => false, 'message' => 'Webhook is not of type Transfer'], 400);
        }

        $handleWebhook = $ninePSBController->handleNinePSBWebhook($request);

        $httpCode = ! isset($handleWebhook['httpCode']) ? 400 : 200;

        logInfo(json_encode($handleWebhook), "Handle web hook Ninepsb result");
        logInfo("$httpCode", "httpCode");

        return response()->json($handleWebhook, $httpCode);

    }

    public function reportTransaction(Request $request)
    {
        \logInfo(json_encode($request->ref_id), "Report transaction ref_id :");
        \logInfo(json_encode($request->reason), "Report transaction  reason:");
        $transaction = transactions::where(['transaction_reference' => $request->ref_id])->first();
        \logInfo(json_encode($transaction), "Report transaction :");
        $company = Company::where('id', $transaction->company_id)->first();
        $user    = User::where('company_id', $company->id)->where('user_type_id', 2)->first();
        $data    = [
            'subtext' => "Transaction issue for {$request->ref_id}",
            'reason' => $request->reason,
            'desc'   => "There is an issue with a transaction , {$request->ref_id}. ",
            'company_name' => $company->company_name,
            'user' => $company->company_name,
        ];
        \logInfo("$user->email", "Mail from user ");
        // initSMTPconfiguration($company->companyEmail);
        $subtext = "Transaction issue for {$request->ref_id}";
        $reason  = $request->reason;
        $msg     = "There is an issue with a transaction , {$request->ref_id}. ";
        $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
        return response()->json(['status' => 'ok'], 200);
    }

    public function paystackWebhook(Request $request)
    {
        $paystack = new PaystackService;
        $paystack->handleWebhook($request);
    }

    public function vtpassWebhook(Request $request)
    {
        $vtPassController = new VTPassController();
        $vtPassController->handleWebhook($request);
    }

public function checkVTPASSBalance($originalAmount)
{
    try {

        $vtPassController = new VTPassController();

        $response = $vtPassController->checkVTPASSBalance();

        logInfo($response, "VTPass Balance Validation Response");

        // VTPass balance request itself failed
        if (!isset($response['status']) || $response['status'] !== true) {
            return [
                'status'  => false,
                'message' => $response['message'] ?? 'Unable to verify VTPass balance',
                'reason'  => 'balance_check_failed',
            ];
        }

        // Make sure balance exists
        if (!isset($response['balance'])) {
            return [
                'status'  => false,
                'message' => 'Unable to verify VTPass balance',
                'reason'  => 'balance_not_available',
            ];
        }

        $vtpassBalance = (float) $response['balance'];
        $amount        = (float) $originalAmount;

        logInfo([
            'requested_amount' => $amount,
            'vtpass_balance'   => $vtpassBalance,
        ], "VTPass Balance Comparison");

        // VTPass does not have enough balance
        if ($amount > $vtpassBalance) {
            return [
                'status'          => false,
                'message'         => 'VTPass balance is insufficient to process this transaction',
                'reason'          => 'insufficient_balance',
                'vtpass_balance'  => $vtpassBalance,
                'requested_amount' => $amount,
            ];
        }

        return [
            'status'          => true,
            'message'         => 'VTPass balance is sufficient',
            'vtpass_balance'  => $vtpassBalance,
            'requested_amount' => $amount,
        ];

    } catch (\Exception $e) {

        logInfo($e->getMessage(), "VTPass Balance Validation Error");

        return [
            'status'  => false,
            'message' => 'Unable to verify VTPass balance. Please try again later',
            'reason'  => 'exception',
        ];
    }
}

    public function ninetyPSBTestHash(Request $request)
    {

        $ninePSBController = new NinePSBController();
        return $ninePSBController->getHashed($request);
    }

public function checkCustomerAndCompanyBalanceBeforeBuyAirtime(
    $customerAccountId,
    $companyId,
    $originalAmount,
    $customerCashback = 0,
    $companyCashback = null,
    $user = null
) {
    // Validate transaction amount
    if ($originalAmount <= 0) {
        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Invalid transaction amount",
        ];
    }

    // Get customer wallet
    $userWallet = user_wallet::where(
        'wb_account_detail_id',
        $customerAccountId
    )->first();

    if (!$userWallet) {
        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Invalid customer provided",
        ];
    }

    $customerInitialBalance = (float) $userWallet->balance;

    // Calculate cashback
    $customerCashbackAmount =
        (float) $customerCashback * (float) $originalAmount;

    $companyCashbackAmount =
        (float) $companyCashback * (float) $originalAmount;

    // Amount customer actually needs to cover
    $customerRequiredAmount =
        (float) $originalAmount - $customerCashbackAmount;

    // Check customer balance
    if ($customerInitialBalance < $customerRequiredAmount) {
        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Insufficient customer balance",
        ];
    }

    // Fetch company
    $company = Company::find($companyId);

    if (!$company || !$company->wallet) {
        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Invalid company or wallet not found",
        ];
    }

    $companyInitialBalance = (float) $company->wallet->balance;

    // Calculate the company's expected balance after transaction
    $companyAfterBalance =
        $companyInitialBalance - (float) $originalAmount;

    logInfo([
        'company_id'             => $company->id,
        'company_balance_before' => $companyInitialBalance,
        'transaction_amount'     => $originalAmount,
        'company_balance_after'  => $companyAfterBalance,
        'customer_balance'       => $customerInitialBalance,
        'customer_required'      => $customerRequiredAmount,
        'customer_cashback'      => $customerCashbackAmount,
        'company_cashback'       => $companyCashbackAmount,
    ], "Airtime Balance Check");

    // Company must be able to cover the transaction
    if ($companyInitialBalance < (float) $originalAmount) {
        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Switcher Error... Insufficient company balance",
        ];
    }

    return [
        "status"       => true,
        "responseCode" => 1,
        "message"      => "Amount is valid",
    ];
}

    public function checkCustomerAndCompanyBalanceBeforeBuyElectricity(
        $customerAccountId,
        $companyId,
        $originalAmount,
        $customerCharge,
        $companyCharge,
        $user = null
    ) {
        logInfo($customerAccountId, "checkCustomerAndCompanyBalanceBeforeBuyElectricity customerAccountId");
        logInfo($companyId, "checkCustomerAndCompanyBalanceBeforeBuyElectricity companyId");

        // Default response (prevents returning null)
        $response = [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Unknown error occurred",
        ];

        // Resolve user
        $currentUser = Auth::check() ? auth()->user() : $user;

        // Validate amount
        if ($originalAmount <= 0) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid transaction amount",
            ];
        }

        // Lock customer wallet for concurrency safety
        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)
            ->lockForUpdate()
            ->first();

        if (! $userWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid customer provided",
            ];
        }

        logInfo(json_encode($userWallet), "Customer wallet to be debited");

        $customerInitialBalance = $userWallet->balance;
        $totalDebit             = $originalAmount + $customerCharge;

        logInfo($originalAmount, "Original Amount");
        logInfo($customerCharge, "Customer Charge");

        // ✅ Step 1: Ensure customer has sufficient balance
        if ($customerInitialBalance < $totalDebit) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Insufficient customer balance",
            ];
        }

        // ✅ Step 2: Get company wallet
        $company = Company::find($companyId);

        if (! $company) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid company specified",
            ];
        }

        $companyWallet = company_wallet::where('company_id', $companyId)->lockForUpdate()->first();

        if (! $companyWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Company wallet not found",
            ];
        }

        logInfo(json_encode($companyWallet), "Company wallet to be debited");

        $companyIDs       = [1, 29, 36, 63, 67, 92, 97];
        $companyIDsStatus = in_array($company->id, $companyIDs);

        $companyAfterBalance = $companyWallet->balance - ($originalAmount + $companyCharge);

        logInfo($companyAfterBalance, "Company after balance");

        // ✅ Step 3: Ensure company wallet has safe limits
        if ($companyWallet->balance > 100000 && $companyAfterBalance > 100000) {
            $companyIDsStatus = true;
        }

        if (($companyWallet->balance >= ($originalAmount + $companyCharge)) && $companyIDsStatus) {
            $response = [
                "status"       => true,
                "responseCode" => 1,
                "message"      => "Amount is valid",
            ];
        } else {
            $response = [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Switcher-related error. Please contact support",
            ];
        }

        return $response;
    }

    public function getPOSBalance(Request $request)
    {
        $userPOS = AgentTerminal::where('user_id', auth()->user()->id)->first();

        if ($userPOS != null) {

            return response()->json(["status" => true,
                'data'                            => $userPOS->balance], 200);
        } else {
            return response()->json(["status" => true,
                'message'                         => "POS does not "], 400);
        }

    }

    public function processVerifyAndReverseTransaction(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "transaction_reference" => "required|string",
        ]);

        $partnerType = "others";

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        $transaction = transactions::where('transaction_reference', $request->transaction_reference)->first();

        logInfo($transaction, "transaction_reference reverse mobile");

        if (! $transaction) {
            return response()->json(['status' => 0, 'message' => "Invalid Transaction reference"]);
        }

        if ($transaction->company_id == 61) {
            return response()->json(['status' => 0, 'message' => "Blocked Reversal"]);
        }

        if ($transaction->transaction_type_id != 8) {
            return response()->json(['status' => 0, 'message' => "This transaction is not a bank transfer"]);
        }

        $startingDate    = Carbon::parse('2026-05-12 00:00:00');
        $transactionDate = Carbon::parse($transaction->created_at);

        logInfo($transactionDate, "transaction_reference reverse mobile transactionDate");

        if ($startingDate->gt($transactionDate)) {
            return response()->json(['status' => 0, 'message' => "This transaction cannot be reversed"]);
        }

        if ($transaction->transaction_remarks_id == 5) {
            return response()->json(['status' => 0, 'message' => "Transaction has already been reversed"]);
        }

        /*  if ($transaction->transaction_remarks_id == 1 || $transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);
        }*/

        if ($transaction->transaction_remarks_id == 6) {
            return response()->json(['status' => 0, 'message' => "Transaction cannot be reversed"]);

        }

        if ($transaction->created_at > Carbon::now()->subMinutes(30)) {
            return response()->json(['status' => 0, 'message' => "Transaction must be at least 30 minutes before the transaction status can be confirmed"]);
        }

        $affliateCustomerCharge = 0;
        $affliateCompanyCharge  = 0;
        $whoToCredit            = $transaction->account_id == null ? 'company' : 'both';

        $childTransaction = transactions::where('parent_transaction_id', $transaction->id)->first();

        if ($childTransaction) {
            $affliateCustomerCharge = $childTransaction->amount;
            $affliateCompanyCharge  = $childTransaction->company_amount;
        }

        $kudaController = new KudaController();

        $verifyTransaction = $kudaController->verifyTransaction($request->transaction_reference);
        logInfo(json_encode($verifyTransaction), "transaction_reference reverse verifyTransaction mobile");

        if ($verifyTransaction['status'] == "03") {
            $partnerType            = "kuda_business";
            $kudaBusinessController = new KudaBusinessController();
            $verifyTransaction      = $kudaBusinessController->verifyTransaction($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType         = "safehaven";
            $safeHavenController = new SafehavenController();
            $verifyTransaction   = $safeHavenController->getTransactionStatus($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "03") {
            $partnerType = "monnify";

            $monnifyController = new MonnifyController();
            $verifyTransaction = $monnifyController->getTransactionStatus($request->transaction_reference);
        }

        if ($verifyTransaction['status'] == "00") {

            $transaction->transaction_remarks_id = 2;
            $transaction->save();

            $verifyTransaction['status'] = 1;

            return response()->json($verifyTransaction);
        } else {

            logInfo($verifyTransaction, "Verify Transaction Before");
            if ($verifyTransaction['status'] == "02") {

                $verifyTransaction['status'] = 1;

                if ($partnerType == "safehaven" || $partnerType == "others" || $partnerType == "kuda_business") {

                    logInfo($transaction, "Transaction Records for Safe haven");
                    $totalCustomerAmount = $transaction->company_amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->company_amount + $affliateCompanyCharge;
                } else if ($partnerType != "safehaven") {
                    $totalCustomerAmount = $transaction->amount + $affliateCustomerCharge;
                    $totalCompanyAmount  = $transaction->amount + $affliateCompanyCharge;
                }

                $reversalResult = debitReversal($request->transaction_reference, $transaction->account_id, $transaction->company_id, $totalCustomerAmount, $totalCompanyAmount, $whoToCredit);

                if (! $reversalResult['status']) {
                    $reversalResult['status'] = 0;
                    return response()->json($reversalResult);
                }
            } else {
                $verifyTransaction['status'] = 0;
            }

            logInfo($verifyTransaction, "Verify Transaction Res");
            return response()->json($verifyTransaction);
        }

    }

    public function verifyAndReverseTransactionAdmin(Request $request)
    {

        $validation = Validator::make($request->all(), [
            "transaction_reference" => "required|string",
        ]);

        $partnerType = "others";

        if ($validation->fails()) {
            return response()->json(['status' => 0, 'message' => $validation->errors()->all()[0]]);
        }

        return $this->processVerifyAndReverseTransaction($request);

    }

    public function createPhysicalCard(Request $request)
    {
        // Disable service (optional)
        // return response()->json(['status' => 0, 'message' => 'This service has been disabled']);

        $transactionId                   = generateTransactionReference();
        $customer                        = null;
        $userID                          = $request->user_id;
        $first_name                      = $request->first_name;
        $last_name                       = $request->last_name;
        $customer_sudo_account_id        = $request->customerId;
        $preferred_virtual_card_currency = 1;
        $auth                            = User::where('id', $userID)->first();

        try {
            // Call Safehaven API for physical card creation
            $safehaven    = new SafehavenController();
            $cardResponse = $safehaven->createPhysicalCard(
                $request,
                $transactionId,
                $customer,
                $customer_sudo_account_id
            );

            if (! $cardResponse['status']) {
                //  DB::rollback();
                return response()->json(['status' => 0, 'message' => $cardResponse['message']]);
            } else {

                $card2FAResponse = $safehaven->enrollcard2fa($cardResponse['result']['data']["_id"]);
                if ($card2FAResponse) {
                    $cardDetailsResponse = $safehaven->getPhysicalCardDetials($cardResponse['result']['data']["_id"]);
                }

            }

            $cardTypeMap = [
                'visa'       => 1,
                'verve'      => 2,
                'mastercard' => 3,
                'afrigo'     => 4,
            ];

// Normalize input to lowercase (to avoid case issues)
            $inputCardType = strtolower($request->card_type);

// Get matching code (or null if not found)
            $cardTypeCode = $cardTypeMap[$inputCardType] ?? null;

            UserCard::updateOrCreate(
                [
                    "sudo_customer_id" => $cardResponse['customer_sudo_account_id'],

                    "exp_date"         => $cardResponse['result']['data']["expiryYear"] ?? "",
                ],
                [
                    "user_id"   => $userID,
                    "card_name" => "{$first_name} {$last_name}",
                    "card_number"      => $cardResponse['result']['data']["maskedPan"],
                    "masked_pan"       => $cardResponse['result']['data']["maskedPan"],
                    "card_category_id" => 2,
                    "card_type_id"     => $cardTypeCode,
                    "card_reference"   => $cardResponse['result']['data']["_id"],
                    "currency_id"      => $preferred_virtual_card_currency,
                    "cvv"              => "",
                    "created_by"       => $userID,

                ]);

            // Save card details
            /*   UserCard::create([
                "user_id"   => $customer->id,
                "card_name" => "{$customer->first_name} {$customer->last_name}",
                "card_type_id"     => $cardTypeCode,
                "card_category_id" => 2,
                "card_number"      => $cardResponse['result']['data']["maskedPan"],
                "masked_pan"       => $cardResponse['result']['data']["maskedPan"],
                "card_reference"   => $cardResponse['result']['data']["_id"],
                "currency_id"      => $companySettings->preferred_virtual_card_currency,
                "exp_date"         => $cardResponse['result']['data']["expiryYear"] ?? "",
                "cvv"              => "",
                "created_by"       => $auth->id,
                "sudo_customer_id" => $cardResponse['sudo_customer_id'],
            ]);*/

            CardRequestLog::create([
                "user_id"    => $userID,
                "company_id" => $auth->company_id,
            ]);

            $pin = $cardDetailsResponse['data']['defaultPin'];
            return response()->json(['status' => 1, 'message' => "Card mapped successfully Deafult Pin is {$pin} ", 'data' => $cardResponse]);

        } catch (\Exception $e) {
            // DB::rollback();
            return response()->json(['status' => 0, 'message' => $e->getMessage()]);
        }
    }

    public function getGiftCardSubCategories()
    {
        $neonDataApi = new NeonDataApiController();
        return $neonDataApi->getGiftCardSubCategories();
    }

    public function postGiftTransaction(Request $request)
    {
        $neonDataApi = new NeonDataApiController();
        $response    = $neonDataApi->giftCardTransaction($request);
        if ($response) {
            return response()->json(['status' => 1, 'message' => "Gift Card Transaction Successful"], 200);
        } else {
            response()->json(['status' => 0, 'message' => "Gift Card Transaction failed"], 400);
        }
    }

    public function createVPayAccount(Request $request)
    {
        $vpay = new VPayController();

        $companyRecord    = Company::where('id', $request->company)->first();
        $userRecord       = User::where('company_id', $request->company)->where('user_type_id', 2)->first();
        $businessname     = $companyRecord->short_name;
        $email            = $userRecord->email;
        $phone            = $userRecord->phone;
        $contactfirstname = $businessname;
        $contactlastname  = $companyRecord->first_name;
        $bvn              = $userRecord->bvn;

        return $vpay->createAccount($businessname, $email, $phone, $contactfirstname, $contactlastname, $bvn);

    }

    public function setSpendSave(Request $request)
    {
        $user       = auth('api')->user();
        $company_id = $user->company_id;
        $charge     = $request->spend_save_charge_or_percentage / 100;
        $settings   = SpendSave::where('user_id', $user->id)->first();
        if ($request->spend_save_charge_or_percentage != null) {
            $charge = ($request->spend_save_charge_or_percentage / 100);
        } else {
            $charge = $settings->spend_save_charge_or_percentage;
        }

        $newSettings = SpendSave::updateOrCreate(
            [
                "user_id" => $user->id,

            ],
            [

                "company_id"                      => $company_id,
                "spend_save_charge_or_percentage" => $charge,
                "balance"                         => $settings->balance ?? 0,
                "status_id"                       => $request->status ?? 1,

            ]);

        return response()->json(["status" => 1, "message" => "Spend Save settings is saved", "spend_save_charge" => $newSettings->spend_save_charge_or_percentage]);

    }

public function withdrawSpendSave(Request $request)
{
    $user = auth('api')->user();
    
    // FIX: Read 'amount' instead of 'final_amount' if that's what your API payload passes
    $amount = (double) ($request->amount ?? $request->final_amount);

    if ($amount < 50) {
        return response()->json([
            'status' => false, 
            'message' => 'Transfer Amount cannot be less than 50'
        ], 400);
    }

    // Validate account product charges using the correct amount variable
    $chargeValidation = $this->validateAccountProductCharges($amount);
    if (! $chargeValidation['status']) {
        return response()->json($chargeValidation, 400);
    }

    // Calculate total amount (withdrawal + charges)
    $charge      = $this->getChargeAmount($chargeValidation, $user);
    $totalAmount = $charge + $amount; // FIX: Use the validated local $amount variable

    // Process Spend/Save debit
    $debitResult = debitCustomerWithdrawSpendSave($user->id, $totalAmount);

        if (! $debitResult['status']) {
            return response()->json([
                'status'  => false,
                'message' => $debitResult['message'] ?? 'Withdrawal failed',
            ], 400);
        }

        $charge      = (double) $charge;
        $totalAmount = (double) $totalAmount;

        // Perform database transaction
        try {
            DB::transaction(function () use ($user, $request, $charge, $totalAmount, $debitResult) {
                $this->createWithdrawalTransactions($user, $request, $charge, $totalAmount, $debitResult);
            });
        } catch (\Exception $e) {
            \Log::error('Withdrawal transaction failed', [
                'user_id' => $user->id,
                'amount'  => $request->amount,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Transaction failed. Please try again.',
            ], 500);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Withdrawal completed successfully',
            'data'    => [
                'amount_withdrawn' => $request->amount,
                'charge'           => $charge,
                'total_debited'    => $totalAmount,
                'balance_after'    => $debitResult['balance_after'],
            ],
        ]);
    }

/**
 * Validate account product charges
 *
 * @param float $finalAmount
 * @return array
 */
    private function validateAccountProductCharges(float $finalAmount): array
    {
        $validationResult = $this->accountProductCharges($finalAmount);

        if (! $validationResult['status']) {
            return [
                'status'  => false,
                'message' => $validationResult['message'] ?? 'Invalid product charges',
            ];
        }

        return $validationResult;
    }

/**
 * Get charge amount
 *
 * @param array $chargeValidation
 * @param mixed $user
 * @return float
 */
    private function getChargeAmount(array $chargeValidation, $user): float
    {
        if (isset($chargeValidation['data']['charge'])) {
            return $chargeValidation['data']['charge'];
        }

        return $user->company->setting->min_withdraw_charge ?? 0;
    }

/**
 * Create withdrawal transactions
 *
 * @param mixed $user
 * @param Request $request
 * @param float $charge
 * @param float $totalAmount
 * @param array $debitResult
 * @return void
 */
    private function createWithdrawalTransactions($user, Request $request, float $charge, float $totalAmount, array $debitResult): void
    {
        // Get ledger settings
        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(104, $user->company_id);

        // Create primary withdrawal transaction
        $primaryTransaction = transactions::create([
            'balance_before'         => $debitResult['balance_before'],
            'amount'                 => $request->amount,
            'account_id'             => $user->id,
            'balance_after'          => $debitResult['balance_after'],
            'company_balance_before' => 0,
            'company_balance_after'  => 0,
            'company_amount'         => 0,
            'description'            => 'Withdrawal from Spend/Save',
            'branch_id'              => $user->branch_id,
            'company_id'             => $user->company_id,
            'transaction_reference'  => generate_txnID(),
            'transaction_remarks_id' => 2,
            'transaction_type_id'    => 104,
            'credit_debit_type_id'   => 1,
            'created_date'           => now()->toDateString(),
            'created_by'             => $user->id,
            'ledger_cr'              => $ledgerSettings->ledger_cr ?? null,
            'ledger_dr'              => $ledgerSettings->ledger_dr ?? null,
        ]);

        // Create charge transaction if applicable
        if ($charge > 0) {
            transactions::create([
                'balance_before'         => $debitResult['balance_before'],
                'company_balance_before' => 0,
                'amount'                 => $charge,
                'company_amount'         => 0,
                'balance_after'          => $debitResult['balance_before'],
                'company_balance_after'  => 0,
                'description'            => 'Charge from Spend/Save withdrawal',
                'company_id'             => $user->company_id,
                'branch_id'              => $user->branch_id,
                'parent_transaction_id'  => $primaryTransaction->id,
                'transaction_reference'  => generateTransactionReference(),
                'transaction_remarks_id' => 2,
                'transaction_type_id'    => 27,
                'credit_debit_type_id'   => 2,
                'created_date'           => now()->toDateString(),
                'created_by'             => 1, // System user
            ]);
        }
    }

    public function getSpendSave(Request $request)
    {
        $user       = auth('api')->user();
        $company_id = $user->company_id;
        $settings   = SpendSave::where('user_id', $user->id)->first();

        return response()->json(["status" => 1, "message" => "Spend Save settings", "spend_save_charge" => $settings->spend_save_charge_or_percentage, "balance" => $settings->balance, "spend_save_status" => $settings->status_id]);

    }

    public function getUserToken(Request $request)
    {
        $user  = auth('api')->user();
        $token = generateToken();
        $user->update([
            'transfer_token' => $token,

        ]);

        return response()->json(["status" => 1, "message" => "Token Generated Successflly", "token" => $token]);

    }

    public function getUserTokenSchedule(Request $request)
    {
        $user  = auth('api')->user();
        $token = generateToken();
        $user->update([
            'transfer_schedule_token' => $token,

        ]);

        return response()->json(["status" => 1, "message" => "Token Generated Successflly", "token" => $token]);

    }

    public function scheduleTransfer(Request $request)
    {

        $currentRequest = $request->all();

        try {

            foreach ($currentRequest['data'] as $data) {

                $user = User::where('uuid', $data['user_id'])->first();

                if ($data['token'] != $user->transfer_schedule_token) {
                    $schedule = ScheduledPayment::where('status', 'pending')->first();
                    if (isset($schedule->status)) {
                        $data['token'] = $user->transfer_schedule_token;
                    }
                }

                ScheduledPayment::create([

                    'user_id'           => $user->id,

                    'recipient_name'    => $data['recipient_name'],

                    'recipient_account' => $data['recipient_account'],

                    'recipient_bank'    => $data['recipient_bank'],

                    'bank_code'         => $data['bank_code'],

                    'amount'            => $data['amount'],

                    'transaction_pin'   => $data['token'],

                    'scheduled_date'    => $data['scheduled_date'],

                    'scheduled_time'    => $data['scheduled_time'],

                ]);
            }
            return response()->json(["status" => 1, "message" => "Payment scheduled successfully."], 200);

        } catch (\Exception $e) {

            \Log::error("Payment scheduled Failed", [

                'error'  => $e->getMessage(),
                'Line: ' => $e->getLine(),
            ]);

            return response()->json(["status" => 0, "message" => "error"], 400);
        }
    }

    public function getscheduleTransfer(Request $request)
    {
        $user = auth('api')->user();
        $data = ScheduledPayment::where('user_id', $user->id)->paginate(50);

        return response()->json(['status' => true, 'message' => 'ScheduledTransfer Fetched successfully', 'data' => $data]);

    }

    public function scheduleSalaryTransfer(Request $request)
    {

        $currentRequest = $request->all();

        try {

            foreach ($currentRequest['data'] as $data) {

                $user = User::where('uuid', $data['user_id'])->first();

                CustomerSalary::updateOrCreate(
                    [
                        'recipient_account' => $data['recipient_account'],
                    ],
                    [

                        'user_id'        => $user->id,

                        'recipient_name' => $data['recipient_name'],

                        'recipient_bank' => $data['recipient_bank'],

                        'bank_code'      => $data['bank_code'],

                        'amount'         => $data['amount'],

                        'scheduled_date' => $data['scheduled_date'],

                        'scheduled_time' => $data['scheduled_time'],

                    ]);
            }
            return response()->json(["status" => 1, "message" => "Payment scheduled successfully."], 200);

        } catch (\Exception $e) {

            \Log::error("Payment scheduled Failed", [

                'error'  => $e->getMessage(),
                'Line: ' => $e->getLine(),
            ]);

            return response()->json(["status" => 0, "message" => "error"], 400);
        }
    }

    public function getscheduleSalaryTransfer(Request $request)
    {
        $user = auth('api')->user();
        $data = CustomerSalary::where('user_id', $user->id)->paginate(50);

        return response()->json(['status' => true, 'message' => 'Scheduled Salary Fetched successfully', 'data' => $data]);

    }

    public function initiateCreateSafehavenMaintenanceAccount(Request $request)
    {

        $company = Company::with('allStaff')->find($request->company);

        $admin = $company->allStaff->first();

        $companyIDforSafeHavenAccountCheck = 14; //finova account

        $this->safeHavenIdentityCheckResponse($admin->bvn, $companyIDforSafeHavenAccountCheck);
    }

    public function createSafehavenMaintenancAccount(Request $request)
    {
        $company = Company::with('allStaff')->find($request->company);

        $admin = $company->allStaff->first();

        if (! $admin) {
            \logInfo(["company_id" => $company->id, "error" => "No admin found"], 'Create Safehaven Account');
            response()->json(['status' => 0, 'message' => "No admin found"], 400);
        }
        $firstName = $company->short_name;

        $lastName = "";

        $email = $admin->email;

        $phone = $admin->phone;

        $customerReference = generateTransactionReference();

        $bvn = $admin->bvn;

        $otp = $request->otp;

        $bvnId = $request->bvnId;

        $companyIDforSafeHavenAccountCheck = 14; //finova account

        $safeHavenController = new SafehavenController();

        return $safeHavenController->createVirtualAccount($firstName, $lastName, $email, $phone, $customerReference, $bvn, $otp, $bvnId, $companyIDforSafeHavenAccountCheck);
    }

    public function generate_end_of_day(Request $request)
{
    

    $filter = $request->query('search', ['value' => ''])['value'];
    $from   = $request->query('from');
    $to     = $request->query('to');

    // Build the base query
    $query = transactions::join('wb_account_details', 'wb_account_details.id', '=', 'transactions.account_id')
        ->join('users', 'users.id', '=', 'wb_account_details.user_id')
        ->join('transaction_types', 'transactions.transaction_type_id', '=', 'transaction_types.id')
        ->join('credit_debit_types', 'transactions.credit_debit_type_id', '=', 'credit_debit_types.id')
        ->groupBy('transactions.transaction_type_id', 'transactions.credit_debit_type_id', 'credit_debit_types.type', 'transaction_types.transaction_type')
        ->select(
            'transactions.transaction_type_id',
            'transactions.credit_debit_type_id',
            'credit_debit_types.type as credit_debit_type_name',
            'transaction_types.transaction_type',
            DB::raw('sum(transactions.amount) as total')
        )
        ->where([
            'users.user_type_id' => '1',
            'users.company_id' => auth()->user()->company_id,
            'transactions.created_by' => auth()->user()->id,
            'transactions.transaction_status_id' => 1,
            'users.status_id' => 1,
        ]);

    // Role‑based filter
    if (!cando('root')) {
        $query->where('users.account_officer_id', auth()->user()->id);
    }

    // Date range
    if ($from && $to) {
        $query->whereDate('transactions.created_date', '<=', add_day_to_date(0, $to))
              ->whereDate('transactions.created_date', '>=', add_day_to_date(0, $from));
    } else {
        $query->whereDate('transactions.created_date', '=', date('Y-m-d'));
    }

    // Search filter
    if (!empty($filter)) {
        $query->where(function ($q) use ($filter) {
            $q->where('transactions.transaction_reference', 'like', '%' . $filter . '%')
              ->orWhere('transaction_types.transaction_type', 'like', '%' . $filter . '%');
        });
    }

    // (Optional) Sorting / pagination – uncomment if needed
    // $order = $request->query('order', [1, 'desc']);
    // $sortColumns = [0 => 'transaction_types.transaction_type', 1 => 'transactions.amount'];
    // $query->orderBy($sortColumns[$order[0]['column']], $order[0]['dir'])
    //       ->skip($request->query('start', 0))
    //       ->take($request->query('length', 2000));

    $results = $query->get();

    $transactions = [];
    $totalCredit = 0;
    $totalDebit  = 0;
    $balance     = 0;

    foreach ($results as $item) {
        $amount = (float) $item->total;
        $balance += $amount; // careful: credit adds, debit subtracts – but we compute separately

        if ($item->credit_debit_type_id == 1) { // credit
            $totalCredit += $amount;
        } elseif ($item->credit_debit_type_id == 2) { // debit
            $totalDebit += $amount;
        }

        $transactions[] = [
            'type'       => $item->transaction_type,
            'amount'     => $amount,
            'credit_debit_type_id' => $item->credit_debit_type_id,
            // you could also add 'credit_debit_type_name' => $item->credit_debit_type_name
        ];
    }

    // Compute final balance (credit - debit)
    $balance = $totalCredit - $totalDebit;

    // Currency symbol
    $currency = auth()->user()->company->currency->symbol ?? '';

    $response = [
        'transactions' => $transactions,
        'summary' => [
            'total_credit' => $totalCredit,
            'total_debit'  => $totalDebit,
            'balance'      => $balance,
            'currency'     => $currency,
        ],
    ];

    return response()->json($response);
}

    private function createTransanctionforUtilityPayment(Request $request, $transactionId, $customerBalance, $transaction_type, $description)
    {

        $amount      = 0;
        $totalAmount = 0;

        $amount      = $request->final_amount;
        $totalAmount = $request->final_amount;

        $ledger_cr = null;
        $ledger_dr = null;

        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transaction_type, \auth()->user()->company_id);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        $transaction = transactions::create([
            "balance_before"         => $customerBalance['balance'],
            "amount"                 => $totalAmount,
            "account_id"             => $customerBalance['account_id'],
            "description"            => $description,
            "transaction_reference"  => $transactionId,
            "company_balance_before" => $customerBalance["company_balance"],
            "company_amount"         => 0,
            "company_id"             => auth()->user()->company_id,
            "transaction_remarks_id" => 6,
            "branch_id"              => \auth()->user()->branch_id,
            "transaction_type_id"    => $transaction_type,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => auth()->user()->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);
    }

    private function sendTransactionReportEmail($subtext, $msg, $reason, $user)
    {

        $data = [
            'subtext'      => $subtext,
            'reason'       => $reason,
            'desc'         => $msg,
            'company_name' => $user->company->company_name,
            'user'         => $user->company->company_name,
        ];

        try {
            Mail::to($user->email)->send(new TransactionReport($data));
        } catch (\Throwable $e) {
            \Log::error("Failed to send TransactionReport email: " . $e->getMessage());
            // continue silently
        }
    }

    private function checkCompanyBalanceThresholdTransaction($amount)
    {

        $response   = [];
        $userWallet = company_wallet::where('company_id', auth()->user()->company_id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($amountLeft, "amountLeft for company");

        if ($amount > 0) {

            if ($amountLeft < 100001) {
                $response = ["status" => false, 'message' => "Issue with this service. Contact Support"];
            } else {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => auth()->user()->company_id, 'balance' => $userWallet->balance];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;

    }

    private function vtPassControllerSercice($action, array $data = [])
    {

        $transactionId      = $data['transactionId'] ?? null;
        $networkProvider    = $data['networkProvider'] ?? null;
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;

        $vtPassController = new VTPassController();

        if ($action == "buyAirTime") {
            $networkProvider = $request->network_provider == '9mobile' ? 'etisalat' : $request->network_provider;

            $service = $vtPassController->buyAirtime($transactionId, $networkProvider, $request->final_amount, $request->phone_number);
        }
        if ($action == "buyData") {
            $service = $vtPassController->buyData($transactionId, $request->network_provider, $request->data_plan, $selectedDataBundle['variation_amount'], $request->phone_number);
        }
        if ($action == "buyElectricity") {
            $service = $vtPassController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number
            );
            logInfo($service, "VTPass Electricity Response");
        }
        if ($action == "subscribeCableTV") {
            $subscribeCableTV = $vtPassController->subscribeCableTV($transactionId, $request->cable_tv_type, $request->subscription_plan, $request->smart_card_number, $request->final_amount, $request->phone_number);
        }
        if ($action == "verifyMeterNumber") {
            $service = $vtPassController->verifyMeterNumber($request->meter_number, $request->electricity_disco, $request->type);
        }
        if ($action == "verifyCableTV") {
            $service = $vtPassController->verifyCableTV($request->smart_card_number, $request->cable_tv_type);
        }
        if ($action == "getDataBundles") {

            $service = $vtPassController->getDataBundles($networkProvider);
        }

        if ($action == "getDataBundlesList") {
            $networkProvider = $request->network_provider == '9mobile' ? 'etisalat' : $request->network_provider;
            $service         = $vtPassController->getDataBundles($networkProvider);
        }

        if ($action == "getSubscriptions") {

            $service = $vtPassController->getSubscriptions($cableTV);
        }

        return $service;
    }

    private function safeHavenControllerSercice($action, array $data = [])
    {

        $transactionId      = $data['transactionId'] ?? null;
        $networkProvider    = $data['networkProvider'] ?? null;
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;

        $safeHavenController = new SafehavenController();
        $company_id          = company_wallet::where('company_id', auth()->user()->company_id)->first()->company_id;
        $company_id          = (int) $company_id;

        $getService = $safeHavenController->getService($action, $company_id);

        if ($request->network_provider != null) {
            $networkProvider = strtolower($request->network_provider);

            $networkProvider = preg_replace('/-data$/', '', $networkProvider);

            $networkProvider    = $networkProvider === '9mobile' ? 'etisalat' : $networkProvider;
            $getServiceCategory = $safeHavenController->getServiceCategory($getService, $company_id, strtoupper($networkProvider));
        } else {
            if ($request->electricity_disco != null) {
                $disco = $request->electricity_disco;
                $map   = [
                    'abuja-electric'        => 'AEDC',
                    'ibadan-electric'       => 'IBEDC',
                    'eko-electric'          => 'EKEDC',
                    'ikeja-electric'        => 'IKEDC',
                    'portharcourt-electric' => 'PHEDC',
                    'jos-electric'          => 'JEDC',
                    'kano-electric'         => 'KEDCO',
                    'enugu-electric'        => 'EEDC',
                    'yola-electric'         => 'YEDC',
                    'benin-electric'        => 'BEDC',
                    'kaduna-electric'       => 'KAEDC',
                    // future actions can go here
                ];

                $provider = $map[$disco];

            }
            $getServiceCategory = $safeHavenController->getServiceCategory($getService, $company_id, strtoupper($provider));
        }

        $getServiceCategoryID = $getServiceCategory['data']['_id'];

        if ($action == "buyAirTime") {

            $service = $safeHavenController->buyAirtime($transactionId, $getServiceCategoryID, $request->final_amount, $request->phone_number, $company_id);
        }
        if ($action == "buyData") {

            $service = $safeHavenController->buyData($transactionId, $getServiceCategoryID, $getServiceCategory['data']['service'], $request->final_amount, $request->phone_number, $company_id);
        }

        if ($action == "buyElectricity") {

            $service = $safeHavenController->buyElectricity(
                $transactionId,
                $request->electricity_disco,
                $request->meter_type,
                $request->meter_number,
                $request->final_amount,
                $request->phone_number,
                $company_id
            );
            logInfo($service, "VTPass Electricity Response");
        }

        if ($action == "subscribeCableTV") {

            $service = $safeHavenController->subscribeCableTV($transactionId, $getServiceCategoryID, $request->subscription_plan, $request->smart_card_number, $request->final_amount, $request->phone_number, $company_id);
        }

        if ($action == "verifyMeterNumber") {
            $service = $safeHavenController->verifyMeterNumber($request->meter_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "verifyCableTV") {
            $service = $safeHavenController->verifyCableTV($request->smart_card_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "getDataBundles") {

            $service = $safeHavenController->getDataBundles($networkProvider);
        }

        return $service;
    }
}
