<?php

use App\Http\Controllers\JobController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/jobs/{jobId}', [JobController::class, 'show']);
});