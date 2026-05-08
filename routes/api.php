<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgentTicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/invite/{token}', [AuthController::class, 'registerByInvite']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/faqs/{id}', [FaqController::class, 'show']);

    Route::middleware('is.admin')->group(function () {
        Route::get('/admin/invitations', [InvitationController::class, 'index']);
        Route::post('/admin/invitations', [InvitationController::class, 'store']);
        Route::delete('/admin/invitations/{invitation}', [InvitationController::class, 'destroy']);
        Route::get('/admin/ticket/{ticket}/messages', [MessageController::class, 'index']);
        Route::post('/admin/ticket/{ticket}/messages', [MessageController::class, 'store']);
        Route::apiResource('admin/categories', CategoryController::class);
        Route::post('/admin/faqs', [FaqController::class, 'store']);
        Route::put('/admin/faqs/{faq}', [FaqController::class, 'update']);
        Route::delete('/admin/faqs/{faq}', [FaqController::class, 'destroy']);
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::put('/admin/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
    });

    Route::middleware('is.agent')->group(function () {
        Route::get('/agent/tickets', [AgentTicketController::class, 'index']);
        Route::get('/agent/tickets/available', [AgentTicketController::class, 'available']);
        Route::get('/agent/tickets/{ticket}', [AgentTicketController::class, 'show']);
        Route::post('/agent/tickets/{ticket}/assign', [AgentTicketController::class, 'assign']);
        Route::put('/agent/tickets/{ticket}/close', [AgentTicketController::class, 'close']);
        Route::put('/agent/tickets/{ticket}/updateStatus', [AgentTicketController::class, 'updateStatus']);
        Route::post('/agent/ticket/{ticket}/messages', [MessageController::class, 'store']);
        Route::get('/agent/ticket/{ticket}/messages', [MessageController::class, 'index']);
        Route::put('/agent/tickets/{ticket}/escalate', [AgentTicketController::class, 'escalate']);
    });

    Route::middleware('is.agent.l2')->group(function () {
        // rotte solo agenti L2
    });

    Route::middleware('is.user')->group(function () {
        Route::post('/ticket', [TicketController::class, 'store']);
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/ticket/{id}', [TicketController::class, 'show']);
        Route::post('/ticket/{ticket}/messages', [MessageController::class, 'store']);
        Route::get('/ticket/{ticket}/messages', [MessageController::class, 'index']);
    });

});
