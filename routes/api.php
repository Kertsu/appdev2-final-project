<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
});

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::controller(AuthController::class)->group(function () {
        Route::post('/logout', 'logout')->name('logout');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/users/{username}', 'show');
        Route::post('/users/{link_token}/validate', 'validate_link_token');
    });

    Route::controller(MessageController::class)->group(function () {
        Route::post('/users/{link_token}/send', 'initiate_conversation');
        Route::post('/conversations/{conversation}/send', 'send_message')->missing(function () {
            return response()->json([
                'error' => 'Conversation not found'
            ], 404);
        });
    });
});
