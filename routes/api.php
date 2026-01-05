<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PesertaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KunjunganController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded within a group which is assigned the "api" middleware
| group.
|
*/

// Public authentication routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
    Route::delete('/profile', [AuthController::class, 'deleteAccount']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Peserta management
    Route::apiResource('/peserta', PesertaController::class);
    Route::get('/peserta/{peserta}/latest-visit', [PesertaController::class, 'getLatestVisit']);

    // Kunjungan Management
    Route::apiResource('/kunjungan', KunjunganController::class);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
    Route::get('/dashboard/chart', [DashboardController::class, 'getChartData']);
});
