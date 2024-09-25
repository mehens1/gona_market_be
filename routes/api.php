<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\LGAController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

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
});

