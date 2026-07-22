<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public Auth Routes ──
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/register', [AuthController::class, 'register']);

    // ── Authenticated Routes ──
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::put('auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('auth/verify-password', [AuthController::class, 'verifyPassword']);

        // Future: add domain route groups here...
    });
});
