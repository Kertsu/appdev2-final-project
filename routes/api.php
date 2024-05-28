<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

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

    Route::controller(UserController::class)->group(function () {
        Route::post('/users/{username}/validate', 'validate_username');
    });

    Route::controller(ConversationController::class)->group(function () {
        Route::get('/conversations', 'index');
        Route::get('/conversations/{conversation}/messages', 'get_messages')->missing(function () {
            return response()->json([
                'error' => 'Conversation not found'
            ], 404);
        });
    });

    Route::controller(MessageController::class)->group(function () {
        Route::post('/users/{username}/send', 'initiate_conversation');
        Route::post('/conversations/{conversation}/send', 'send_message')->missing(function () {
            return response()->json([
                'error' => 'Conversation not found'
            ], 404);
        });
    });
});
