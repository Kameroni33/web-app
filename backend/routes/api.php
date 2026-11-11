<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('login-code', [LoginController::class, 'requestCode'])
        ->middleware('throttle:login-request')
        ->name('login-code');
    Route::post('verify-code', [LoginController::class, 'verifyCode'])
        ->middleware('throttle:login-verify')
        ->name('verify-code');
    Route::post('verify-link', [LoginController::class, 'verifyLink'])
        ->middleware('throttle:login-verify')
        ->name('verify-link');
    Route::post('logout', [LoginController::class, 'logout'])
        ->middleware('auth:sanctum')
        ->name('logout');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [LoginController::class, 'me'])->name('me');
    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');
});
