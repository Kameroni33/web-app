<?php

use App\Http\Controllers\ActiveProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\AvailableAppController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ProfileController;
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

    // Usable before a profile is chosen: the picker and first-time setup.
    Route::get('profiles', [ProfileController::class, 'index'])->name('profiles.index');
    Route::post('profiles', [ProfileController::class, 'store'])->name('profiles.store');
    Route::post('profiles/{profile}/select', ActiveProfileController::class)->name('profiles.select');

    // Everything else acts as the profile chosen on this device.
    Route::middleware('profile')->group(function () {
        Route::patch('profiles/{profile}', [ProfileController::class, 'update'])->name('profiles.update');
        Route::delete('profiles/{profile}', [ProfileController::class, 'destroy'])->name('profiles.destroy');
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('apps', AvailableAppController::class)->name('apps.index');
    });
});
