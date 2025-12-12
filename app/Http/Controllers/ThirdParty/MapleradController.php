<?php
namespace App\Http\Controllers\ThirdParty;

use App\Http\Controllers\CustomClasses\WebhookController;
use App\Models\Company;
use App\Models\company_wallet;
use App\Models\transactions;
use App\Models\UserCard;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MapleradController extends WebhookController
{

    private $grantType;
    private $clientAssertionType;
    private $clientId;
    private $ibsClientId;
    private $sessionId;
    private $clientAssertion;
    private $baseUrl;
    private $token;
    private $sendingAccount;
    private $debitAccount;
    private $baseSudoUrl;
    private $baseSudoKey;
    private $fundingSourceId;

    public function __construct()
    {
        $this->token               = "";
        $this->ibsClientId         = "";
        $this->sessionId           = "";
        $this->sendingAccount      = "0117187948";
        $this->grantType           = "";
        $this->clientAssertionType = "";
        $this->clientId            = "";
        $this->clientAssertion     = "";
        $this->baseUrl             = env('APP_MODE') == 'test' ? config('maplerad.base_url_test') : config('safehaven.base_url_live');
        $this->baseMapleradKey     = env('APP_MODE') == 'test' ? config('maplerad.base_test_key') : config('maplerad.base_live_key');

    }

    public function mapleradHeader()
    {
        $header = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $this->baseMapleradKey,

        ];

        return $header;
    }
    public function mapleradPostRequest($endpoint, $body)
    {
        logInfo("$endpoint", "Complete Maplerad url");
        logInfo($this->mapleradHeader(), "Maplerad header");
        $response = Http::withHeaders($this->mapleradHeader())->post("$endpoint", $body);
        return $response->json();
    }

    public function mapleradGetRequest($endpoint)
    {
        $response = Http::withHeaders($this->mapleradHeader())->get("$endpoint");
        return $response->json();
    }

    public function mapleradPutRequest($endpoint, array $body = [])
    {
        try {
            if (! empty($body)) {
                // PUT with body
                $response = Http::withHeaders($this->mapleradHeader())
                    ->put($endpoint, $body);
            } else {
                // PUT without body
                $response = Http::withHeaders($this->mapleradHeader())
                    ->put($endpoint);
            }

            return $response->json();

        } catch (\Exception $e) {
            logInfo($e->getMessage(), "Sudo PUT Request Error");
            return [
                'status'  => false,
                'message' => 'PUT Request Failed',
                'error'   => $e->getMessage(),
            ];
        }
    }

    public function createVirtualCard($request, $customer, $customer_account_id)
    {
        /**
         * 1. Resolve or create Sudo Customer
         */
        $customer_account_id = $customer_account_id ?? $this->getCustomerMapleradID($customer);

        if (is_null($customer_account_id)) {
            // No Sudo ID → create new Sudo customer now
            $mapleradCustomer = $this->createMapleradCustomer($request, $customer);

            // Ensure valid structure returned
            if (! isset($mapleradCustomer['data']['reference'])) {
                return ['status' => false, 'message' => "Failed to create Sudo customer"];
            }

            $customer_account_id = $mapleradCustomer['data']['reference'];
        }

        /**
         * 2. Build request body
         */
        $body = [
            "customer_id"  => $customer_account_id,
            "type"         => "VIRTUAL",
            "currency"     => "USD",
            "auto_approve" => true,
            "brand"        => "MASTERCARD",

        ];

        logInfo(json_encode($body), "Maplerad Customer Register Virtual Card body");

        $endpoint = "{$this->baseUrl}/issuing";

        /**
         * 3. Execute request
         */
        try {
            $result = $this->mapleradPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Maplerad Customer Register Virtual Card Result");

            if (isset($result['status'])) {
                return [
                    'status'      => true,
                    'message'     => $result['message'],
                    'result'      => $result,
                    "customer_id" => $mapleradCustomer['data']['reference'],
                ];
            }

            return ['status' => false, 'message' => $result['message'] ?? "Failed to create card"];
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Maplerad Customer Register Virtual Card Error");
            return ['status' => false, 'message' => "Failed to create card... Please contact support"];
        }
    }

    public function createMapleradCustomer($request, $customer)
    {
        $firstName = $request->first_name ?? $customer->first_name;
        $lastName  = $request->last_name ?? $customer->last_name;
        $email     = $request->email ?? $customer->email;
        $phone     = $request->phone ?? $customer->phone;
        $address   = $request->address ?? $customer->address;
        $address40 = str_pad(Str::limit($address, 40, ''), 40, ' ');

        $body = [
            "first_name" => $firstName,
            "last_name"  => $lastName,
            "email"      => $email,
            "country"    => "NG",
            "dob"        => $customer->dob,
            "phone"      => [
                "phone_country_code" => "+234",
                "phone_number"       => $phone,
            ],
            "address"    => [
                "street"      => $address,
                "city"        => $customer->state_of_origin ?? "Ikeja",
                "state"       => $customer->state_of_origin ?? "Lagos",
                "country"     => "NG",
                "postal_code" => "23401",
            ],

        ];

        logInfo($body, "Maplerad Customer Register request body");

        $endpoint = "$this->baseUrl/customers/enroll";

        try {
            $result = $this->mapleradPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Maplerad Customer Register Result");

            if (isset($result['status'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to create customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Maplerad Customer Register Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getMapleradCustomer($id)
    {

        $endpoint = "$this->baseUrl/v1/customers/$id)";

        try {
            $result = $this->mapleradGetRequest($endpoint);

            logInfo(json_encode($result), "Maplerad Customer Get Result");

            if (isset($result['status'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Maplerad Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getCustomerMapleradID($user)
    {
        $existingCard = UserCard::where('user_id', $user->id)->first();
        if ($existingCard != null) {
            return $existingCard->sudo_customer_id;

        } else {
            return null;
        }

    }

    public function fundCard($cardReference, $amount)
    {

        $endpoint = "$this->baseSudoUrl/v1/issuing/$cardReference/fund";

        $body = [
            "amount" => $amount,

        ];

        logInfo($body, "Maplerad Customer fundCard request body");

        try {
            $result = $this->mapleradPostRequest($endpoint, $body);

            logInfo(json_encode($result), "Maplerad Customer fundCard Response");

            if (isset($result['status'])) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function getVirtualCardBalance($cardReference)
    {
        $endpoint = "$this->baseSudoUrl/cards/$cardReference/enroll2fa";

        try {
            $result = $this->mapleradPutRequest($endpoint);

            logInfo(json_encode($result), "Sudo Customer Put Card Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Failed to get customer... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Failed to create ccustomer ... Please contact support"];
        }
    }

    public function changeUserCardPin(Request $request)
    {
        $cardReference = $request->card_reference;
        $endpoint      = "$this->baseSudoUrl/cards/$cardReference/pin";

        $body = [
            "oldPin" => "{$request->old_pin}",
            "newPin" => "{$request->new_pin}",

        ];

        try {
            $result = $this->mapleradPutRequest($endpoint, $body);

            logInfo(json_encode($result), "Sudo Customer Get Card Result");

            if (isset($result['statusCode']) && $result['statusCode'] == 200) {
                return $result;
            } else {
                return ['status' => false, 'message' => "Card pin change failed... Please contact support"];
            }
        } catch (Exception $e) {
            logInfo($e->getMessage(), "Sudo Customer Get Result Error");

            return ['status' => false, 'message' => "Card pin change failed ... Please contact support"];
        }
    }

    public function getMapleradUserCardBalance($user_wallet)
    {

        logInfo($user_wallet->balance, "Sudo Virtual Account Amount");

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            "data"         => [
                "balance" => $user_wallet->balance,
            ],
            'httpCode'     => true,
        ];

    }

    public function getMapleradUserTransaction($request, $user_wallet, $companyId, $branchId, $user, $transactionReference)
    {

        $amount      = $request->input('data.object.amount');
        $description = $request->input('data.object.transactionMetadata.type');
        $oldBalance  = $user_wallet->balance;
        logInfo($oldBalance, "Old Wallet Balance");

        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        $debitCustomer = $this->debitCustomerWithChargeSudo($formatedAmount, 0, $user, $user_wallet);
        logInfo(json_encode($debitCustomer), "debitCustomer Sudo");

        $debitCompany = $this->debitCompanyCardWallet($companyId, $formatedAmount);
        logInfo(json_encode($debitCompany), "debitCompany Card Wallet");

        $customerAccountId = $user->id;

        $this->debitSudoTransaction($debitCustomer, $debitCompany, $formatedAmount, $customerAccountId, $companyId, $description, $branchId, $user, $transactionReference);

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            'httpCode'     => true,
        ];

    }

    public function getMapleradUserAuthorization($request, $user_wallet, $companyId, $branchId, $user)
    {

        $amount      = $request->input('data.object.amount');
        $description = $request->input('data.object.transactionMetadata.type');
        $oldBalance  = $user_wallet->balance;
        logInfo($oldBalance, "Old Wallet Balance");

        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        if ($formatedAmount < $oldBalance) {
            logInfo("amount working", "formated Amount if less than");
            logInfo("ready to send response", "response");

            $checkCompanyBalance = $this->checkCompanyBalance($user_wallet, $amount, $companyId, $user);
            logInfo(json_encode($checkCompanyBalance), "checkCompanyBalance Sudo");

            if ($checkCompanyBalance['status']) {

                return [
                    'statusCode'   => 200,
                    'responseCode' => "00",
                    'httpCode'     => true,
                ];

            } else {
                return [
                    'statusCode'   => 403,
                    'responseCode' => "58",
                    "message"      => $checkCompanyBalance['message'],
                    'httpCode'     => false,
                ];

            }

        } else {
            return [
                'statusCode'   => 403,
                'responseCode' => "51",
                "message"      => "Insufficient fund",
                'httpCode'     => false,
            ];

        }
    }

    public function getMapleradUserRefund($request, $user_wallet, $companyId, $branchId)
    {
        $amount = $request->input('data.object.amount');
        logInfo($amount, "Amount");
        $formatedAmount = abs($amount);

        logInfo($formatedAmount, "formated Amount");

        $oldBalance = $user_wallet->balance;
        logInfo($user_wallet->balance, "Old Wallet Balance");

        $customerAccountId = $user_wallet->wb_account_detail_id;
        $creditCustomer    = $this->creditCustomerSudo($customerAccountId, $formatedAmount, $charge = 0.0, $user_wallet);
        $creditCompany     = $this->creditCompanyCardWallet($companyId, $formatedAmount);

        if ($creditCustomer['status']) {
            $newdescription = "Card Refund";
            $this->refundSudoTransaction($creditCustomer, $creditCompany, $formatedAmount, $companyId, $newdescription, $branchId, $customerAccountId, $user);
            logInfo($creditCompany['balance_before'], "New Wallet Balance");
        }

        return [
            'statusCode'   => 200,
            'responseCode' => "00",
            'httpCode'     => true,
        ];
    }

    public function getUserCard($accountReference)
    {
        return UserCard::where('card_reference', $accountReference)->first()->user_id;

    }

    private function refundMapleradTransaction($creditCustomer, $creditCompany, $amount, $companyId, $newdescription, $branchId, $customerAccountId, $user)
    {
        $transaction = transactions::create([
            "balance_before"         => $creditCustomer['balance_before'],
            "amount"                 => $amount,
            "company_amount"         => $amount,
            "balance_after"          => $creditCustomer['balance_after'],
            'company_balance_before' => $creditCompany['balance_before'],
            'company_balance_after'  => $creditCompany['balance_after'],
            "description"            => $newdescription,
            "company_id"             => $companyId,
            "branch_id"              => $branchId,
            "account_id"             => $customerAccountId,
            "transaction_reference"  => $this->transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 23,
            "credit_debit_type_id"   => 1,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
        ]);
    }

    private function debitMapleradTransaction($debitCustomer, $debitCompany, $amount, $customerAccountId, $companyId, $description, $branchId, $user, $transactionReference)
    {
        $ledger_cr       = null;
        $ledger_dr       = null;
        $ledger_Settings = get_CR_DR_TXN_Type_Ledger(23, $companyId);
        if ($ledger_Settings) {
            $ledger_cr = $ledger_Settings->ledger_cr;
            $ledger_dr = $ledger_Settings->ledger_dr;
        }

        logInfo("transaction created working", "debitSudoTransaction");
        // Save Charges to transaction table

        $transaction = transactions::create([

            "balance_before"         => $debitCustomer['balance_before'],
            "amount"                 => $amount,
            "balance_after"          => $debitCustomer["balance_after"],
            "account_id"             => $customerAccountId,
            "company_id"             => $companyId,
            "branch_id"              => $branchId,
            "company_balance_before" => $debitCompany["balance_before"],
            "company_amount"         => $debitCompany["amount"],
            "company_balance_after"  => $debitCompany["balance_after"],
            "description"            => "Card {$description} {$amount}",
            "transaction_reference"  => $transactionReference,
            "transaction_remarks_id" => 2,
            "transaction_type_id"    => 23,
            "credit_debit_type_id"   => 2,
            "created_date"           => date('Y-m-d'),
            "created_by"             => $user->id,
            "ledger_dr"              => $ledger_dr,
            "ledger_cr"              => $ledger_cr,

        ]);
    }

    private function checkCompanyBalance($user_wallet, $amount, $companyId, $user)
    {

        $response = [];

        $companyWallet = company_wallet::where('company_id', $companyId)->first();

        if ($companyWallet->card_balance < 300001) {
            $response = ["status" => false, 'message' => "Below Threshold of 300000"];
        } else {
            if ($companyWallet->card_balance >= $amount) {

                $response = [
                    "status"          => true,
                    'message'         => "Amount is Valid",
                    'account_id'      => $user->accounts->first()->id,
                    'balance'         => $user_wallet->balance,
                    'company_balance' => $user->company->balance,
                ];
            } else {
                $response = ["status" => false, 'message' => "Switcher Error... Please contact support $companyWallet->balance $amount"];
            }
        }

        return $response;
    }

    private function debitCustomerWithChargeMaplerad($originalAmount, $charge, $user, $userWallet)
    {
        // Validate amount
        if ($originalAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        $initialBalance = $userWallet->balance;
        $debitAmount    = $originalAmount + $charge;

        // Minimum balance allowed to remain in the wallet
        $minBalance = $user->company->setting->min_withdraw_charge;

        // Check sufficient funds
        if (($initialBalance - $minBalance) <= $debitAmount) {
            return [
                "status"  => false,
                "message" => "Not Sufficient funds",
            ];
        }

        // Debit the wallet
        $newBalance          = $initialBalance - $debitAmount;
        $userWallet->balance = $newBalance;
        $userWallet->save();

        return [
            "status"         => true,
            "amount"         => $debitAmount,
            "message"        => "Amount is valid",
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }

    private function debitCompanyCardWallet($companyId, $formattedAmount)
    {
        $response = [];

        // Ensure wallet exists
        $companyWallet = company_wallet::where('company_id', $companyId)->first();
        if (! $companyWallet) {
            return [
                "status"  => false,
                "message" => "Company wallet not found",
            ];
        }

        $initialBalance = $companyWallet->card_balance;

        logInfo("$initialBalance", "Initial Card wallet balance for company {$companyId}");

        // Validate transaction
        if ($formattedAmount <= 0 || $formattedAmount > $initialBalance) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        // Update new balance
        $companyWallet->card_balance = $initialBalance - $formattedAmount;
        $companyWallet->save();

        logInfo("{$companyWallet->card_balance}", "New Card wallet balance for company {$companyId}");

        return [
            "status"         => true,
            "amount"         => $formattedAmount,
            "message"        => "Amount is valid",
            "balance_before" => $initialBalance,
            "balance_after"  => $companyWallet->card_balance,
        ];
    }

    private function creditCustomerMaplerad($customerAccountId, $originalAmount, $charge = 0.0, $userWallet)
    {
        // Validate credit amount
        if ($originalAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid Transaction Amount",
            ];
        }

        // Do not allow negative credit after charge deduction
        if ($originalAmount < $charge) {
            return [
                "status"  => false,
                "message" => "Charge cannot be greater than the amount",
            ];
        }

        $initialBalance = $userWallet->balance;
        $creditAmount   = $originalAmount - $charge;

        // Apply credit
        $newBalance          = $initialBalance + $creditAmount;
        $userWallet->balance = $newBalance;
        $userWallet->save();

        return [
            "status"         => true,
            "message"        => "Amount credited successfully",
            "amount"         => $creditAmount,
            "account_id"     => $customerAccountId,
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }

    private function creditCompanyCardWallet($companyId, $formattedAmount)
    {
        // Check for valid amount
        if ($formattedAmount <= 0) {
            return [
                "status"  => false,
                "message" => "Invalid credit amount",
            ];
        }

        // Fetch company wallet
        $companyWallet = company_wallet::where('company_id', $companyId)->first();

        if (! $companyWallet) {
            return [
                "status"  => false,
                "message" => "Company wallet not found",
            ];
        }

        $initialBalance = $companyWallet->card_balance;

        logInfo($initialBalance, "Initial Card Wallet Balance for {$companyWallet->company_id}");

        // Perform credit
        $newBalance                  = $initialBalance + $formattedAmount;
        $companyWallet->card_balance = $newBalance;
        $companyWallet->save();

        logInfo($newBalance, "New Card Wallet Balance for {$companyWallet->company_id}");

        return [
            "status"         => true,
            "message"        => "Amount credited successfully",
            "amount"         => $formattedAmount,
            "balance_before" => $initialBalance,
            "balance_after"  => $newBalance,
        ];
    }
}
