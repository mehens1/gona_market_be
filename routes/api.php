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
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\GuageController;
use App\Http\Controllers\OrderController;

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/states', [StateController::class, 'index']);
Route::get('/lga', [LGAController::class, 'index']);
Route::get('/lga/{state}', [LGAController::class, 'state']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/guages', [GuageController::class, 'index']);

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

Route::middleware('auth:api')->group(function () {

    Route::controller(ProductController::class)->group(function () {
        Route::post('/product', 'index');
        Route::get('/my-products', 'myProducts');
        Route::post('/upload-product', 'uploadProduct');
    });

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

        Route::post('autorized-otp', 'authorizedOTP');
    });

    Route::controller(OrderController::class)->prefix('orders')->group(function () {
        Route::get('/', 'index');
        Route::get('/my-orders', 'myOrders');
        Route::get('/{id}', 'show');
    });


});

