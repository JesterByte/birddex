<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json(['message' => 'Please log in.'], 401);
})->name('login');

Route::post('/auth/google', [AuthController::class, 'google'])->middleware('throttle:auth-google');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
