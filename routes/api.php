<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\LGAController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PaymentController;

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/states', [StateController::class, 'index']);
Route::get('/lga', [LGAController::class, 'index']);
Route::get('/lga/{state}', [LGAController::class, 'state']);

Route::get('/products', [ProductController::class, 'index']);

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});


Route::middleware('auth:api')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index');
        Route::get('/user/{id}', 'show');
        Route::get('/me', 'me');
    });

    Route::controller(CartController::class)->group(function () {
        Route::get('/cart', 'index');
        Route::post('/cart', 'store');
    });

    Route::controller(PaymentController::class)->prefix('payment')->group(function () {
        Route::post('charge-card-monnify', 'cardChargeMonnify');
        Route::get('pay-by-card-monnify', 'payByCardMonnify');
        Route::post('pay-by-transfer-monnify', 'payByTransferMonnify');
    });
});

