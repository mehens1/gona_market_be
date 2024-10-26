<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{

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
            'expiryYear' => 'required|string|min:2|max:2',
            'cvv' => 'required|string|min:3|max:3',
            'pin' => 'required|string|min:4|max:4'
        ]);

        $cardData = [
            'cardNumber' => $request->cardNumber,
            'expiryMonth' => $request->expiryMonth,
            'expiryYear' => $request->expiryYear,
            'cvv' => $request->cvv,
            'pin' => $request->pin,
        ];

        $data = [
            // 'cardNumber' => $request->cardNumber,
            // 'expiryMonth' => $request->expiryMonth,
            // 'expiryYear' => $request->expiryYear,
            // 'cvv' => $request->cvv,
            'card' => $cardData,
        ];

        $MONNIFY_BASE_URL = env('MONNIFY_BASE_URL');
        $token = env('MONNIFY_API_KEY');
        // $token = env('MONNIFY_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => "bearer {$token}",
            'Content-Type' => 'application/json',
        ])->post("{$MONNIFY_BASE_URL}/v1/merchant/cards/charge", $data);

        if ($response->successful()) {
            return $response->json();
        } else {

            \Log::debug("response: ", [$response]);
            return response()->json(['error' => 'Payment failed.'], $response->status());
        }

    }

    public function payByTransferMonnify(Request $request)
    {
        \Log::debug("request", [$request]);
        return 'payByTransferMonnify';
    }
}
