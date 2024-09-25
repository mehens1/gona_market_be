<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponseTrait;

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

            return $this->successResponse([], 'User registered successfully!', 201);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('User registration failed', ['error' => $e->getMessage()]);

            if ($e->getCode() == 42) {
                return $this->errorResponse('Database table not found. Please check your database configuration.', 'table_not_found', 500);
            }

            return $this->errorResponse('An unexpected error occurred during registration. Please try again later.', 'unexpected_error', 500);
        } catch (\Throwable $th) {
            \Log::error('User registration failed', ['error' => $th->getMessage()]);
            return $this->errorResponse('User registration failed!', $th->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';

        try {
            if (!$token = auth('api')->attempt([$field => $request->login, 'password' => $request->password])) {
                return $this->errorResponse('Invalid credentials', 'invalid_credentials', 401);
            }

            $user = auth('api')->user()->load('userDetail');

            if (!$user->is_active) {
                auth('api')->logout();
                return $this->errorResponse('Your account is not activated. Please contact support.', 'account_inactive', 403);
            }

            return $this->successResponse([
                'access_token' => $token,
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user_data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'user' => array_merge($user->toArray()),
                ]
            ], 'Login successful!', 200);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 42) {
                return $this->errorResponse('User table not found. Please check your database configuration.', 'table_not_found', 500);
            }

            return $this->errorResponse('An unexpected error occurred. Please try again later.', 'unexpected_error', 500);
        }
    }

    public function logout()
    {
        Auth::logout();
        return $this->successResponse([], 'Logged out successfully.', 200);
    }
}
