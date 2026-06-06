<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AgentDashboardController;
use App\Http\Controllers\AgentTicketController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/invite/{token}', [AuthController::class, 'registerByInvite']);
Route::get('/auth/invite/{token}', [AuthController::class, 'showInvite']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return User::with('company')->find(Auth::id());
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/faqs/{id}', [FaqController::class, 'show']);
    Route::put('/user/theme', [ProfileController::class, 'updateTheme']);
    Route::put('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    

    Route::middleware('is.admin')->group(function () {
        Route::get('/admin/invitations', [InvitationController::class, 'index']);
        Route::post('/admin/invitations', [InvitationController::class, 'store']);
        Route::delete('/admin/invitations/{id}', [InvitationController::class, 'destroy']);
        Route::get('/admin/tickets', [AdminTicketController::class, 'index']);
        Route::get('/admin/tickets/{id}', [AdminTicketController::class, 'show']);
        Route::get('/admin/tickets/{id}/messages', [MessageController::class, 'index']);
        Route::post('/admin/tickets/{id}/messages', [MessageController::class, 'store']);
        Route::put('/admin/tickets/{id}/escalate', [AdminTicketController::class, 'escalate']);
        Route::put('/admin/tickets/{id}/updateStatus', [AdminTicketController::class, 'updateStatus']);
        Route::apiResource('admin/categories', CategoryController::class);
        Route::post('/admin/faqs', [FaqController::class, 'store']);
        Route::put('/admin/faqs/{faq}', [FaqController::class, 'update']);
        Route::delete('/admin/faqs/{faq}', [FaqController::class, 'destroy']);
        Route::get('/admin/users', [AdminUserController::class, 'index']);
        Route::put('/admin/users/{user}', [AdminUserController::class, 'update']);
        Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);
        Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
        Route::get('/admin/dashboard/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/admin/dashboard/details', [AdminDashboardController::class, 'details']);
        Route::put('/admin/tickets/{id}/updatePriority', [AdminTicketController::class, 'updatePriority']);
        Route::get('/admin/tickets/escalated/available', [AdminTicketController::class, 'escalatedAvailable']);
        Route::post('/admin/users/{user}/reset-password', [AdminUserController::class, 'resetPassword']);
    });

    Route::middleware('is.agent')->group(function () {
        Route::get('/agent/tickets', [AgentTicketController::class, 'index']);
        Route::get('/agent/tickets/available', [AgentTicketController::class, 'available']);
        Route::get('/agent/tickets/{id}', [AgentTicketController::class, 'show']);
        Route::post('/agent/tickets/{id}/assign', [AgentTicketController::class, 'assign']);
        Route::put('/agent/tickets/{id}/close', [AgentTicketController::class, 'close']);
        Route::put('/agent/tickets/{id}/updateStatus', [AgentTicketController::class, 'updateStatus']);
        Route::post('/agent/tickets/{id}/messages', [MessageController::class, 'store']);
        Route::get('/agent/tickets/{id}/messages', [MessageController::class, 'index']);
        Route::put('/agent/tickets/{id}/escalate', [AgentTicketController::class, 'escalate']);
        Route::get('/agent/dashboard/stats', [AgentDashboardController::class, 'stats']);
        Route::put('/agent/tickets/{id}/updatePriority', [AgentTicketController::class, 'updatePriority']);
        Route::get('/agent/categories', [CategoryController::class, 'index']);
    });

    Route::middleware('is.agent.l2')->group(function () {
        // rotte solo agenti L2
        Route::get('/agent/tickets/escalated/available', [AgentTicketController::class, 'escalatedAvailable']);
        Route::post('/agent/tickets/{id}/assignEscalated', [AgentTicketController::class, 'assignEscalated']);
    });

    Route::middleware('is.user')->group(function () {
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::get('/tickets', [TicketController::class, 'index']);
        Route::get('/tickets/{id}', [TicketController::class, 'show']);
        Route::post('/tickets/{id}/messages', [MessageController::class, 'store']);
        Route::get('/tickets/{id}/messages', [MessageController::class, 'index']);
    });

});
