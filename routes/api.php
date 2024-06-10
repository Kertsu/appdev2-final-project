<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
    Route::post('/otp/resend', 'resend_verification_code');
    Route::post('/otp/verify', 'verify_email');
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout');
    });

    Route::prefix('users')->controller(Usercontroller::class)->group(function () {
        Route::post('/{username}/send', 'initiate_conversation');
        Route::post('/{username}/validate', 'validate_username');
        Route::delete('/self/delete', 'destroy');
        Route::get('/self', 'get_self');
    });

    Route::prefix('conversations')->controller(ConversationController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/{conversation}/messages', 'get_messages')->missing(fn () => response()->json(['error' => 'Conversation not found'], 404));
    });

    Route::controller(MessageController::class)->group(function () {
        Route::post('/conversations/{conversation}/send', 'send_message')->missing(fn () => response()->json(['error' => 'Conversation not found'], 404));
        Route::patch('/conversations/{conversation}/messages/{message}', 'update_message')->missing(fn ($request) => handleMissing($request));
        Route::patch('/conversations/{conversation}/messages/{message}/read', 'mark_message_as_read')->missing(fn ($request) => handleMissing($request));
    });
});

