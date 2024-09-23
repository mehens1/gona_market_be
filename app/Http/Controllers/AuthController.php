<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'registration_platform' => 'required|string|in:mobile_app,admin_dashboard',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:local_government_areas,id',
            'address' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $user = User::create([
                    'email' => $request->email,
                    'phone_number' => $request->phone_number,
                    'password' => Hash::make($request->password),
                    'is_active' => false,
                ]);

                UserDetail::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'registration_platform' => $request->registration_platform,
                    'state_id' => $request->state_id,
                    'lga_id' => $request->lga_id,
                    'address' => $request->address,
                ]);
            });
        } catch (\Throwable $th) {
            \Log::error('User registration failed', ['error' => $th->getMessage()]);
            return response()->json(['message' => 'User registration failed!', 'error' => $th->getMessage()], 500);
        }

        return response()->json(['message' => 'User registered successfully!'], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        if (Auth::attempt([$field => $request->login, 'password' => $request->password])) {
            $user = Auth::user();
            $token = $user->createToken('gonaMarket')->plainTextToken;
            return response()->json(['token' => $token, 'user' => $user]);
        }
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
}
