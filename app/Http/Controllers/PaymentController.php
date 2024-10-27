<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function payByCardMonnify(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // \Log::debug("request", [$request]);
        return 'payByCardMonnify';
        // $perPage = $request->input('per_page', 10);
        // return UserResource::collection(User::paginate($perPage));

    }

    public function cardChargeMonnify(Request $request)
    {

        $request->validate([
            'cardNumber' => 'required|string|min:16|max:16',
            'expiryMonth' => 'required|string|min:2|max:2',
            'expiryYear' => 'required|string|min:2|max:4',
            'cvv' => 'required|numeric|min:100|max:999',
            'pin' => 'required|numeric|min:1000|max:9999',
            'amount' => 'required|numeric|min:100',
            'paymentDescription' => 'string|nullable'
        ]);

        $generateAccessTokenResponse = $this->generateAccessToken();

        if (isset($generateAccessTokenResponse['accessToken'])) {
            $accessToken = $generateAccessTokenResponse['accessToken'];
        } else {
            \Log::error("Failed to retrieve access token", [$generateAccessTokenResponse]);
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
            if($response['responseBody']['status'] == "FAILED"){
                return $this->errorResponse('Error: ', $response['responseBody']['message'], 500);
            }
            return $response->json();
        }

        \Log::debug("response: ", [$response]);
            return $this->errorResponse('Error debitting card!. ', $response, 500);

    }

    public function payByTransferMonnify(Request $request)
    {
        \Log::debug("request", [$request]);
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
}
