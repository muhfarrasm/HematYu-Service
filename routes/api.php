<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KategoriPemasukanController;

Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Dashboard routes
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        // Bisa ditambahkan route dashboard lainnya nanti
    });

    // Kategori Pemasukan routes
    Route::prefix('kategori-pemasukan')->group(function () {
        Route::get('/', [KategoriPemasukanController::class, 'index']);
        Route::post('/', [KategoriPemasukanController::class, 'store']);
        Route::get('/{id}', [KategoriPemasukanController::class, 'show']);
        Route::put('/{id}', [KategoriPemasukanController::class, 'update']);
        Route::delete('/{id}', [KategoriPemasukanController::class, 'destroy']);
        Route::get('/{id}/stats', [KategoriPemasukanController::class, 'stats']);
    });

});

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});