<?php

namespace App\Http\Controllers\ThirdParty;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NinePSBSandboxController extends NinePSBController
{
    /**
     * Ensure this controller can only be used against the 9PSB sandbox.
     */
    private function ensureSandbox()
    {
        if (env('APP_MODE') !== 'test') {
            return response()->json([
                'status' => false,
                'message' => '9PSB sandbox endpoints are only available when APP_MODE=test.',
            ], 403);
        }

        return null;
    }

    /**
     * Test 9PSB Virtual Account Creation
     *
     * POST /api/v1/9psb/sandbox/virtual-account
     */
    public function createVirtualAccount(Request $request)
    {
        if ($response = $this->ensureSandbox()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'reference'         => 'required|string|max:100',
            'email'             => 'required|email',
            'phone'             => 'required|string|max:20',
            'first_name'        => 'required|string|max:100',
            'last_name'         => 'required|string|max:100',
            'tracking_reference'=> 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = parent::createAccount(
                $request->reference,
                $request->email,
                $request->phone,
                $request->first_name,
                $request->last_name,
                $request->tracking_reference
            );

            return response()->json($result);
        } catch (Throwable $e) {
            logInfo([
                'message' => $e->getMessage(),
                'endpoint' => '9PSB Sandbox Virtual Account Creation',
            ], '9PSB Sandbox Error');

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while creating the virtual account.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test 9PSB Bank Account Verification
     *
     * POST /api/v1/9psb/sandbox/account/verify
     */
    public function verifyBankAccount(Request $request)
    {
        if ($response = $this->ensureSandbox()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'bank_code'      => 'required|string',
            'account_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = parent::verifyBankAccount(
                $request->bank_code,
                $request->account_number
            );

            return response()->json($result);
        } catch (Throwable $e) {
            logInfo([
                'message' => $e->getMessage(),
                'endpoint' => '9PSB Sandbox Account Verification',
            ], '9PSB Sandbox Error');

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while verifying the bank account.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test 9PSB Bank Transfer
     *
     * POST /api/v1/9psb/sandbox/transfer
     */
    public function bankTransfer(Request $request)
    {
        if ($response = $this->ensureSandbox()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'reference'      => 'required|string|max:100',
            'account_number' => 'required|string',
            'bank_code'      => 'required|string',
            'session_id'     => 'nullable|string',
            'amount'         => 'required|numeric|min:1',
            'account_name'   => 'required|string|max:150',
            'description'    => 'required|string|max:255',
            'type'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = parent::bankTransfer(
                $request->reference,
                $request->account_number,
                $request->bank_code,
                $request->session_id,
                $request->amount,
                $request->account_name,
                $request->description,
                $request->type ?? 'bank_transfer'
            );

            return response()->json($result);
        } catch (Throwable $e) {
            logInfo([
                'message' => $e->getMessage(),
                'endpoint' => '9PSB Sandbox Bank Transfer',
            ], '9PSB Sandbox Error');

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while processing the bank transfer.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Transaction Status Verification
     *
     * POST /api/v1/9psb/sandbox/transaction/verify
     */
    public function verifyTransaction(Request $request)
    {
        if ($response = $this->ensureSandbox()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'required|string|max:100',
            'amount'               => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = parent::verifyTransaction(
                $request->transaction_reference,
                $request->amount
            );

            return response()->json($result);
        } catch (Throwable $e) {
            logInfo([
                'message' => $e->getMessage(),
                'endpoint' => '9PSB Sandbox Transaction Verification',
            ], '9PSB Sandbox Error');

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while verifying the transaction.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Transaction Reversal Verification
     *
     * POST /api/v1/9psb/sandbox/transaction/reversal
     */
    public function verifyTransactionReversal(Request $request)
    {
        if ($response = $this->ensureSandbox()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'required|string|max:100',
            'amount'               => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $result = parent::verifyTransactionReversal(
                $request->transaction_reference,
                $request->amount
            );

            return response()->json($result);
        } catch (Throwable $e) {
            logInfo([
                'message' => $e->getMessage(),
                'endpoint' => '9PSB Sandbox Transaction Reversal Verification',
            ], '9PSB Sandbox Error');

            return response()->json([
                'status'  => false,
                'message' => 'An error occurred while verifying the transaction reversal.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}