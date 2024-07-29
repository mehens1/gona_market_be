<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\LGAController;

Route::get('/countries', [CountryController::class, 'index']);
Route::get('/states', [StateController::class, 'index']);
Route::get('/lga', [LGAController::class, 'index']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
