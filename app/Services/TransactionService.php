<?php
namespace App\Services;

use App\Http\Controllers\ThirdParty\IdentityPassController;
use App\Http\Controllers\ThirdParty\VTPassController;
use App\Mail\TransactionReport;
use App\Models\banks;
use App\Models\Beneficiary;
use App\Models\branch;
use App\Models\BranchWallet;
use App\Models\Company;
use App\Models\company_settings;
use App\Models\company_wallet;
use App\Models\PreferredEbankingPartner;
use App\Models\PreferredEbankingPartnerCompany;
use App\Models\savings_product;
use App\Models\transactions;
use App\Models\User;
use App\Models\user_wallet;
use App\Models\wb_account_details;
use App\Services\AccountCreationService;
use App\Services\ebanking\BankTransferType;
use App\Services\ebanking\EbankingCompanyService;
use App\Services\ebanking\EbankingService;
use App\Services\VirtualAccountType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TransactionService
{
    private const MAX_TRANSFER_AMOUNT               = 3000000;
    private const LOCK_DURATION                     = 20;
    private const BANK_CODE_KUDA                    = '090267';
    private const BANK_CODE_SAFE_HAVEN              = '90286';
    private const STAMP_DUTY_THRESHOLD              = 9999;
    private const TRANSACTION_PENDING               = 1;
    private const TRANSACTION_COMPLETED             = 2;
    private const TRANSACTION_FAILED                = 3;
    private const TRANSACTION_REVERSED              = 5;
    private const TRANSACTION_TYPE_FUND_WALLET      = 3;
    private const TRANSACTION_TYPE_COMPANY_TRANSFER = 5;
    private const TRANSACTION_TYPE_BANK_TRANSFER    = 8;
    private const TRANSACTION_TYPE_MOBILE_MONEY     = 19;
    private const TRANSACTION_TYPE_WALLET_TRANSFER  = 4;

    private const TRANSACTION_REMARKS_PENDING = 6;
    private const CREDIT_TYPE                 = 1;
    private const DEBIT_TYPE                  = 2;

    private const MINIMUM_AMOUNT = 0;

    private const MAX_AIRTIME_AMOUNT = 50000;
    private const TRANSACTION_TYPE_AIRTIME = 11;
    private const TRANSACTION_TYPE_CASHBACK = 66;

     private float $customerCashback = 0.05;
    private float $companyCashback = 0.015;

    private string $baseUrl;

    public function __construct()
    {

        $this->baseUrl = url('/');
    }


    public function initiateTransfer(Request $request, User $user): JsonResponse | array
    {
        // Validate request
        $validationResult = $this->validateRequest($request);
        if (! $validationResult['status']) {
            return response()->json([
                'status'  => false,
                'message' => $validationResult['message'],
            ], 406);
        }

        // Check company transaction status
        $companyStatus = $this->checkCompanyStatus($user->company_id);
        if (! $companyStatus['status']) {
            return response()->json($companyStatus, 400);
        }

        // Route to appropriate handler based on transaction type
        return $this->handleTransactionType($request, $user);
    }

    /**
     * Handle transaction based on type
     */
    private function handleTransactionType(Request $request, User $user): array | JsonResponse
    {
        $transactionType = (int) $request->transaction_type;

        return match ($transactionType) {
            self::TRANSACTION_TYPE_FUND_WALLET      => $this->handleFundWallet($request, $user),
            self::TRANSACTION_TYPE_COMPANY_TRANSFER => $this->handleCompanyTransfer($request, $user),
            self::TRANSACTION_TYPE_BANK_TRANSFER, self::TRANSACTION_TYPE_MOBILE_MONEY =>
            $this->handleBankOrMobileTransfer($request, $user, $transactionType),
            self::TRANSACTION_TYPE_WALLET_TRANSFER  => $this->handleWalletTransfer($request, $user),
            default                                 => [
                'status'  => false,
                'message' => 'Invalid Transaction Type',
            ],
        };
    }

    /**
     * Handle wallet funding
     */
    private function handleFundWallet(Request $request, User $user): JsonResponse
    {
        $transactionId  = generateTransactionReference();
        $ledgerSettings = $this->getLedgerSettings($request->transaction_type, $user->company_id);

        // Create transaction record
        $transaction = $this->createTransaction([
            'balance_before'         => $user->company->wallet->balance,
            'amount'                 => $request->amount,
            'description'            => 'Funding Admin Wallet',
            'transaction_reference'  => $transactionId,
            'branch_id'              => $user->branch_id,
            'transaction_remarks_id' => self::TRANSACTION_REMARKS_PENDING,
            'company_id'             => $user->company_id,
            'transaction_type_id'    => $request->transaction_type,
            'credit_debit_type_id'   => self::CREDIT_TYPE,
            'created_date'           => date('Y-m-d'),
            'created_by'             => $user->id,
            'ledger_cr'              => $ledgerSettings['ledger_cr'] ?? null,
            'ledger_dr'              => $ledgerSettings['ledger_dr'] ?? null,
        ]);

        // Initiate Flutterwave payment
        $paymentLink = $this->initiateFlutterwavePayment($request, $user, $transactionId);

        return response()->json([
            'status'        => true,
            'transactionId' => $transactionId,
            'link'          => $paymentLink,
            'message'       => ' ' . generateTransactionReference(),
        ]);
    }

    /**
     * Handle company wallet transfer
     */
    private function handleCompanyTransfer(Request $request, User $user): array | JsonResponse
    {
        $finalAmount = (float) $request->final_amount;

        if ($finalAmount <= 0) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid Transfer amount',
            ], 400);
        }

        // Check balance
        if ($user->company->wallet->balance < $finalAmount) {
            return response()->json([
                'status'  => false,
                'message' => 'Insufficient Balance',
            ], 400);
        }

        $description    = $request->description ?? 'Company Wallet Transfer';
        $ledgerSettings = $this->getLedgerSettings($request->transaction_type, $user->company_id);

        // Create transaction
        $transaction = $this->createTransaction([
            'balance_before'         => $user->company->wallet->balance,
            'amount'                 => $finalAmount,
            'description'            => $description,
            'transaction_reference'  => generateTransactionReference(),
            'branch_id'              => $user->branch_id,
            'company_id'             => $user->company_id,
            'transaction_remarks_id' => self::TRANSACTION_REMARKS_PENDING,
            'transaction_type_id'    => $request->transaction_type,
            'credit_debit_type_id'   => self::DEBIT_TYPE,
            'created_date'           => date('Y-m-d'),
            'created_by'             => $user->id,
            'ledger_cr'              => $ledgerSettings['ledger_cr'] ?? null,
            'ledger_dr'              => $ledgerSettings['ledger_dr'] ?? null,
        ]);

        return [
            'status'  => true,
            'message' => 'Transaction initialized',
            'data'    => [
                'charge'            => 0.00,
                'transactionId'     => $transaction->id,
                'transactionTypeId' => $request->transaction_type,
                'exchangeAmount'    => $finalAmount,
                'total'             => $finalAmount,
            ],
        ];
    }

    /**
     * Handle bank or mobile money transfer
     */
    private function handleBankOrMobileTransfer(Request $request, User $user, int $transactionType): array | JsonResponse
    {
        $isMobileMoney = $transactionType == self::TRANSACTION_TYPE_MOBILE_MONEY;
        $amount        = (float) $request->final_amount;

        // Calculate charges
        $charge      = $isMobileMoney ? 834 : $this->calculateTransferCharge($amount, $user);
        $totalAmount = $isMobileMoney ? $amount : $amount + $charge;

        // Set description
        $description = $this->buildTransferDescription($request, $user, $transactionType);

        // Check customer balance
        $balanceCheck = $this->checkCustomerAndCompanyBalance($totalAmount, $user);
        if (! $balanceCheck['status']) {
            return response()->json($balanceCheck, 400);
        }

        // Check restrictions
        if (checkcustomerrestriction($user->id, '2')) {
            return response()->json([
                'status'  => false,
                'message' => 'Your account has been restricted. please contact support',
            ], 400);
        }

        // Validate transfer
        $transferValidation = $this->validateTransfer($amount, $user);
        if (! $transferValidation['status']) {
            return response()->json($transferValidation, 400);
        }

        // Get ledger settings
        $ledgerSettings = $this->getLedgerSettings($transactionType, $user->company_id);

        // Create transaction
        $transactionId = generateTransactionReference();
        $transaction   = $this->createTransaction([
            'balance_before'         => $balanceCheck['balance'],
            'amount'                 => $amount,
            'account_id'             => $balanceCheck['account_id'],
            'description'            => $description,
            'branch_id'              => $user->branch_id,
            'company_balance_before' => $balanceCheck['company_balance'],
            'company_amount'         => $amount,
            'system_description'     => $user->company->company_name . ' - ' .
            $user->first_name . ' sent money to ' . ($request->receipient_name ?? ''),
            'company_id'             => $user->company_id,
            'transaction_reference'  => $transactionId,
            'transaction_remarks_id' => self::TRANSACTION_REMARKS_PENDING,
            'transaction_type_id'    => $transactionType,
            'credit_debit_type_id'   => self::DEBIT_TYPE,
            'created_date'           => date('Y-m-d'),
            'created_by'             => $user->id,
            'ledger_cr'              => $ledgerSettings['ledger_cr'] ?? null,
            'ledger_dr'              => $ledgerSettings['ledger_dr'] ?? null,
        ]);

        return [
            'status'  => true,
            'message' => 'Transaction initialized',
            'data'    => [
                'charge'                => $charge ?? 0.00,
                'transaction_reference' => $transactionId,
                'transactionTypeId'     => $transactionType,
                'transfer_amount'       => $amount,
                'total'                 => $totalAmount,
            ],
        ];
    }

    /**
     * Handle wallet to wallet transfer
     */
    private function handleWalletTransfer(Request $request, User $user): array
    {
        $amount      = (float) $request->final_amount;
        $totalAmount = $amount;

        // Check self-transfer
        if ($request->destination_account == $user->virtual_account) {
            return [
                'status'  => false,
                'message' => 'Cannot transfer to self',
            ];
        }

        // Check balance
        $balanceCheck = $this->checkCustomerAndCompanyBalance($totalAmount, $user);
        if (! $balanceCheck['status']) {
            return $balanceCheck;
        }

        // Check restrictions
        if (checkcustomerrestriction($user->id, '2')) {
            return [
                'status'  => false,
                'message' => 'Your account has been restricted. please contact support',
            ];
        }

        // Build description
        $name        = $request->receipient_name ?? 'N/A';
        $description = "You sent payment to $name $request->description / $request->destination_account ";

        // Validate transfer
        $transferValidation = $this->validateTransfer($amount, $user);
        if (! $transferValidation['status']) {
            return $transferValidation;
        }

        // Get ledger settings
        $ledgerSettings = $this->getLedgerSettings($request->transaction_type, $user->company_id);

        // Create transaction
        $transactionId = generateTransactionReference();
        $transaction   = $this->createTransaction([
            'balance_before'         => $balanceCheck['balance'],
            'amount'                 => $totalAmount,
            'account_id'             => $balanceCheck['account_id'],
            'description'            => $description,
            'transaction_reference'  => $transactionId,
            'company_balance_before' => $balanceCheck['company_balance'],
            'company_amount'         => 0,
            'company_id'             => $user->company_id,
            'transaction_remarks_id' => self::TRANSACTION_REMARKS_PENDING,
            'branch_id'              => $user->branch_id,
            'transaction_type_id'    => $request->transaction_type,
            'credit_debit_type_id'   => self::DEBIT_TYPE,
            'created_date'           => date('Y-m-d'),
            'created_by'             => $user->id,
            'ledger_cr'              => $ledgerSettings['ledger_cr'] ?? null,
            'ledger_dr'              => $ledgerSettings['ledger_dr'] ?? null,
        ]);

        return [
            'status'  => true,
            'message' => 'Transaction initialized',
            'data'    => [
                'charge'                => 0.00,
                'transaction_reference' => $transactionId,
                'transactionTypeId'     => $request->transaction_type,
                'transfer_amount'       => $amount,
                'total'                 => $totalAmount,
            ],
        ];
    }

    public function checkCustomerAndCompanyBalance($amount, $user)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();

            \logInfo($user->accounts->first()->id, "wb_account_detail_id Transaction Service");

            \logInfo($userWallet->balance, "userWallet balance from checkCustomerAndCompanyBalance Transaction Service");

            if ($userWallet->balance >= $amount) {

                $companyWallet = company_wallet::where('company_id', $user->company_id)->first();

                if ($companyWallet->balance >= $amount) {

                    $response = [
                        "status"          => true,
                        'message'         => "Amount is Valid",
                        'account_id'      => $user->accounts->first()->id,
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

    public function validateTransfer($amount, $user)
    {
        $companySettings = company_settings::where('company_id', $user->company_id)->first();
        $accountTier     = $user->tier;

        logInfo(json_encode($accountTier), "accountTier Transaction service");
        logInfo(json_encode($user->accounts->first()), "Transaction service");

        $wbaAccount = $user->accounts->first();

        if (! $companySettings) {
            return ["status" => false, 'message' => "Contact support to resolve advanced settings"];
        }

        if ($amount > $accountTier->maximum_transfer) {
            return ["status" => false, 'message' => "Maximum amount per transaction is NGN$accountTier->maximum_transfer. You are transfering $amount, Pls Upgrade your account to transfer higher amounts"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();
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
        $sum      = transactions::where(['transaction_remarks_id' => '2', 'credit_debit_type_id' => 2, 'account_id' => $user->accounts->first()->id])->where('transaction_type_id', '<>', '27')->whereDate('created_at', Carbon::today())->count();
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

        return ["status" => true, 'message' => "Transaction Validated"];
    }

    public function transferToBankAccount(
        string $description,
        float $amount,
        string $transactionReference,
        string $destinationAccount,
        string $bankCode,
        string $transactionPin,
        User $user,
        string $recipientName
    ): JsonResponse {
        $lockKey = "user:last_request:{$user->id}";
        $lock    = Cache::lock($lockKey, self::LOCK_DURATION);

        if (! $lock->get()) {
            return $this->errorResponse('Transaction is in progress. Try again.', 400);
        }

        try {
            return $this->executeTransfer(
                $description,
                $amount,
                $transactionReference,
                $destinationAccount,
                $bankCode,
                $transactionPin,
                $user,
                $recipientName
            );
        } finally {
            $lock->release();
        }
    }

    /**
     * Execute the transfer process
     */
    private function executeTransfer(
        string $description,
        float $amount,
        string $transactionReference,
        string $destinationAccount,
        string $bankCode,
        string $transactionPin,
        User $user,
        string $recipientName
    ): JsonResponse {
        // Early validation
        if ($bankCode === self::BANK_CODE_KUDA) {
            return $this->errorResponse('Service is not available right now', 406);
        }

        if ($amount > self::MAX_TRANSFER_AMOUNT) {
            return $this->errorResponse('Maximum transfer amount is 3,000,000', 400);
        }

        // Check company transaction status
        $companyStatus = $this->checkCompanyStatus($user->company_id);
        if ($companyStatus['status'] != 1) {
            $companyStatus['status'] = $companyStatus['status'] == 1;
            return response()->json($companyStatus);
        }

        // Calculate charges
        $charge      = $this->calculateCharge($amount, $user);
        $totalAmount = $amount + $charge;

        // Begin transaction
        DB::beginTransaction();

        try {
            // Validate PIN
            $pinValidation = $this->validatePin($transactionPin, $totalAmount, $user);
            if (! $pinValidation['status']) {
                DB::rollBack();
                return response()->json($pinValidation, 400);
            }

            // Get and validate transaction
            $transaction = $this->getTransaction($transactionReference, $amount);
            if (! $transaction) {
                DB::rollBack();
                return $this->errorResponse('Invalid Transaction Reference', 400);
            }

            // Check transaction status
            $statusCheck = $this->checkTransactionStatus($transaction);
            if ($statusCheck) {
                DB::rollBack();
                return $statusCheck;
            }

            // Mark as pending
            $transaction->transaction_remarks_id = self::TRANSACTION_PENDING;
            $transaction->save();

            // Check user restrictions
            if (checkcustomerrestriction($user->id, '2')) {
                DB::rollBack();
                return $this->errorResponse('Your account has been restricted. please contact support', 400);
            }

            // Check wallet balance
            $userWallet = $this->getUserWallet($user);
            if ($userWallet->balance < $totalAmount) {
                DB::rollBack();
                return $this->errorResponse('Insufficient Balance', 400);
            }

            // Validate transfer amount
            $transferValidation = $this->validateTransfer($amount, $user);
            if (! $transferValidation['status']) {
                DB::rollBack();
                return response()->json($transferValidation, 400);
            }

            // Calculate WBA charges
            $wbaCharge = $this->calculateWbaCharge($amount);
            $charge    = $this->applyStampDuty($amount, $charge);
            $wbaCharge = $this->applyStampDuty($amount, $wbaCharge);

            // Log charges
            $this->logCharges($wbaCharge, $charge);

            // Check customer and company balance
            $balanceCheck = $this->checkCustomerAndCompanyBalance($amount, $user);
            if (! $balanceCheck['status']) {
                DB::rollBack();
                return response()->json($balanceCheck, 400);
            }

            // Check company balance threshold
            $companyBalanceCheck = $this->checkCompanyBalance($amount, $user);
            if (! $companyBalanceCheck['status']) {
                DB::rollBack();
                return response()->json($companyBalanceCheck, 400);
            }

            // Get e-banking service
            $ebankingService = $this->getEbankingService($user);

            // Verify destination account
            $accountVerification = $this->verifyAccount(
                $ebankingService,
                $bankCode,
                $destinationAccount,
                $user->company->id
            );
            if (! $accountVerification['status']) {
                DB::rollBack();
                return $accountVerification;
            }

            // Get bank details
            $bank = $this->getBankByCode($bankCode);

            // Debit customer (pre-transfer check)
            $companyDebitCode = $this->getCompanyBankCode($user);
            $debitCheck       = $this->checkCustomerDebit(
                $companyDebitCode,
                $pinValidation['account_id'],
                $user->company_id,
                $amount,
                $charge,
                $wbaCharge
            );
            if (! $debitCheck['status']) {
                DB::rollBack();
                return response()->json($debitCheck);
            }

            // Commit transaction before external API call
            DB::commit();

            // Process bank transfer
            $transferResult = $this->processBankTransfer(
                $ebankingService,
                $bank,
                $bankCode,
                $destinationAccount,
                $amount,
                $transaction,
                $user,
                $description,
                $charge,
                $wbaCharge,
                $pinValidation['account_id'],
                $accountVerification,
                $recipientName
            );

            return $transferResult;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processBankTransfer(
        $ebankingService,
        $bank,
        string $bankCode,
        string $destinationAccount,
        float $amount,
        $transaction,
        User $user,
        string $description,
        float $charge,
        float $wbaCharge,
        string $accountId,
        array $accountVerification,
        string $recipientName
    ): JsonResponse {
        // Create transfer request
        $bankTransferType = $this->createBankTransferType(
            $bankCode,
            $bank,
            $destinationAccount,
            $amount,
            $transaction,
            $user
        );

        // Execute transfer
        $bankTransfer = $ebankingService->transferToBankAccount($bankTransferType);

        // Handle transfer failure
        if ($bankTransfer['status'] != '00') {
            $this->markTransactionFailed($transaction);
            return $this->errorResponse($bankTransfer['message'] ?? 'Transfer failed', 400);
        }

        // Complete transfer
        return $this->completeTransfer(
            $transaction,
            $user,
            $bank,
            $destinationAccount,
            $recipientName,
            $description,
            $bankCode,
            $amount,
            $charge,
            $wbaCharge,
            $accountId,
            $accountVerification
        );
    }

    /**
     * Create bank transfer type object
     */
    private function createBankTransferType(
        string $bankCode,
        $bank,
        string $destinationAccount,
        float $amount,
        $transaction,
        User $user
    ): BankTransferType {
        $bankTransferType                       = new BankTransferType();
        $bankTransferType->bankCode             = $bankCode;
        $bankTransferType->shortBankCode        = $bank->short_code;
        $bankTransferType->accountNumber        = $destinationAccount;
        $bankTransferType->description          = $user->first_name . ' ' . $user->last_name . ' / ' . $user->company->company_name;
        $bankTransferType->amount               = $amount;
        $bankTransferType->transactionReference = $transaction->transaction_reference;
        $bankTransferType->sessionId            = $user->virtual_account;
        $bankTransferType->companyID            = $user->company->id ?? '';
        $bankTransferType->senderAccountNumber  = $user->accounts->first()->virtual_accnum ?? '';

        return $bankTransferType;
    }

    /**
     * Complete the transfer process
     */
    private function completeTransfer(
        $transaction,
        User $user,
        $bank,
        string $destinationAccount,
        string $recipientName,
        string $description,
        string $bankCode,
        float $amount,
        float $charge,
        float $wbaCharge,
        string $accountId,
        array $accountVerification
    ): JsonResponse {
        // Debit customer (final)
        $companyDebitCode = $this->getCompanyBankCode($user);
        $debitResult      = $this->debitCustomerFinal(
            $companyDebitCode,
            $accountId,
            $user->company_id,
            $amount,
            $charge,
            $wbaCharge
        );

        // Extract debit results
        $debitData = $this->extractDebitData($debitResult);

        // Build description
        $transaction->description = $this->buildTransactionDescription(
            $user,
            $accountVerification,
            $destinationAccount,
            $bank
        );

        // Update transaction
        $transaction->balance_before         = $debitData['customerBalanceBefore'];
        $transaction->company_balance_before = $debitData['companyBalanceBefore'];
        $transaction->company_amount         = $amount + $wbaCharge;
        $transaction->company_balance_after  = $debitData['companyChargeBalanceAfter'];
        $transaction->transaction_remarks_id = self::TRANSACTION_COMPLETED;
        $transaction->save();

        // Check ledger settings
        $ledgerSettings = get_CR_DR_TXN_Type_Ledger(27, $transaction->company_id);
        if (! $ledgerSettings) {
            return $this->errorResponse(
                'Transaction failed, set Bank Transfer Charge ledger for this transaction type in ledger settings!',
                400
            );
        }

        // Send notifications
        $this->sendNotifications($user, $amount, $debitData, $description, $transaction);

        // Save beneficiary
        $this->saveBeneficiary($user, $recipientName, $bank->id, $destinationAccount);

        // Send email report
        $this->sendEmailReport($user, $amount, $debitData, $description, $transaction, $accountVerification);

        return response()->json([
            'status'  => true,
            'message' => 'Wallet to Bank Transfer Successful',
            'data'    => [
                'balance' => $debitData['customerChargeBalanceAfter'],
            ],
        ]);
    }

    private function debitCustomerFinal(
        int $companyDebitCode,
        string $accountId,
        int $companyId,
        float $amount,
        float $charge,
        float $wbaCharge
    ): array {
        if ($companyDebitCode == self::BANK_CODE_SAFE_HAVEN) {
            return debitCustomerAndCompanyWithChargeServiceWallet(
                $accountId,
                $companyId,
                $amount,
                $charge,
                $wbaCharge
            );
        }

        return debitCustomerAndCompanyWithCharge(
            $accountId,
            $companyId,
            $amount,
            $charge,
            $wbaCharge
        );
    }

    /**
     * Extract debit data from result
     */
    private function extractDebitData(array $debitResult): array
    {
        return [
            'accountId'                   => $debitResult['account_id'],
            'customerBalanceBefore'       => $debitResult['balance_before'],
            'customerBalanceAfter'        => $debitResult['balance_after'],
            'customerChargeBalanceBefore' => $debitResult['charge_balance_before'],
            'customerChargeBalanceAfter'  => $debitResult['charge_balance_after'],
            'companyBalanceBefore'        => $debitResult['company_balance_before'],
            'companyAmount'               => $debitResult['company_amount'],
            'companyBalanceAfter'         => $debitResult['company_balance_after'],
            'companyChargeBalanceBefore'  => $debitResult['company_charge_balance_before'],
            'companyChargeBalanceAfter'   => $debitResult['company_charge_balance_after'],
        ];
    }

    /**
     * Build transaction description
     */
    private function buildTransactionDescription(
        User $user,
        array $accountVerification,
        string $destinationAccount,
        $bank
    ): string {
        return "You transferred funds to " .
        $accountVerification['data']['account_name'] .
        "/" . $destinationAccount .
        " / " . $bank->bank_name .
        " / " . $user->company->company_name;
    }

    /**
     * Calculate base charge
     */
    private function calculateCharge(float $amount, User $user): float
    {
        $validation = $this->accountProductCharges($amount, $user);
        return $validation['data']['charge'] ?? 0;
    }

    /**
     * Calculate WBA charge
     */
    private function calculateWbaCharge(float $amount): float
    {
        $wbaCharge = getWbaTransferCharges(1, $amount);
        return $wbaCharge;
    }

    /**
     * Apply stamp duty for amounts over threshold
     */
    private function applyStampDuty(float $amount, float $charge): float
    {
        return $amount > self::STAMP_DUTY_THRESHOLD ? $charge + 50 : $charge;
    }

    /**
     * Log charges for debugging
     */
    private function logCharges(float $wbaCharge, float $charge): void
    {
        logInfo(json_encode($wbaCharge), "wbaCharge Transaction Service");
        logInfo(json_encode($wbaCharge), "wbaCharge > 1 million");
        logInfo(json_encode($wbaCharge), "wbaCharge stamp duty transaction service");
        logInfo(json_encode($charge), "customer Charge stamp duty transaction service");
    }

    /**
     * Validate PIN
     */
    private function validatePin(string $pin, float $amount, User $user): array
    {
        return $this->validatePinCheckCustomerBalance($pin, $amount, null, $user);
    }

    /**
     * Get transaction with lock
     */
    private function getTransaction(string $reference, float $amount)
    {
        return transactions::lockForUpdate()
            ->where([
                'transaction_reference' => $reference,
                'amount'                => $amount,
            ])
            ->first();
    }

    /**
     * Check transaction status and return error if invalid
     */
    private function checkTransactionStatus($transaction): ?JsonResponse
    {
        $statusMessages = [
            self::TRANSACTION_COMPLETED => 'Transaction has already been completed',
            self::TRANSACTION_FAILED    => 'Transaction has been processed and failed. Kindly try another transaction',
            self::TRANSACTION_REVERSED  => 'Transaction failed and has already been reversed. Kindly try another transaction',
            self::TRANSACTION_PENDING   => 'Duplicate Transaction Reference. Please try again later',
        ];

        $status = $transaction->transaction_remarks_id;
        if (isset($statusMessages[$status])) {
            return $this->errorResponse($statusMessages[$status], 409);
        }

        return null;
    }

    public function accountProductCharges($amount, $user)
    {
        $wbaAccount = $user->accounts->first();

        logInfo($user->id, "wbaAccount account id transaction service");

        logInfo($wbaAccount->account_product_id, "wbaAccount accountProductCharges transaction service");

        $accoountProduct = savings_product::where('id', $wbaAccount->account_product_id)->first();

        logInfo($accoountProduct->id, "wbaAccount accountProduct transaction service");

        if (! $accoountProduct) {
            return ["status" => false, 'message' => "Savings Product does not exist"];
        }

        $userWallet = user_wallet::where('wb_account_detail_id', $wbaAccount->id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($accoountProduct->minimum_balance, "accountProduct minimum_balance transaction service");

        if ($amountLeft < $accoountProduct->minimum_balance) {
            return ["status" => false, 'message' => "Insufficient Customer balance"];
        }

        logInfo($amountLeft, "Transaction Service Amount left after Deduction");
        logInfo($accoountProduct->minimum_balance, "Transaction Service Amount left after Deduction");

        if ($amount < $accoountProduct->minimum_withdraw) {
            return ["status" => false, 'message' => "Transaction Service Transfer Amount cannot be less than $accoountProduct->minimum_withdraw"];
        }

        if ($amount > $accoountProduct->max_withdraw) {
            return ["status" => false, 'message' => "Transaction Service Transfer Amount cannot exceed $accoountProduct->max_withdraw"];
        }

        if ($accoountProduct->bank_transfer_charge_type == 1) {
            $charge = $amount * ($accoountProduct->percentage_transfer_charge / 100);

            $charge = $charge > $accoountProduct->max_bank_transfer_charge ? $accoountProduct->max_bank_transfer_charge : $charge;
        } else {
            $charge = $accoountProduct->max_bank_transfer_charge;
        }

        return ["status" => true, 'message' => "Transaction Service Savings products validated", 'data' => ['charge' => $charge]];
    }

    public function verifyBankAccount($bank_code, $account_number)
    {

        $preferredEbankingPartner = PreferredEbankingPartner::all();

        $ebankingService = new EbankingService($preferredEbankingPartner);

        $bankAccount = $ebankingService->verifyAccountNumberFull($bank_code, $account_number);

        logInfo(json_encode($bankAccount), "Transaction Service bank account");

        if (! $bankAccount['status']) {
            return $bankAccount;
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

        return
            [
            'status'  => true,
            'message' => "Account Verified Successfully",
            'data'    => [
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'bank_name'  => $bank_name,

            ],
        ];
    }

    public function getBankCode($account_name)
    {
        logInfo(json_encode($account_name), "Transaction Service account_name");

        $bank_code = banks::whereRaw('LOWER(bank_name) LIKE ?', ["%" . strtolower($account_name) . "%"])->first()->bank_code;

        return $bank_code;
    }

    public function validatePinCheckCustomerBalance($transactionPin, $amount, $selectedwallet = null, $user)
    {
        $response = [];

        try {

            if ($amount > 0) {
                //    $validatePin = User::where(['id' => $user->id, 'pin' => $transactionPin])->first();

                $validatePin = User::where('id', $user->id)
                    ->where(function ($query) use ($transactionPin) {
                        $query->where('pin', $transactionPin)
                            ->orWhere('transfer_token', $transactionPin)
                            ->orWhere('transfer_schedule_token', $transactionPin);
                    })
                    ->first();

                if ($validatePin) {
                    $sourceWbAccount = ($selectedwallet != null) ? $selectedwallet : $user->accounts->first()->id;
                    $userWallet      = user_wallet::where('wb_account_detail_id', $sourceWbAccount)->lockForUpdate()->first();

                    if ($userWallet->balance >= $amount) {
                        if ($user->company->wallet->balance >= $amount) {
                            $user->balance_fails         = 0;
                            $user->transaction_pin_fails = 0;
                            $user->save();
                            $response = ["status" => true, 'message' => "Amount and pin are Valid", 'account_id' => $user->accounts->first()->id, 'balance' => $userWallet->balance];
                        } else {
                            $response = ["status" => false, 'message' => "Switcher Error... Please contact support for assistance"];
                        }
                    } else {
                        if ($user->balance_fails < 4) {
                            $user->balance_fails += 1;
                            $user->save();
                            $response  = ["status" => false, 'message' => "Insufficient Funds"];
                        } else {
                            // $user->status_id = 5;
                            restrictUserAccount(2, "Multiple Insuficient balance trials");

                            $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                        }
                    }
                } else {
                    if ($user->transaction_pin_fails < 4) {
                        $user->transaction_pin_fails += 1;
                        $user->save();
                        $response  = ["status" => false, 'message' => "Invalid Transaction pin. Your account will be restricted after " . (5 - $user->transaction_pin_fails) . " wrong attempts"];
                    } else {
                        restrictUserAccount(2, "Multiple Invalid transaction pin");
                        $response = ["status" => false, 'message' => "Your account has been restricted. please contact support"];
                    }
                }
            } else {
                $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
            }
        } catch (\Exception $e) {
            logInfo($e->getMessage(), "customer Debit error");
            $response = ["status" => false, 'message' => "Server Error"];
        }

        return $response;
    }

      /**
     * Buy airtime for user
     */
    public function buyAirtime(
        string $transactionPin,
        float $amount,
        User $user,
        string $networkProvider,
        string $phoneNumber
    ): JsonResponse {
        // Generate transaction reference
        $transactionId = $this->vtPassRequestId();
        
        // Validate amount
        if ($amount > self::MAX_AIRTIME_AMOUNT) {
            return $this->errorResponse('Airtime purchase cannot be greater 50,000', 400);
        }
        
        // Check company status
        $companyStatus = $this->checkCompanyStatus($user->company_id);
        if (!$companyStatus['status']) {
            return response()->json($companyStatus, 400);
        }
        
        // Validate PIN
        $pinValidation = $this->validatePin($transactionPin, $amount, $user);
        if (!$pinValidation['status']) {
            return response()->json($pinValidation, 400);
        }
        
        // Check user restrictions
        if (checkcustomerrestriction($user->id, '2')) {
            return $this->errorResponse(
                'Your account has been restricted. please contact support',
                400
            );
        }
        
        // Validate transfer amount
        $transferValidation = $this->validateTransfer($amount, $user);
        if (!$transferValidation['status']) {
            return response()->json($transferValidation, 400);
        }
        
        // Get account details
        $accountId = $user->accounts->first()->id;
        
        // Check customer and company balance
        $balanceCheck = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime(
            $accountId,
            $user->company_id,
            $amount,
            0,
            0.01
        );
        
        if (!$balanceCheck['status']) {
            return $this->errorResponse($balanceCheck['message'], 400);
        }
        
        // Get company bank code
        $companyDebitCode = $this->getCompanyBankCode($user);
        
        // Check VTPASS balance
        $vtpassBalanceCheck = $this->checkVtpassBalance($amount);
        if (!$vtpassBalanceCheck) {
            return $this->errorResponse(
                'Service is not available at the moment. Please try again later',
                400
            );
        }
        
        // Begin database transaction
        DB::beginTransaction();
        
        try {
            // Get customer balance
            $customerBalance = $this->checkCustomerAndCompanyBalance($amount, $user);
            if (!$customerBalance['status']) {
                DB::rollBack();
                return response()->json($customerBalance, 400);
            }
            
            // Create initial transaction
            $this->createInitialTransaction($user, $amount, $transactionId, $customerBalance);
            
            // Debit customer
            $debitResult = $this->debitCustomer($user, $amount, $accountId, $companyDebitCode);
            if (!$debitResult['status']) {
                DB::rollBack();
                return response()->json($debitResult, 400);
            }
            
            // Purchase airtime from provider
            $airtimePurchase = $this->purchaseAirtime(
                $companyDebitCode,
                $user,
                $transactionId,
                $networkProvider,
                $amount,
                $phoneNumber
            );
            
            if (!$airtimePurchase['status']) {
                DB::rollBack();
                return $this->errorResponse(
                    'Failed to purchase airtime. Kindly contact admin for assistance if you have been debited',
                    400,
                    ['transaction_id' => $transactionId]
                );
            }
            
            // Complete transaction
            $result = $this->completeTransaction(
                $transactionId,
                $user,
                $amount,
                $phoneNumber,
                $accountId,
                $debitResult,
                $pinValidation['account_id']
            );
            
            DB::commit();
            return $result;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

     /**
     * Create initial transaction record
     */
    private function createInitialTransaction(
        User $user,
        float $amount,
        string $transactionId,
        array $customerBalance
    ): void {
        $ledgerSettings = $this->getLedgerSettings(
            self::TRANSACTION_TYPE_AIRTIME,
            $user->company_id
        );
        
        transactions::create([
            "balance_before" => $customerBalance['balance'],
            "amount" => $amount,
            "account_id" => $customerBalance['account_id'],
            "description" => "Buy Airtime Initialized",
            "branch_id" => $user->branch_id,
            "company_balance_before" => $customerBalance['company_balance'],
            "company_amount" => $amount,
            "company_id" => $user->company_id,
            "transaction_reference" => $transactionId,
            "transaction_remarks_id" => self::TRANSACTION_REMARKS_PENDING,
            "transaction_type_id" => self::TRANSACTION_TYPE_AIRTIME,
            "credit_debit_type_id" => self::DEBIT_TYPE,
            "created_date" => date('Y-m-d'),
            "created_by" => $user->id,
            "ledger_cr" => $ledgerSettings['ledger_cr'] ?? null,
            "ledger_dr" => $ledgerSettings['ledger_dr'] ?? null,
        ]);
    }

    /**
     * Debit customer based on company bank code
     */
    private function debitCustomer(
        User $user,
        float $amount,
        string $accountId,
        int $companyDebitCode
    ): array {
        logInfo($companyDebitCode, "company_debit_bankcode");
        
        if ($companyDebitCode == self::BANK_CODE_SAFE_HAVEN ) {
            return debitCustomerAndCompanyWithCompanyCashbackServiceWallet(
                $accountId,
                $user->company_id,
                $amount,
                $this->customerCashback,
                $this->companyCashback
            );
        }
        
        return debitCustomerAndCompanyWithCompanyCashback(
            $accountId,
            $user->company_id,
            $amount,
            $this->customerCashback,
            $this->companyCashback
        );
    }
    
    /**
     * Purchase airtime from provider based on company bank code
     */
    private function purchaseAirtime(
        int $companyDebitCode,
        User $user,
        string $transactionId,
        string $networkProvider,
        float $amount,
        string $phoneNumber
    ): array {
        if ($companyDebitCode == self::BANK_CODE_SAFE_HAVEN) {
            return $this->safeHavenControllerService('buyAirTime', [
                'user' => $user,
                'transactionId' => $transactionId,
                'network_provider' => $networkProvider,
                'amount' => $amount,
                'phone_number' => $phoneNumber
            ]);
        }
        
        return $this->vtPassControllerService('buyAirTime', [
            'user' => $user,
            'transactionId' => $transactionId,
            'network_provider' => $networkProvider,
            'amount' => $amount,
            'phone_number' => $phoneNumber
        ]);
    }
    
    /**
     * Complete the transaction
     */
    private function completeTransaction(
        string $transactionId,
        User $user,
        float $amount,
        string $phoneNumber,
        string $accountId,
        array $debitResult,
        string $pinAccountId
    ): JsonResponse {
        // Get transaction with lock
        $transaction = transactions::lockForUpdate()
            ->where([
                'transaction_reference' => $transactionId,
                'amount' => $amount
            ])
            ->first();
        
        if (!$transaction) {
            return $this->errorResponse('Transaction not found', 400);
        }
        
        // Check transaction status
        $statusCheck = $this->checkTransactionStatus($transaction);
        if ($statusCheck) {
            return $statusCheck;
        }
        
        // Update main transaction
        $this->updateMainTransaction($transaction, $user, $amount, $phoneNumber, $accountId, $debitResult, $pinAccountId);
        
        // Create cashback transaction
        $this->createCashbackTransaction($user, $debitResult, $accountId);
        
        // Update transaction as successful
        $this->updateTransactionAsSuccessful($transactionId, $debitResult['balance_before'], false);
        
        
        // Send notifications
        $this->sendNotificationsAirtime($user, $amount, $phoneNumber, $debitResult);
        
        return response()->json([
            'status' => true,
            'message' => 'Airtime Purchase Successful',
            'data' => [
                'balance' => $debitResult['balance_after']
            ]
        ]);
    }

    /**
     * Update main transaction
     */
    private function updateMainTransaction(
        $transaction,
        User $user,
        float $amount,
        string $phoneNumber,
        string $accountId,
        array $debitResult,
        string $pinAccountId
    ): void {
        $transaction->update([
            "balance_before" => $debitResult['balance_before'],
            "amount" => $amount,
            "account_id" => $accountId,
            "balance_after" => $debitResult['balance_after'],
            "company_balance_before" => $debitResult['company_balance_before'],
            "company_balance_after" => $debitResult['company_balance_after'],
            "company_amount" => $debitResult['company_amount'],
            "company_id" => $user->company_id,
            "branch_id" => $user->branch_id,
            "account_id" => $pinAccountId,
            "description" => "Purchase of Airtime worth $amount for $phoneNumber",
            "transaction_reference" => $transaction->transaction_reference,
            "transaction_remarks_id" => self::TRANSACTION_PENDING,
            "transaction_type_id" => self::TRANSACTION_TYPE_AIRTIME,
            "credit_debit_type_id" => self::DEBIT_TYPE,
        ]);
    }
    
    /**
     * Create cashback transaction
     */
    private function createCashbackTransaction(
        User $user,
        array $debitResult,
        string $accountId
    ): void {
        $cashbackTransactionId = generate_txnID();
        $ledgerSettings = $this->getLedgerSettings(
            self::TRANSACTION_TYPE_CASHBACK,
            $user->company_id
        );
        
        transactions::create([
            "balance_before" => $debitResult['cashback_balance_before'],
            "amount" => $debitResult['customer_cashback'],
            "account_id" => $debitResult['customer_cashback'] > 0 ? $accountId : null,
            "balance_after" => $debitResult['cashback_balance_after'],
            "company_balance_before" => $debitResult['company_cashback_balance_before'],
            "company_balance_after" => $debitResult['company_cashback_balance_after'],
            "company_amount" => $debitResult['company_cashback'],
            "description" => "Cashback on Airtime Purchase",
            "branch_id" => $user->branch_id,
            "company_id" => $user->company_id,
            "transaction_reference" => $cashbackTransactionId,
            "transaction_remarks_id" => self::TRANSACTION_COMPLETED,
            "transaction_type_id" => self::TRANSACTION_TYPE_CASHBACK,
            "credit_debit_type_id" => self::CREDIT_TYPE,
            "created_date" => date('Y-m-d'),
            "created_by" => $user->id,
            "ledger_cr" => $ledgerSettings['ledger_cr'] ?? null,
            "ledger_dr" => $ledgerSettings['ledger_dr'] ?? null,
        ]);
    }
    


    public function getDataBundlesList($networkProvider)
    {
        $vtPassController = new VTPassController();
        $dataBundles      = $vtPassController->getDataBundles($networkProvider);

        if ($dataBundles['status']) {
            return ['status' => true, 'message' => 'Data Bundles fetched successfully', 'data' => $dataBundles['data']];
        } else {
            return ['status' => false, 'message' => 'Failed to fetch Data bundles', 'data' => []];
        }
    }

    public function buyDataBundle($transaction_pin, $amount, $user, $network_provider, $data_plan, $phone_number)
    {

        $transactionId = $this->vtPassRequestId();

        $checkAllowedTransaction = $this->checkCompanyStatus($user->company_id);

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] = 1;

        }
        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePin($transaction_pin, $amount, $user)
        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalance = $this->checkCompanyBalance($amount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($wb_account_details->id, $amount);

         $customerBalance = $this->checkCustomerAndCompanyBalance($request->final_amount);

        if (! $customerBalance['status']) {
            return response()->json($customerBalance, 400);
        }

        // $checkVTPASSBalance = $this->checkVTPASSBalance($request->final_amount);

        $company_debit_bankcode = $this->getCompanyBankCode();

        $checkVTPASSBalance = match ($company_debit_bankcode) {

            90286 => true, //7000299948
            default
                  => $this->checkVTPASSBalance($request->final_amount),
        };

        if (! $checkVTPASSBalance) {
            return response()->json("Service is not available at the moment . Please try again later", 400);
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
            if ($value['variation_code'] == $data_plan) {
                $selectedDataBundle = $value;
                $isDataPlanValid    = true;
            }
        }

    
        if (! $isDataPlanValid) {
            return response()->json(["status" => false, 'message' => "Invalid Data bundle"], 400);
        }

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $network_provider,
            "variation_code" => $data_plan['plan']['variation_code'],
            "amount"         => $data_plan['plan']['variation_amount'],
            "phone"          => $phone_number,
        ];

         $checkCustomerAndCompanyBalanceBeforeBuyAirtime = $this->checkCustomerAndCompanyBalanceBeforeBuyAirtime($user->accounts->first()->id, $user->company_id, $selectedDataBundle['variation_amount'], 0, 0.01);

        if (! $checkCustomerAndCompanyBalanceBeforeBuyAirtime['status']) {
            return \response()->json($checkCustomerAndCompanyBalanceBeforeBuyAirtime['message'], 400);
        }

        $endpoint = "pay";

        $variation_amount = $data_plan['plan']['variation_amount'];
        $variation_code   = $data_plan['plan']['variation_code'];

        $vtPassController = new VTPassController();

        $buyAirtime = $vtPassController->buyData($transactionId, $network_provider, $variation_code, $variation_amount, $phone_number);

        if ($buyAirtime["status"]) {

            $debitCustomer = $this->debitCustomerAndCompanyWithCompanyCashback($user->accounts->first()->id, $user->company_id, $variation_amount, 0, 0.01, $user);

            /*   if (! $debitCustomer['status']) {
            return response()->json($debitCustomer, 400);
        }*/

            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(12, $user->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }

            if (! $debitCustomer['status']) {
                response()->json($debitCustomer, 400);
            }

            $transaction = transactions::create([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $variation_amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "account_id"             => $transactionPinValidation['account_id'],
                "description"            => "Purchase of Data worth " . $variation_amount . " for $phone_number",
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 12,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $cashbackTransactionId = \generate_txnID();
            $ledger_cr             = null;
            $ledger_dr             = null;
            $ledger_Settings       = get_CR_DR_TXN_Type_Ledger(66, $user->company_id);
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
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "parent_transaction_id"  => $transaction->id,
                "transaction_reference"  => $cashbackTransactionId,
                "transaction_remarks_id" => 2,
                "transaction_type_id"    => 66,
                "credit_debit_type_id"   => 1,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);
            $updateBalance = true;
            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_after'], $updateBalance, $user);

            return
                [
                "status"  => true,
                'message' => "Data Plan Purchase Successful",
                'data'    => [
                    'balance' => $debitCustomer['balance_after'],
                ],
            ];
        } else {
            return ["status" => false, 'message' => $buyAirtime['message']];
        }
    }

    public function verifyMeterNumber($meter_number, $electricity_disco, $type)
    {

        $vtPassController = new VTPassController();

        $verifyMeterResult = $vtPassController->verifyMeterNumber($meter_number, $electricity_disco, $type);

        if (! $verifyMeterResult['status']) {
            return $verifyMeterResult;
        }
        return $verifyMeterResult;
    }

    public function verifySmartCard(Request $request)
    {

        $vtPassController = new VTPassController();

        $verifyCableTV = $vtPassController->verifyCableTV($smart_card_number, $cable_tv_type);

        if (! $verifyCableTV["status"]) {
            return $verifyCableTV;
        }
        return $verifyCableTV;
    }

    public function buyElectricity($user, $amount, $transaction_pin, $electricity_disco, $meter_type, $meter_number, $phone_number)
    {

        // $transactionId = $this->randomNumber(12);
        $transactionId = $this->vtPassRequestId();

        \logInfo(json_encode($user), "checkAllowedTransaction");

        $checkAllowedTransaction = checkCompanyStatus($user->company_id);

        \logInfo(json_encode($checkAllowedTransaction), "checkAllowedTransaction");

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return $checkAllowedTransaction;

        }

        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePin($transaction_pin, $amount, $user);
        \logInfo(json_encode($transactionPinValidation), "transactionPinValidation");

        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(13, $amount);

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $amount;

        $checkCompanyBalance = $this->checkCompanyBalance($checkCompanyBalanceAmount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $amount);

        if (! $checkCustomerBalance['status']) {
            return $checkCustomerBalancee;
        }

        $body = [
            "request_id"     => $transactionId,
            "serviceID"      => $electricity_disco,
            "variation_code" => $meter_type,
            "billersCode"    => $meter_number,
            "amount"         => $amount,
            "phone"          => $phone_number,
        ];

        $endpoint = "pay";

        $vtPassController = new VTPassController();

        $buyElectricity = $vtPassController->buyElectricity(
            $transactionId,
            $electricity_disco,
            $meter_type,
            $meter_number,
            $amount,
            $phone_number
        );

        logInfo($buyElectricity, "VTPass Electricity Response");

        if (! $buyElectricity['status']) {
            return $buyElectricity;
        } else {
            $debitCustomer = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], $user->company_id, $amount, $companySettings->bills_payment_charge, $wbaCharge);

            /*   if (! $debitCustomer['status']) {
                return \response()->json($debitCustomer, 400);
            }*/

            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(14, $user->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([
                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "account_id"             => $transactionPinValidation['account_id'],
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Purchase of Electricity worth $amount",
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 14,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $transactionChargeId = \generate_txnID();
            $ledger_cr           = null;
            $ledger_dr           = null;
            $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $user->company_id);
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
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
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
                "created_by"             => $user->id,
                "ledger_cr"              => $ledger_cr,
                "ledger_dr"              => $ledger_dr,
            ]);

            $electricityToken = $buyElectricity['data']['token'];

            $transaction->transaction_remarks_id = 2;
            $transaction->description            = "Purchased Electricity Token: $electricityToken Amount: $amount";
            $transaction->save();

            $buyElectricity['data']['balance'] = $debitCustomer["charge_balance_after"];

            sendSMS($user->id, "Purchased Electricity Token $electricityToken, Amount: $amount, Avail Bal: " . $debitCustomer["charge_balance_after"], true, true, 'Electricity Token');

            return $buyElectricity;
        }

    }

    public function subscribeCableTV($user, $amount, $phone_number, $smart_card_number, $subscription_plan, $cable_tv_type, $transaction_pin)
    {

        $transactionId = $this->vtPassRequestId();

        $checkAllowedTransaction = $this->checkCompanyStatus($user->company_id);

        \logInfo(json_encode($checkAllowedTransaction), "checkAllowedTransaction");

        if ($checkAllowedTransaction['status'] == 3 || $checkAllowedTransaction['status'] == 4) {
            $checkAllowedTransaction['status'] = $checkAllowedTransaction['status'] == 1;
            return $checkAllowedTransaction;

        }

        $selectedwallet           = null;
        $transactionPinValidation = $this->validatePin($transaction_pin, $amount, $user)
        \logInfo(json_encode($transactionPinValidation), "transactionPinValidation");

        if (! $transactionPinValidation['status']) {
            return $transactionPinValidation;
        }

        if (\checkcustomerrestriction($user->id, '2')) {
            return ["status" => false, 'message' => "Your account has been restricted. please contact support"];
        }

        //Get WBA Charges
        //Get WBA Charges
        $wbaCharge = getWbaTransferCharges(12, ($amount / 100));

        $companySettings = company_settings::where('company_id', $user->company_id)->first();

        $validateTransferAmount = $this->validateTransfer($amount, $user);

        if (! $validateTransferAmount['status']) {
            return $validateTransferAmount;
        }

        $accountId = $user->accounts->first()->id;

        $wb_account_details = wb_account_details::where('user_id', $user->id)->where('acc_reference', $user->account_reference)->first();

        $checkCompanyBalanceAmount = $companySettings->bills_payment_charge + $amount;

        $checkCompanyBalance = $this->checkCompanyBalance($checkCompanyBalanceAmount, $user);

        if (! $checkCompanyBalance['status']) {
            return $checkCompanyBalance;
        }
        $checkCustomerBalance = checkCustomerBalance($accountId, $amount);

        if (! $checkCustomerBalance['status']) {
            return $checkCustomerBalancee;
        }

        $vtPassController = new VTPassController();

        $subscribeCableTV = $vtPassController->subscribeCableTV($transactionId, $cable_tv_type, $subscription_plan, $smart_card_number, $amount, $phone_number);

        logInfo($subscribeCableTV, "VTPass Subscribe Cable TV Log");

        if ($subscribeCableTV['status']) {

            $debitCustomer   = \debitCustomerAndCompanyWithCharge($transactionPinValidation["account_id"], \auth()->user()->company_id, $request->final_amount, $companySettings->bills_payment_charge, $wbaCharge);
            $ledger_cr       = null;
            $ledger_dr       = null;
            $ledger_Settings = get_CR_DR_TXN_Type_Ledger(13, auth()->user()->company_id);
            if ($ledger_Settings) {
                $ledger_cr = $ledger_Settings->ledger_cr;
                $ledger_dr = $ledger_Settings->ledger_dr;
            }
            $transaction = transactions::create([

                "balance_before"         => $debitCustomer['balance_before'],
                "amount"                 => $amount,
                "balance_after"          => $debitCustomer["balance_after"],
                "account_id"             => $transactionPinValidation['account_id'],
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
                "company_balance_before" => $debitCustomer["company_balance_before"],
                "company_amount"         => $debitCustomer["company_amount"],
                "company_balance_after"  => $debitCustomer["company_balance_after"],
                "description"            => "Cable TV Subscription worth $amount",
                "transaction_reference"  => $transactionId,
                "transaction_remarks_id" => 1,
                "transaction_type_id"    => 13,
                "credit_debit_type_id"   => 2,
                "created_date"           => date('Y-m-d'),
                "created_by"             => $user->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ]);

            $transactionChargeId = \generate_txnID();
            $ledger_cr           = null;
            $ledger_dr           = null;
            $ledger_Settings     = get_CR_DR_TXN_Type_Ledger(51, $user->company_id);
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
                "company_id"             => $user->company_id,
                "branch_id"              => $user->branch_id,
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
                "created_by"             => $user->id,
                "ledger_dr"              => $ledger_dr,
                "ledger_cr"              => $ledger_cr,
            ]);

            $this->updateTransactionAsSuccessful($transactionId, $debitCustomer['balance_before'], false);

            $subscribeCableTV['data']['balance'] = $debitCustomer['charge_balance_after'];

            return $subscribeCableTV;
        } else {
            return $subscribeCableTV;
        }
    }

    public function updateTransactionAsSuccessful($transactionId, $balance = 0, $updateBalance = true, $user)
    {
        $transaction = transactions::where('transaction_reference', $transactionId)->first();

        if ($updateBalance) {
            $transaction->balance_after = $balance;
        }

        $transaction->transaction_remarks_id = 2;
        $transaction->updated_at             = Carbon::now();
        $transaction->modified_by            = $user->id;
        $transaction->save();
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

    public function checkCompanyBalance($amount, $user)
    {

        $response = [];

        if ($amount > 0) {

            $userWallet = company_wallet::where('company_id', $user->company_id)->first();

            if ($userWallet->balance >= $amount) {
                $response = ["status" => true, 'message' => "Amount is Valid", 'account_id' => $user->company_id, 'balance' => $userWallet->balance];
            } else {
                $response = ["status" => false, 'message' => "Insufficient Funds"];
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;

    }
    public function debitCustomerAndCompanyWithCompanyCashback($customerAccountId, $companyId, $originalAmount, $customerCashback = 0, $companyCashback = null, $user = null)
    {

        $response = [];

        $currentUser = $user;

        if ($originalAmount > 0) {

            $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

            if (! $userWallet) {
                $response = ["status" => false, "responseCode" => 0, 'message' => "Invalid customer Provided"];
            }

            $customerInitialBalance = $userWallet->balance;

            $customerCashbackAmount = $customerCashback * $originalAmount;
            $companyCashbackAmount  = $companyCashback * $originalAmount;

            if ($customerInitialBalance > ($originalAmount - $customerCashbackAmount)) {

                $company = Company::find($companyId);
                if ($company->wallet->balance > ($originalAmount - $companyCashbackAmount)) {

                    $customerBalanceAfter = $customerInitialBalance - $originalAmount;

                    $customerFinalBalance  = $userWallet->balance -= ($originalAmount - $customerCashbackAmount);
                    $userWallet->save();
                    if (isset($userWallet->wb_account_details->user->branch_id)) {
                        $BranchWallet           = BranchWallet::where('branch_id', $userWallet->wb_account_details->user->branch_id)->first();
                        $BranchWallet->balance -= $originalAmount;
                        $BranchWallet->save();
                    }
                    $companyInitialBalance = $company->wallet->balance;
                    $companyBalanceAfter   = $companyInitialBalance - $originalAmount;

                    $companyFinalBalance  = $company->wallet->balance -= ($originalAmount - $companyCashbackAmount);
                    $company->wallet->save();

                    $currentUser->balance_fails = 0;
                    $currentUser->save();

                    $response = [
                        "status"                          => true,
                        'message'                         => "Amount is Valid",
                        'account_id'                      => $customerAccountId,
                        'balance_before'                  => $customerInitialBalance,
                        'balance_after'                   => $customerBalanceAfter,
                        'cashback_balance_before'         => $customerBalanceAfter,
                        'cashback_balance_after'          => $customerFinalBalance,
                        'company_balance_before'          => $companyInitialBalance,
                        'company_amount'                  => $originalAmount,
                        'customer_cashback'               => $customerCashbackAmount,
                        'company_cashback'                => $companyCashbackAmount,
                        'company_balance_after'           => $companyBalanceAfter,
                        'company_cashback_balance_before' => $companyBalanceAfter,
                        'company_cashback_balance_after'  => $companyFinalBalance,
                    ];
                } else {
                    $response = ["status" => false, "responseCode" => 0, 'message' => "Switcher related error... Please contact support"];
                }
            } else {
                if ($currentUser->balance_fails < 4) {
                    $currentUser->balance_fails += 1;
                    $currentUser->save();
                    $response  = ["status" => false, "responseCode" => 0, 'message' => "Insufficient Funds"];
                } else {
                    // auth()->user()->status_id = 5;
                    restrictUserAccount(2, "Multiple Insuficient balance trials");

                    $response = ["status" => false, "responseCode" => 0, 'message' => "Your account has been restricted. please contact support"];
                }
            }
        } else {
            $response = ["status" => false, 'message' => "Invalid Transaction Amount"];
        }
        return $response;
    }

    public function register($bvn, $company_id, $email, $phone, $username, $customerInfo, $referral, $password, $pin)
    {
        $rules = [
            'first_name' => 'required|string|max:50|min:2',
            'last_name'  => 'required|string|max:50|min:2',
            'phone'      => 'required|string',
            'email'      => 'required|email|string',
            'dob'        => 'required|date',
            'username'   => 'required|string',
            'company'    => 'required|string',
            'pin'        => 'required|numeric|gt:0',
            'password'   => 'required|string',
        ];

        if ($bvn) {
            $userNIN = User::where('bvn', $bvn)->get();
            if (count($userNIN) > 0) {
                return response()->json(["status" => false, "message" => "BVN already exist!"], 409);
            }
        }

        $companyRecord = Company::where(['company_reference' => $company_id])->orWhere(['id' => $company_id])->first();
        if (! $companyRecord || $companyRecord->id == 27) {
            $ra = ["status" => false, "message" => "Invalid company provided"];
            return $ra;
        }

        \logInfo($companyRecord->id, "Current company ID");

        $savings_product = $companyRecord->savingsProducts[0];
        $selectedProduct = $savings_product;

        if (! ($selectedProduct)) {
            $ra = ["status" => false, "message" => "No Savings product found for this company"];
            return $ra;
        }

        if ($companyRecord->sub->end_date < Date('Y-m-d')) {
            $ra = ["status" => false, "message" => "Please hold on we are carrying out a quick maintenance on our system"];
            // return response()->json($ra, 400);
        }

        if ($companyRecord->status_id != 1) {
            $ra = ["status" => false, "message" => "Encounter error while trying to connect to  institution.. please try again later"];
            return $ra;
        }
        $userPhone = $phone;

        $user = User::where([['email', $email], ['company_id', $companyRecord->id]])->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Email already exists"];
            return $ra;
        }

        $user = User::where([['phone', $userPhone], ['company_id', $companyRecord->id]])->get();
        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Phone number already exists"];
            return $ra;
        }

        $user = User::where('username', $username)->get();

        if (count($user) > 0) {
            $ra = ["status" => false, "message" => "Username already exiss"];
            return $ra;
        }

        \logInfo($user, "Current user");

        $companyIDs = [];

        $companyIDsStatus = false;

        if (in_array($companyRecord->id, $companyIDs)) {
            $companyIDsStatus = true;
        }

        $bank_id      = $companyRecord->setting->preferred_bank_id ?? 126;
        $selectedBank = banks::where('id', $bank_id)->first();
        if (! isset($request->account_type) || $request->account_type != 'regular') {
            $virtualAccount = new VirtualAccountType();

            \logInfo($bank_id, "Register Bank Id ");
            \logInfo($selectedBank->bank_code, "Register Bank code ");

            if ($companyIDsStatus) {
                $customSelectedBank = "090645";
            } else {
                $customSelectedBank = $selectedBank->bank_code;
            }

            $customSelectedBank = $selectedBank->bank_code;

            $virtualAccount->bankCode                     = $customSelectedBank;
            $virtualAccount->currencyCode                 = $companyRecord->currency->code;
            $virtualAccount->firstName                    = $request->first_name;
            $virtualAccount->companyShortName             = $companyRecord->short_name;
            $virtualAccount->lastName                     = $request->last_name;
            $virtualAccount->email                        = $request->email;
            $virtualAccount->accountType                  = 'personal';
            $virtualAccount->phone                        = $request->phone;
            $virtualAccount->dob                          = $request->dob;
            $virtualAccount->gender                       = $request->gender;
            $virtualAccount->bvn                          = $request->bvn;
            $virtualAccount->address                      = $request->address;
            $virtualAccount->companyAccountCreationStatus = $companyRecord->setting->can_create_virtual_account;

            $createAccountService = new AccountCreationService();

            $response = $createAccountService->createVirtualAccount($virtualAccount);

            if (! $response['status']) {
                $response['status'] = 0;
                return $response;
            }
        }
        $branch = $request->branch ?? $companyRecord->branch[0]->id;
        // if($request->branch){
        $branchData = (branch::where('id', $request->branch)->first());
        // }
        // logInfo($response, "Create Account service response");
        $customerReference = $response['data']['account_reference'] ?? rand(1000000000, 9999999999);

        $virtual_acccount_number = (! isset($request->account_type) || $request->account_type != 'regular') ? $response['data']['virtual_account'] : rand(1000000000, 9999999999);
        if ($request->selected_product) {
            $savings_product = savings_product::Where('id', $request->selected_product)->first();
            $selectedProduct = $savings_product;
            if (! $savings_product) {
                $ra = ["status" => false, "message" => "Product doesn't exist!"];
                return response()->json($ra, 400);
            }
            if (isset($request->account_type) && $request->account_type == 'regular') {

                if (isset($savings_product->code)) {
                    if ($savings_product->code != null) {
                        $length                  = strlen($savings_product->code);
                        $virtual_acccount_number = substr_replace($virtual_acccount_number, $savings_product->code, 0, $length);
                    }
                }
            }
        }
        if (isset($request->account_type) && $request->account_type == 'regular') {

            if (isset($branchData->code)) {
                if ($branchData->code != null) {
                    $branchCodeLength         = strlen($branchData->code);
                    $branchCodeLength        -= 1;
                    $virtual_acccount_number  = substr_replace($virtual_acccount_number, $branchData->code, -$branchCodeLength);
                }
            }
        }

        $accountOfficer = User::where('company_id', $companyRecord->id)->first();
        //
        //  GENERATE AND SEND OTP TO USER HERE
        //
        $otp = generateOtp();
        // $otp = '7777';
        DB::beginTransaction();

        try {
            // referral_id
            $dataraw = [
                'first_name'                   => $customerInfo['first_name'],
                'last_name'                    => $customerInfo['last_name'],
                'company_id'                   => $companyRecord->id,
                'email'                        => $email,
                'bvn'                          => $bvn,
                'phone'                        => $userPhone,
                'otp'                          => $otp,
                'nin'                          => $request->nin ?? null,
                'bank_id'                      => $bank_id ?? 126,
                'pin'                          => $pin,
                'address'                      => $customerInfo['address'],
                'dob'                          => $customerInfo['dob'],
                'sms'                          => 1,
                'kyc_level_id'                 => 1,
                'status_id'                    => $companyRecord->setting->account_approval == 0 ? 7 : 1,
                'branch_id'                    => $branch,
                'profile_pic'                  => 'https://safe.wbalite.com/passports/default_image.jpg',
                'user_type_id'                 => 1,
                'phone_verification_status_id' => 1,
                'virtual_account'              => $virtual_acccount_number,
                'otp_expiration_date'          => Carbon::now()->addMinutes(10),
                'account_reference'            => $customerReference,
                'username'                     => $username,
                'account_officer_id'           => $accountOfficer->id,
                'uuid'                         => $customerReference,
                'password'                     => Hash::make($password),
                'phone_verification_status_id' => (isset($branchData->currency_id)) ? (($branchData->currency_id == 1) ? null : 1) : null,
                //
            ];

            if ($referral) {
                $refeUser = User::where(['company_id' => $companyRecord->id, 'username' => $request->referral])->first();
                if (! $refeUser) {
                    return response()->json(['status' => false, 'message' => 'Invalid referral username']);
                }
                $dataraw['referral_id'] = $refeUser->id;
            }

            $user = User::create($dataraw);
            if (! $user) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::09'];
            }

            $wb_account_details = wb_account_details::create([
                'user_id'            => $user->id,
                'account_product_id' => $selectedProduct->id,
                'bankid'             => $bank_id ?? 126,
                'virtual_accnum'     => $virtual_acccount_number,
                'acc_reference'      => $customerReference,
                'has_atm_card'       => 0,
                'currency_id'        => $branchData->currency_id ?? 1,
            ]);
            if (! $wb_account_details) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::10'];
            }

            $user_wallet = user_wallet::create([
                'wb_account_detail_id' => $wb_account_details->id,
                'balance'              => 0,
            ]);
            if (! $user_wallet) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Failed to complete Registration Err::11'];
            }

            // \restrictUserAccountAPI($user->id, 2, "New Account Restriction");

            $bank = banks::where('id', $bank_id)->first();

            if (! $bank) {
                DB::rollBack();
                return ['status' => false, 'message' => 'Invalid bank selected'];
            }

            // if ($user && $user_wallet) {
            $user->assignRole([4]);

            $accountNumber = $virtual_acccount_number;

            $welcomeMessage = "Welcome $request->first_name. Account No: $accountNumber, Bank: $bank->bank_name. Kindly upload your Valid ID & Recent Utility bill. T&Cs apply";

            // $welcomeMessage = "Welcome FirstName! Your account number is 5080135881. Bank Name: Asset Matrix MFB.  upload your Valid ID and link BVN. Have Access via USSD. Simply dial *7006*10#. T&Cs applyEnjoy 24/7.";
            //             sendSMS($userid, $msg, $type = true, $charge_customer = true, $title = 'Create Account', $accno = '', $usesms = 0, $isApi = false, $all_sms_email = 1, $sms_logdata = [])
            $smsstate = sendSMS($user->id, "Welcome to " . $companyRecord->company_name . ". Your OTP is $otp. Alternatively, dial *7006*80*00%23 To verify your phone from the registered phone number", true, false, 'Verify OTP', '');
            $smsstate = sendSMS($user->id, $welcomeMessage, true, false, 'Welcome Message', '', 0, false);
            // $sendSMS = \sendVansoSMS($userPhone, $welcomeMessage);

            \logInfo($smsstate, "SMS response");

            DB::commit();

            $companyIDs = [30];

            $companyIDsStatus = false;
            if (in_array($user->company_id, $companyIDs)) {
                $companyIDsStatus = true;
            }

            return [
                'status'  => true,
                'message' => 'Registration Successful',
                'data'    => [
                    'user_reference' => $customerReference,
                    'account_number' => $accountNumber,
                    'bank'           => $bank->bank_name,
                    'otp_code'       => $user->otp,

                ],
            ];

        } catch (\Exception $e) {

            \logInfo($e->getTraceAsString(), "Registration failure");
            DB::rollback();
            // something went wrong
            return ['status' => false, 'message' => 'Failed to complete Registration', 'data' => $e->getMessage() . ' ' . $e->getLine()];
        }

    }

    public function verifyBVN($bvn, $company_id)
    {

        $charge = 50;

        logInfo($company_id, "Twilio Debit company id");

        $debitCompany = debitCompanyWithCharge($company_id, $charge, 0, );

        if (! $debitCompany['status']) {
            return ['status' => false, 'message' => 'Switcher Error. Kindly contact support'];
        }

        logInfo($debitCompany, "Twilio Debit company log");

        $this->debitBVNFromCompany($debitCompany, $company_id, $charge);

        $identityPassController = new IdentityPassController();

        $bvnResponse = $identityPassController->verifyBVN($bvn);

        if (isset($bvnResponse['data']['verification']['status']) && $bvnResponse['data']['verification']['status'] == "VERIFIED") {

            return $bvnResponse;
        } else {
            return false;
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

    public function verifyOTP($user_reference, $otp_code)
    {
        \logInfo($otp_code, "verifyOTP Twilio request log");

        $userAccunt = User::where(['uuid' => $user_reference, 'otp' => $otp_code])->first();

        if ($userAccunt) {

            $newdate = Carbon::now();

            if (Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date)) > Carbon::now()) {

                $userAccunt->phone_verification_status_id = 2;
                $userAccunt->last_login                   = Carbon::now();
                $userAccunt->save();

                return ['status' => true, 'message' => 'OTP Verification Successful'];
            } else {
                return ['status' => false, 'message' => 'You OTP has Expired', 'data' => "Expiration: " . Carbon::createFromTimestamp(strtotime($userAccunt->otp_expiration_date))->format('d-m-Y / h:i:s A') . " ***** CurrentDate: " . Carbon::now()->format('d-m-Y / h:i:s A')];
            }
        } else {
            return response(['status' => false, 'message' => "Invalid user reference or OTP supplied"]);
        }
    }

    public function getCompanyBankCode($user)
    {
        $company_bank_id         = company_wallet::where('company_id', $user->company_id)->first()->bank_id;
        $bank                    = banks::find($company_bank_id);
        $company_debit_bank_code = (int) $bank->bank_code;
        return $company_debit_bank_code;
    }

    private function checkCompanyBalanceThresholdTransaction($amount, $user)
    {

        $response   = [];
        $userWallet = company_wallet::where('company_id', $user->company_id)->first();
        $amountLeft = $userWallet->balance - $amount;

        logInfo($amountLeft, "amountLeft for company transaction service");

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

    private function errorResponse(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
        ], $code);
    }

    private function getUserWallet(User $user)
    {
        return user_wallet::where('wb_account_detail_id', $user->accounts->first()->id)->first();
    }

    private function getEbankingService(User $user)
    {
        logInfo(json_encode($user->company->id), "PreferredEbankingPartnerCompany");

        $preferredPartner = PreferredEbankingPartnerCompany::where('company_id', $user->company->id)->first();

        if (isset($preferredPartner->company_id)) {
            logInfo(json_encode($preferredPartner), "PreferredEbankingPartnerCompany");
            return new EbankingCompanyService($preferredPartner);
        }

        $preferredPartners = PreferredEbankingPartner::all();
        return new EbankingService($preferredPartners);
    }

    /**
     * Verify account
     */
    private function verifyAccount(
        $ebankingService,
        string $bankCode,
        string $destinationAccount,
        int $companyId
    ): array {
        $result = $ebankingService->verifyAccountNumberFull($bankCode, $destinationAccount, $companyId);

        if (! $result['status']) {
            $result['status'] = false;
        }

        return $result;
    }

    /**
     * Get bank by code
     */
    private function getBankByCode(string $bankCode)
    {
        return banks::where('bank_code', $bankCode)
            ->orWhere('short_code', $bankCode)
            ->first();
    }

    /**
     * Check customer debit
     */
    private function checkCustomerDebit(
        int $companyDebitCode,
        string $accountId,
        int $companyId,
        float $amount,
        float $charge,
        float $wbaCharge
    ): array {
        if ($companyDebitCode == self::BANK_CODE_SAFE_HAVEN) {
            return checkCustomerAndCompanyWithChargeSafeHaven(
                $accountId,
                $companyId,
                $amount,
                $charge,
                $wbaCharge
            );
        }

        return checkCustomerAndCompanyWithCharge(
            $accountId,
            $companyId,
            $amount,
            $charge,
            $wbaCharge
        );
    }
    /**
     * Mark transaction as failed
     */
    private function markTransactionFailed($transaction): void
    {
        $transaction->transaction_remarks_id = self::TRANSACTION_FAILED;
        $transaction->save();
    }

    /**
     * Save beneficiary
     */
    private function saveBeneficiary(User $user, string $name, int $bankId, string $accountNumber): void
    {
        $exists = Beneficiary::where('accountNo', $accountNumber)
            ->whereNull('transaction_id')
            ->exists();

        if (! $exists) {
            $beneficiary            = new Beneficiary();
            $beneficiary->name      = $name;
            $beneficiary->bank_id   = $bankId;
            $beneficiary->accountNo = $accountNumber;
            $beneficiary->user_id   = $user->id;
            $beneficiary->save();
        }
    }

    /**
     * Send notifications
     */
    private function sendNotifications(
        User $user,
        float $amount,
        array $debitData,
        string $description,
        $transaction
    ): void {
        if ($user->company->id == 33) {
            goldencreditSMSPost(
                $user->phone,
                'transaction',
                $amount,
                $debitData['customerChargeBalanceAfter'],
                $user->id
            );
        } else {
            $message = "Debit Amt: | $amount, Desc: App/ {$description} " .
            "Acc: " . ($debitData['accountName'] ?? '') . " " .
            "Bal: N {$debitData['customerChargeBalanceAfter']} " .
            "Date: " . date('Y-m-d') . " Ref: " . $transaction->transaction_reference;

            sendSMS($user->id, $message, true, true, 'Debit Transaction');
        }
    }

    /**
     * Send email report
     */
    private function sendEmailReport(
        User $user,
        float $amount,
        array $debitData,
        string $description,
        $transaction,
        array $accountVerification
    ): void {
        $subtext = "Debit Transaction";
        $reason  = "";
        $msg     = "Debit Amt:$amount,<br><br> Desc: App/" .
        $accountVerification['data']['account_name'] .
        "$description" .
        "<br><br> Avail Bal:" . $debitData['customerChargeBalanceAfter'] .
        "<br><br>Date:" . date('Y-m-d') .
        " <br><br>Ref: " . $transaction->transaction_reference;

        $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
    }

    private function validateRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            "final_amount"     => "required|numeric|gt:0",
            "transaction_type" => "required|numeric|gt:0",
            "description"      => "string|nullable",
        ]);

        if ($validator->fails()) {
            return [
                'status'  => false,
                'message' => $validator->errors()->all()[0],
            ];
        }

        return ['status' => true];
    }

    private function checkCompanyStatus(int $companyId): array
    {
        $status = checkCompanyAllowedTransactionStatus($companyId);

       /* if ($status['status'] != 1) {
            $status['status'] = $status['status'] == 1;
            return $status;
        }*/

        return $status;
    }

    /**
     * Calculate transfer charge
     */
    private function calculateTransferCharge(float $amount, User $user): float
    {
        $charges = $this->accountProductCharges($amount, $user);
        $charge  = $charges['data']['charge'] ?? 0;
        logInfo($charge, "Charge for Transaction type 8");
        return $charge;
    }

    /**
     * Build transfer description based on type
     */
    private function buildTransferDescription(Request $request, User $user, int $transactionType): string
    {
        if ($transactionType == self::TRANSACTION_TYPE_MOBILE_MONEY) {
            return $request->description ?? 'Mobile Money Transfer';
        }

        return $request->description ?? "You sent money to $request->receipient_name via Bank Transfer";
    }

    /**
     * Initiate Flutterwave payment
     */
    private function initiateFlutterwavePayment(Request $request, User $user, string $transactionId): string
    {
        $header = ['Authorization' => 'Bearer ' . $this->flutterwaveSecretKey];
        $body   = [
            "tx_ref"          => $transactionId,
            "amount"          => $request->amount,
            "currency"        => $user->company->currency->code,
            "redirect_url"    => $this->baseUrl . "/company/" . $user->company->short_name . "/transaction/verify-fund-wallet",
            "payment_options" => "card",
            "customer"        => [
                "email"       => $user->email,
                "phonenumber" => $user->phone,
                "name"        => $user->first_name . ' ' . $user->last_name,
            ],
            "customizations"  => [
                "title"       => "Funding WBA Wallet",
                "description" => "Fund Wired Banking Wallet",
                "logo"        => "https://wiredbanking.ng/img/logo/logo-dark.png",
            ],
        ];

        $url      = 'https://api.flutterwave.com/v3/payments';
        $response = postRequest($header, $body, $url);

        return $response['data']['link'] ?? '';
    }

    /**
     * Get ledger settings
     */
    private function getLedgerSettings(int $transactionType, int $companyId): array
    {
        $settings = get_CR_DR_TXN_Type_Ledger($transactionType, $companyId);

        if ($settings) {
            return [
                'ledger_cr' => $settings->ledger_cr,
                'ledger_dr' => $settings->ledger_dr,
            ];
        }

        return ['ledger_cr' => null, 'ledger_dr' => null];
    }

    /**
     * Create transaction record
     */
    private function createTransaction(array $data)
    {
        return transactions::create($data);
    }

    public function checkVtpassBalance($originalAmount)
    {
        $vtPassController = new VTPassController();
        $response         = $vtPassController->checkVTPASSBalance($originalAmount);
        if ($originalAmount > $response['balance']) {
            return false;
        } else {
            return true;
        }
    }

    public function checkCustomerAndCompanyBalanceBeforeBuyAirtime(
        $customerAccountId,
        $companyId,
        $originalAmount,
        $customerCashback = 0,
        $companyCashback = null,
        $user = null
    ) {
        $response = [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "An unexpected error occurred",
        ];

        // Determine user
        $currentUser = Auth::check() ? auth()->user() : $user;

        if ($originalAmount <= 0) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid transaction amount",
            ];
        }

        // Get customer wallet
        $userWallet = user_wallet::where('wb_account_detail_id', $customerAccountId)->first();

        if (! $userWallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid customer provided",
            ];
        }

        $customerInitialBalance = $userWallet->balance;

        // Compute cashback values (safe defaults)
        $customerCashbackAmount = (float) $customerCashback * $originalAmount;
        $companyCashbackAmount  = (float) $companyCashback * $originalAmount;

        // ✅ Ensure customer has enough to pay after cashback
        if ($customerInitialBalance < ($originalAmount - $customerCashbackAmount)) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Insufficient customer balance",
            ];
        }

        // Fetch company and check its wallet
        $company = Company::find($companyId);

        if (! $company || ! $company->wallet) {
            return [
                "status"       => false,
                "responseCode" => 0,
                "message"      => "Invalid company or wallet not found",
            ];
        }

        $companyIDs       = [1, 29, 36, 63, 67, 80, 92, 97];
        $companyIDsStatus = in_array($company->id, $companyIDs);

        $companyAfterBalance = $company->wallet->balance - $originalAmount;

        logInfo(json_encode($companyAfterBalance), "checkCustomerAndCompanyBalanceBeforeBuyAirtime Company after balance for cash back");

        // ✅ Logic for switcher company wallet balance
        if ($company->wallet->balance > 100000 && $companyAfterBalance > 100000) {
            $companyIDsStatus = true;
        }

        if ($company->wallet->balance > 100100 || $companyIDsStatus) {
            return [
                "status"       => true,
                "responseCode" => 1,
                "message"      => "Amount is valid",
            ];
        }

        return [
            "status"       => false,
            "responseCode" => 0,
            "message"      => "Switcher-related error... Please contact support",
        ];
    }

     private function createTransanctionforUtilityPayment($user, $amount, $transactionId, $customerBalance, $transaction_type, $description)
    {

        $amount      = 0;
        $totalAmount = 0;

        $amount      = $amount;
        $totalAmount = $amount;

        $ledger_cr = null;
        $ledger_dr = null;

        $ledger_Settings = get_CR_DR_TXN_Type_Ledger($transaction_type, $user->company_id);
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
            "company_id"             => $user->company_id,
            "transaction_remarks_id" => 6,
            "branch_id"              => $user->branch_id,
            "transaction_type_id"    => $transaction_type,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_cr"              => $ledger_cr,
            "ledger_dr"              => $ledger_dr,
        ]);
    }

     private function vtPassControllerSercice($action, array $data = [])
    {

        $transactionId      = $data['transactionId'] ?? null;
        $network_provider    = $data['network_provider'] ?? null;
        $networkProvider = $data['network_provider'] ?? null; 
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;
        $user = $data['user'];
        $phone_number = $data['phone_number'];
        $amount =  $data['amount'];

        $vtPassController = new VTPassController();

        if ($action == "buyAirTime") {
            $networkProvider = $network_provider == '9mobile' ? 'etisalat' : $network_provider;

            $service = $vtPassController->buyAirtime($transactionId, $networkProvider, $request->final_amount, $request->phone_number);
        }
        if ($action == "buyData") {
            $service = $vtPassController->buyData($transactionId, $request->network_provider, $request->data_plan, $selectedDataBundle['variation_amount'], $request->phone_number);
        }
        if ($action == "buyElectricity") {
            $service = $vtPassController->buyElectricity(
                $transactionId,
                $electricity_disco,
                $meter_type,
                $meter_number,
                $amount,
                $phone_number
            );
            logInfo($service, "VTPass Electricity Response");
        }
        if ($action == "subscribeCableTV") {
            $subscribeCableTV = $vtPassController->subscribeCableTV($transactionId, $cable_tv_type, $subscription_plan, $smart_card_number, $amount, $phone_number);
        }
        if ($action == "verifyMeterNumber") {
            $service = $vtPassController->verifyMeterNumber($meter_number, $electricity_disco, $type);
        }
        if ($action == "verifyCableTV") {
            $service = $vtPassController->verifyCableTV($smart_card_number, $cable_tv_type);
        }
        if ($action == "getDataBundles") {

            $service = $vtPassController->getDataBundles($networkProvider);
        }

        if ($action == "getDataBundlesList") {
            $networkProvider = $network_provider == '9mobile' ? 'etisalat' : $request->network_provider;
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
        $network_provider    = $data['network_provider'] ?? null;
        $networkProvider = $data['network_provider'] ?? null;
        $request            = $data['request'] ?? null;
        $selectedDataBundle = $data['selectedDataBundle'] ?? null;
        $cableTV            = $data['cableTV'] ?? null;
        $user = $data['user'];
        $phone_number = $data['phone_number'];
        $amount =  $data['amount'];

         

        $safeHavenController = new SafehavenController();
        $company_id          = company_wallet::where('company_id',$user->company_id)->first()->company_id;
        $company_id          = (int) $company_id;

        $getService = $safeHavenController->getService($action, $company_id);

        if ($network_provider != null) {
            $networkProvider = strtolower($network_provider);

            $networkProvider = preg_replace('/-data$/', '', $networkProvider);

            $networkProvider    = $networkProvider === '9mobile' ? 'etisalat' : $networkProvider;
            $getServiceCategory = $safeHavenController->getServiceCategory($getService, $company_id, strtoupper($networkProvider));
        } else {
            if ($electricity_disco != null) {
                $disco = $electricity_disco;
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

            $service = $safeHavenController->buyAirtime($transactionId, $getServiceCategoryID, $amount, $phone_number, $company_id);
        }
        if ($action == "buyData") {

            $service = $safeHavenController->buyData($transactionId, $getServiceCategoryID, $getServiceCategory['data']['service'], $amount, $phone_number, $company_id);
        }

        if ($action == "buyElectricity") {

            $service = $safeHavenController->buyElectricity(
                $transactionId,
                $electricity_disco,
                $meter_type,
                $meter_number,
                $amount,
                $phone_number,
                $company_id
            );
            logInfo($service, "safeHaven Electricity Response");
        }

        if ($action == "subscribeCableTV") {

            $service = $safeHavenController->subscribeCableTV($transactionId, $getServiceCategoryID, $subscription_plan, $smart_card_number, $amount, $phone_number, $company_id);
        }

        if ($action == "verifyMeterNumber") {
            $service = $safeHavenController->verifyMeterNumber($meter_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "verifyCableTV") {
            $service = $safeHavenController->verifyCableTV($smart_card_number, $getServiceCategoryID, $company_id);
        }

        if ($action == "getDataBundles") {

            $service = $safeHavenController->getDataBundles($networkProvider);
        }

        return $service;
    }

    /**
     * Send notifications
     */
    private function sendNotificationsAirtime(User $user, float $amount, string $phoneNumber, array $debitResult): void
    {
        $subtext = "Airtime Purchase Successful";
        $reason = "";
        $msg = "Purchase of Airtime worth $amount for $phoneNumber";
        
        $this->sendTransactionReportEmail($subtext, $msg, $reason, $user);
        
        // Send SMS notification
        sendSMS(
            $user->id,
            "Airtime Purchase: N$amount for $phoneNumber. Bal: N{$debitResult['balance_after']}",
            true,
            true,
            'Airtime Purchase'
        );
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

}
