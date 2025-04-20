<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Order;
use App\Http\Resources\UserResource;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function cardChargeMonnify(Request $request)
    {

        $request->validate([
            'cardNumber' => 'required|string|min:16',
            'expiryMonth' => 'required|string|min:2|max:2',
            'expiryYear' => 'required|string|min:2|max:4',
            'cvv' => 'required|string',
            'pin' => 'required|numeric|min:1000|max:9999',
            'amount' => 'required|numeric|min:100',
            'paymentDescription' => 'string|nullable',
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0.01',
        ]);

        $generateAccessTokenResponse = $this->generateAccessToken();

        if (isset($generateAccessTokenResponse['accessToken'])) {
            $accessToken = $generateAccessTokenResponse['accessToken'];
        } else {
            return response()->json(['error' => 'Failed to retrieve access token'], 500);
        }

        $user = new UserResource($request->user()->load('userDetail'));
        $user = $user->toArray($request);
        $referenceCode = Str::upper(Str::random(20));
        $MONNIFY_CONTRACT_CODE = env('MONNIFY_CONTRACT_CODE');

        $trans_init_data = [
            "amount" => $request->amount,
            "customerName" => $user['first_name'] . ' ' . $user['last_name'] ,
            "customerEmail" => $user['email'],
            "paymentReference" => $referenceCode,
            "paymentDescription" => "$request->paymentDescription",
            "currencyCode" => "NGN",
            "contractCode" => $MONNIFY_CONTRACT_CODE,
            "redirectUrl" => "https://my-merchants-page.com/transaction/confirm",
            "paymentMethods" => ["CARD","ACCOUNT_TRANSFER"]
        ];

        $initTransResponseBody = $this->initiateTransaction($accessToken, $trans_init_data);

        $cardChargeDdata = [
            'transactionReference' => $initTransResponseBody['transactionReference'],
            "collectionChannel" => "API_NOTIFICATION",
            'card' => [
                'number' => $request->cardNumber,
                'expiryMonth' => $request->expiryMonth,
                'expiryYear' => "20".$request->expiryYear,
                'cvv' => $request->cvv,
                'pin' => $request->pin,
            ],
        ];

        $MONNIFY_BASE_URL = env('MONNIFY_BASE_URL');

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$MONNIFY_BASE_URL}/v1/merchant/cards/charge", $cardChargeDdata);

        if ($response->successful()) {

            $responseBody = $response['responseBody'];

            if ($responseBody['status'] == "FAILED") {
                return $this->errorResponse('Error: ', $responseBody['message'], 500);
            }

            if ($responseBody['status'] === "SUCCESS") {
                return $this->submitItems($user, $request->items, $responseBody);
            }

            if (isset($responseBody) && $responseBody['status'] == "OTP_AUTHORIZATION_REQUIRED") {

                \Log::debug("message 1");
                \Log::debug($responseBody);

                // [2025-03-11 03:04:36] local.DEBUG: array (
                //     'status' => 'OTP_AUTHORIZATION_REQUIRED',
                //     'message' => 'Successful',
                //     'otpData' =>
                //     array (
                //       'message' => 'Successful',
                //       'transactionReference' => '000001043764',
                //       'responseCode' => '00',
                //       'amount' => '150.00',
                //     ),
                //     'transactionReference' => 'MNFY|73|20250311040433|001265',
                //     'paymentReference' => 'OO3KEXTHM3UJ1NOYBOH3',
                //     'authorizedAmount' => 150.0,
                //   )

                // if( !(isset($responseBody['otpData']['id']))    ) {
                //     \Log::debug("message 2");
                //     return $this->errorResponse('Card not validating data, if this problem persist, kindly change card or try another bank!', null, 417);
                // }

                if (!isset($responseBody['otpData']) || !isset($responseBody['otpData']['id'])) {
                    \Log::debug("message 2 - ID not found in otpData");
                    return $this->errorResponse('Card not validating data, if this problem persists, kindly change card or try another bank!', null, 417);
                }



                \Log::debug("message 3");
                \Log::debug($responseBody);

                $otpMessage = $responseBody['message'] ?? 'OTP authorization required.';
                $otpData = $responseBody['otpData'] ?? '';
                $tokenId = $responseBody['otpData']['id'] ?? '';
                $transactionReference = $responseBody['transactionReference'] ?? null;

                \Log::debug("message 4");

                return response()->json([
                    'success' => false,
                    'OTP_AUTHORIZATION_REQUIRED' => true,
                    'message' => $otpMessage,
                    'tokenId' => $tokenId,
                    'transactionReference' => $transactionReference ?? null,
                ]);
            }
        }

        \Log::debug("message 5");
        $responseBody = $response->json();

        $res = $response->json();
        $message = $res['responseBody']['message'] ?? null;

        if (isset($res['responseBody']['message'])) {
            return $this->errorResponse('Error debiting card: '.$message, 500);
        }

        return $this->errorResponse('Error debiting card!', $response->json(), 500);
    }

    public function payByTransferMonnify(Request $request)
    {
        return 'payByTransferMonnify';
    }

    private function generateAccessToken() {

        $MONNIFY_BASE_URL = env('MONNIFY_BASE_URL');
        $apiKey = env('MONNIFY_API_KEY');
        $secretKey = env('MONNIFY_SECRET_KEY');
        $encryptedKeys = base64_encode("$apiKey:$secretKey");

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encryptedKeys,
            'Content-Type' => 'application/json',
        ])->post("{$MONNIFY_BASE_URL}/v1/auth/login");

        if ($response->successful()) {
            return ['accessToken' => $response->json()['responseBody']['accessToken']];
        }

        \Log::error('Monnify Auth Error', [$response->json()]);
        return ['error' => 'Unable to retrieve access token'];

    }

    private function initiateTransaction($token, $trans_init_data) {

        $MONNIFY_BASE_URL = env('MONNIFY_BASE_URL');

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("{$MONNIFY_BASE_URL}/v1/merchant/transactions/init-transaction", $trans_init_data);

        if (!$response->successful()) {
            \Log::error('Error initiating tranaction: ', [$response->json()]);
            return ['error' => 'Error initiating tranaction, this is not your fault, kindly contact support!'];
        }

        return $response->json()['responseBody'];

    }

    public function authorizedOTP(Request $request) {

        $request->validate([
            'transactionReference' => 'required|string',
            'collectionChannel' => 'required|string',
            'tokenId' => 'required|string',
            'token' => 'required|string',
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0.01',
        ]);

        $generateAccessTokenResponse = $this->generateAccessToken();

        $MONNIFY_BASE_URL = env('MONNIFY_BASE_URL');

        $autorizedOTPData = [
            "transactionReference" => $request->transactionReference,
            "collectionChannel" => $request->collectionChannel,
            "tokenId" => $request->tokenId,
            "token" => $request->token,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $generateAccessTokenResponse['accessToken'],
            'Content-Type' => 'application/json',
        ])->post("{$MONNIFY_BASE_URL}/v1/merchant/cards/otp/authorize", $autorizedOTPData);


        $responseData = $response->json();

        if (isset($responseData['responseMessage'])) {
            return $this->errorResponse($responseData['responseMessage'], serialize($responseData), 402);
        }

        if ($response->successful()) {
            return $this->submitItems($request->user(), $request->items, $responseData);
        }

        return $responseData;

    }

    private function submitItems($user, $items, $responseBody) {
        // Extract actual data from nested arrays

        $userArray = (array) $user;
        $itemsArray = (array) $items;
        $responseBodyArray = (array) $responseBody;

        // Validate extracted data
        if (!$userArray || !isset($userArray['id'])) {
            return response()->json(['message' => 'Invalid user data'], 400);
        }

        if (!isset($responseBodyArray['paymentReference'], $responseBodyArray['transactionReference'], $responseBodyArray['authorizedAmount'])) {
            return response()->json(['message' => 'Invalid payment response data'], 400);
        }

        // Extract necessary fields
        $paymentReference = $responseBodyArray['paymentReference'];
        $transactionReference = $responseBodyArray['transactionReference'];
        $authorizedAmount = $responseBodyArray['authorizedAmount'];

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => $userArray['id'],
                'payment_reference' => $paymentReference,
                'transaction_reference' => $transactionReference,
                'amount' => $authorizedAmount,
                'status' => 'ordered',
            ]);

            // Attach order items
            foreach ($itemsArray as $item) {
                if (!isset($item['id'], $item['quantity'], $item['price'])) {
                    throw new \Exception('Invalid item data');
                }

                $order->items()->create([
                    'product_id' => $item['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment successful and order created',
                'order' => $order,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error("Error submitting order: ", ['error' => $th->getMessage(), 'trace' => $th->getTraceAsString()]);

            return response()->json([
                'message' => 'Error submitting order!',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

}





// GT Card Details:
// 5399834485699771
// 07 / 28
// 985

// FCMB Card Details:
// 5061082300033106461
// 05 / 28
// 039



// {
//     "requestSuccessful": true,
//     "responseMessage": "success",
//     "responseCode": "0",
//     "responseBody": {
//         "status": "OTP_AUTHORIZATION_REQUIRED",
//         "message": "Kindly enter the OTP sent to 234810***8956",
//         "otpData": {
//             "id": "1714820887",
//             "message": "Kindly enter the OTP sent to 234810***8956",
//             "responseCode": "T0",
//             "amount": "100.00",
//             "statusCode": 202,
//             "authData": "AaMaAw1ecRX6nOMM3r5/QVvVVDD37RW1hZ+x8dTpRG0HMjCNx4us7li36nS38G2IAMDk/01UMNXtdtlS/8frmMXoO2NVlDeuEhWnYBWhPFGmfVFGl3QTFuWqIqICyDiVrnCUP5mPirL4DNJqQxnXrGrTFaGI2ZyGwv4vugmnpuViYkvgAQ12lByFDsXVHP6DPbpAPMbHUdNVp02qeJ7E5vRZEMY1zFqRvhqQ2ernFSL95kEqaRDBC7MhK6ZTfRf3oH9E2XiO9PCqixWjoY7RaeC9va8D5ZqcKnxTKawiZaJYMNTnROCR1cLVZ1dBcU3d39ygW0vx4jJWiXI9yfw4uw=="
//         },
//         "transactionReference": "MNFY|55|20250116014114|016426",
//         "paymentReference": "STEFUBCUYH6KTRTAESYY",
//         "authorizedAmount": 100
//     }
// }
