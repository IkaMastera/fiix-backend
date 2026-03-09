<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\Operator\OperatorJobController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\Technician\TechnicianJobController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Public routes (no auth required) ───────────────────────────
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);

    // Service catalog (guests can browse)
    Route::get('/services',      [ServiceCategoryController::class, 'index']);
    Route::get('/services/{id}', [ServiceCategoryController::class, 'show']);

    // ─── Authenticated routes ────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Customer routes
        Route::post('/jobs',             [JobController::class, 'store']);
        Route::get('/jobs/{id}',         [JobController::class, 'show']);
        Route::post('/jobs/{id}/cancel', [JobController::class, 'cancel']);

        // Operator routes
        Route::prefix('operator')->group(function () {
            Route::get('/jobs',                [OperatorJobController::class, 'index']);
            Route::post('/jobs/{id}/triage',   [OperatorJobController::class, 'triage']);
            Route::post('/jobs/{id}/assign',   [OperatorJobController::class, 'assign']);
            Route::post('/jobs/{id}/reassign', [OperatorJobController::class, 'reassign']);
        });

        // Technician routes
        Route::prefix('technician')->group(function () {
            Route::get('/jobs',              [TechnicianJobController::class, 'index']);
            Route::post('/jobs/{id}/accept', [TechnicianJobController::class, 'accept']);
            Route::post('/jobs/{id}/done',   [TechnicianJobController::class, 'done']);
            Route::post('/jobs/{id}/block',  [TechnicianJobController::class, 'block']);
        });
    });
});