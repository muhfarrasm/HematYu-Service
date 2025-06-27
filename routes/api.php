<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KategoriPemasukanController;
use App\Http\Controllers\Api\KategoriPengeluaranController;
use App\Http\Controllers\Api\KategoriTargetController;
use App\Http\Controllers\Api\PemasukanController;
use App\Http\Controllers\Api\PengeluaranController;
use App\Http\Controllers\Api\RelasiTargetPemasukanController;
use App\Http\Controllers\Api\TargetController;

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

    // Kategori Pengeluaran routes
    Route::prefix('kategori-pengeluaran')->group(function () {
        Route::get('/', [KategoriPengeluaranController::class, 'index']);
        Route::post('/', [KategoriPengeluaranController::class, 'store']);
        Route::get('/{id}', [KategoriPengeluaranController::class, 'show']);
        Route::put('/{id}', [KategoriPengeluaranController::class, 'update']);
        Route::delete('/{id}', [KategoriPengeluaranController::class, 'destroy']);
        Route::get('/{id}/daily-stats', [KategoriPengeluaranController::class, 'dailyStats']);
        Route::get('/{id}/monthly-stats', [KategoriPengeluaranController::class, 'monthlyStats']);
    });

    // Kategori Target routes
    Route::prefix('kategori-target')->group(function () {
        Route::get('/', [KategoriTargetController::class, 'index']);
        Route::post('/', [KategoriTargetController::class, 'store']);
        Route::get('/{id}', [KategoriTargetController::class, 'show']);
        Route::put('/{id}', [KategoriTargetController::class, 'update']);
        Route::delete('/{id}', [KategoriTargetController::class, 'destroy']);
        Route::get('/{id}/monthly-stats', [KategoriTargetController::class, 'monthlyStats']);
        Route::get('/{id}/status-distribution', [KategoriTargetController::class, 'statusDistribution']);
    });

    // Pemasukan Routes (DIUBAH - dipisah dari kategori-pemasukan)
    Route::prefix('pemasukan')->group(function () {
        // CRUD Operations
        Route::get('/', [PemasukanController::class, 'index']);
        Route::post('/', [PemasukanController::class, 'store']);
        Route::get('/{id}', [PemasukanController::class, 'show']);
        Route::put('/{id}', [PemasukanController::class, 'update']);
        Route::delete('/{id}', [PemasukanController::class, 'destroy']);

        // Monthly Total
        Route::get('/total/monthly', [PemasukanController::class, 'monthlyTotal']);
    });

    // Pengeluaran Routes

    Route::prefix('pengeluaran')->group(function () {
        // CRUD Operations
        Route::get('/', [PengeluaranController::class, 'index']);
        Route::post('/', [PengeluaranController::class, 'store']);
        Route::get('/{id}', [PengeluaranController::class, 'show']);
        Route::put('/{id}', [PengeluaranController::class, 'update']);
        Route::delete('/{id}', [PengeluaranController::class, 'destroy']);

        // Monthly Total
        Route::get('/total/monthly', [PengeluaranController::class, 'monthlyTotal']);
    });

    // Relasi Taget Pemasukan Route
    Route::prefix('relasi-target-pemasukan')->group(function () {
        Route::post('/', [RelasiTargetPemasukanController::class, 'store']);
        Route::get('/{id}', [RelasiTargetPemasukanController::class, 'show']);
        Route::put('/{id}', [RelasiTargetPemasukanController::class, 'update']);
        Route::delete('/{id}', [RelasiTargetPemasukanController::class, 'destroy']);
        Route::get('/by-pemasukan/{pemasukanId}', [RelasiTargetPemasukanController::class, 'byPemasukan']);
        Route::get('/by-target/{targetId}', [RelasiTargetPemasukanController::class, 'byTarget']);
        Route::get('/summary-by-target/{targetId}', [RelasiTargetPemasukanController::class, 'summaryByTarget']);
    });

    // Target Routes
    Route::prefix('target')->group(function () {
        Route::get('summary', [TargetController::class, 'summary']);
        Route::get('/', [TargetController::class, 'index']);
        Route::post('/', [TargetController::class, 'store']);
        Route::get('/{id}', [TargetController::class, 'show']);
        Route::put('/{id}', [TargetController::class, 'update']);
        Route::delete('/{id}', [TargetController::class, 'destroy']);
        Route::get('summary', [TargetController::class, 'summary']);
    });
});

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});