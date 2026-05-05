<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('is.admin')->group(function () {
        // rotte admin
    });

    Route::middleware('is.agent')->group(function () {
        // rotte agenti L1 e L2
    });

    Route::middleware('is.agent.l2')->group(function () {
        // rotte solo agenti L2
    });

    Route::middleware('is.user')->group(function () {
        // rotte utenti finali
    });

});
