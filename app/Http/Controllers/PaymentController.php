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

    private $collectionChannel = 'API_NOTIFICATION';
    private $monnifyBaseUrl;

    public function __construct()
    {
        $this->monnifyBaseUrl = env('MONNIFY_BASE_URL');
    }

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
            "collectionChannel" => $this->collectionChannel,
            'card' => [
                'number' => $request->cardNumber,
                'expiryMonth' => $request->expiryMonth,
                'expiryYear' => "20".$request->expiryYear,
                'cvv' => $request->cvv,
                'pin' => $request->pin,
            ],
        ];

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->monnifyBaseUrl}/v1/merchant/cards/charge", $cardChargeDdata);

        if ($response->successful()) {

            $responseBody = $response['responseBody'];

            if ($responseBody['status'] == "FAILED") {
                return $this->errorResponse('Error: ', $responseBody['message'], 500);
            }

            if ($responseBody['status'] === "SUCCESS") {
                return $this->submitItems($user, $request->items, $responseBody);
            }

            if (isset($responseBody) && $responseBody['status'] == "OTP_AUTHORIZATION_REQUIRED") {

                if (!isset($responseBody['otpData']) || !isset($responseBody['otpData']['id'])) {
                    return $this->errorResponse('Card not validating data, if this problem persists, kindly change card or try another bank!', null, 417);
                }

                $otpMessage = $responseBody['message'] ?? 'OTP authorization required.';
                $otpData = $responseBody['otpData'] ?? '';
                $tokenId = $responseBody['otpData']['id'] ?? '';
                $transactionReference = $responseBody['transactionReference'] ?? null;

                return response()->json([
                    'success' => false,
                    'OTP_AUTHORIZATION_REQUIRED' => true,
                    'message' => $otpMessage,
                    'tokenId' => $tokenId,
                    'transactionReference' => $transactionReference ?? null,
                    'collectionChannel' => $this->collectionChannel,
                ]);
            }
        }

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

    private function generateAccessToken()
    {
        $apiKey = env('MONNIFY_API_KEY');
        $secretKey = env('MONNIFY_SECRET_KEY');
        $encryptedKeys = base64_encode("$apiKey:$secretKey");

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encryptedKeys,
            'Content-Type' => 'application/json',
        ])->post("{$this->monnifyBaseUrl}/v1/auth/login");

        if ($response->successful()) {
            return ['accessToken' => $response->json()['responseBody']['accessToken']];
        }

        \Log::error('Monnify Auth Error', [$response->json()]);
        return ['error' => 'Unable to retrieve access token'];

    }

    private function initiateTransaction($token, $trans_init_data) {

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $token,
            'Content-Type' => 'application/json',
        ])->post("{$this->monnifyBaseUrl}/v1/merchant/transactions/init-transaction", $trans_init_data);

        if (!$response->successful()) {
            \Log::error('Error initiating tranaction: ', [$response->json()]);
            return ['error' => 'Error initiating tranaction, this is not your fault, kindly contact support!'];
        }

        return $response->json()['responseBody'];

    }

    public function authorizedOTP(Request $request)
    {
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

        if (isset($generateAccessTokenResponse['accessToken'])) {
            $accessToken = $generateAccessTokenResponse['accessToken'];
        } else {
            return $this->errorResponse('Failed to retrieve access token', null, 500);
        }

        $autorizedOTPData = [
            "transactionReference" => $request->transactionReference,
            "collectionChannel" => $request->collectionChannel,
            "tokenId" => $request->tokenId,
            "token" => $request->token,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post("{$this->monnifyBaseUrl}/v1/merchant/cards/otp/authorize", $autorizedOTPData);

        $responseData = $response->json();

        if ($response->successful()) {
            return $this->submitItems(auth()->user(), $request->items, $responseData['responseBody']);
        }

        return $this->errorResponse(
            $responseData['responseMessage'] ?? 'OTP Authorization failed',
            $responseData,
            402
        );
    }


    private function submitItems($user, array $items, $responseBody)
    {
        $responseBodyArray = (array) $responseBody;
        $itemsArray = (array) $items;

        if (
            !isset($responseBodyArray['paymentReference'],
                $responseBodyArray['transactionReference'],
                $responseBodyArray['authorizedAmount'])
        ) {
            return response()->json(['message' => 'Invalid payment response data'], 400);
        }

        $paymentReference = $responseBodyArray['paymentReference'];
        $transactionReference = $responseBodyArray['transactionReference'];
        $authorizedAmount = $responseBodyArray['authorizedAmount'];

        DB::beginTransaction();

        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
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
                'success' => true,
                'order' => $order,
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error("Error submitting order: ", [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error submitting order!',
                'error' => $th->getMessage(),
            ], 500);
        }
    }
}
