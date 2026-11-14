<?php

use App\Http\Controllers\ActiveProfileController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\GroupInviteController;
use App\Http\Controllers\Admin\GroupMemberController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\AvailableAppController;
use App\Http\Controllers\Gifts\ClaimController;
use App\Http\Controllers\Gifts\ItemOrderController;
use App\Http\Controllers\Gifts\ListController;
use App\Http\Controllers\Gifts\MyItemController;
use App\Http\Controllers\Gifts\PeopleController;
use App\Http\Controllers\Gifts\WishlistController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\InviteController;
use App\Http\Controllers\ProfileAvatarController;
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

// Invite links: preview and sign-up are public; joining needs a signed-in account.
Route::prefix('invites/{token}')->name('invites.')->where(['token' => '[0-9a-f]{48}'])->group(function () {
    Route::get('/', [InviteController::class, 'show'])->middleware('throttle:invite')->name('show');
    Route::post('register', [InviteController::class, 'register'])
        ->middleware(['throttle:invite', 'throttle:login-request'])
        ->name('register');
    Route::post('accept', [InviteController::class, 'accept'])
        ->middleware(['auth:sanctum', 'throttle:invite'])
        ->name('accept');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [LoginController::class, 'me'])->name('me');
    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('sessions/others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

    // Usable before a profile is chosen: the picker and first-time setup.
    Route::get('profiles', [ProfileController::class, 'index'])->name('profiles.index');
    Route::post('profiles', [ProfileController::class, 'store'])->name('profiles.store');
    Route::post('profiles/{profile}/select', ActiveProfileController::class)->name('profiles.select');
    Route::get('profiles/{profile}/avatar', [ProfileAvatarController::class, 'show'])->name('profiles.avatar.show');

    // Everything else acts as the profile chosen on this device.
    Route::middleware('profile')->group(function () {
        Route::patch('profiles/{profile}', [ProfileController::class, 'update'])->name('profiles.update');
        Route::delete('profiles/{profile}', [ProfileController::class, 'destroy'])->name('profiles.destroy');
        Route::post('profiles/{profile}/avatar', [ProfileAvatarController::class, 'update'])
            ->middleware('throttle:20,1')
            ->name('profiles.avatar.update');
        Route::delete('profiles/{profile}/avatar', [ProfileAvatarController::class, 'destroy'])->name('profiles.avatar.destroy');
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('apps', AvailableAppController::class)->name('apps.index');

        // Gifts: your own list, and the lists of people you share an account or family with.
        Route::prefix('gifts')->name('gifts.')->group(function () {
            Route::put('items/order', ItemOrderController::class)->name('items.order');
            Route::apiResource('items', MyItemController::class)->except('show');
            Route::apiResource('wishlists', WishlistController::class)->except('show');
            Route::get('people', PeopleController::class)->name('people');
            Route::get('lists/{profile}', ListController::class)->name('lists.show');
            Route::post('items/{item}/claim', [ClaimController::class, 'store'])->name('items.claim');
            Route::delete('items/{item}/claim', [ClaimController::class, 'destroy'])->name('items.unclaim');
        });

        // Admin accounts (accounts:admin), using their admin profile.
        Route::prefix('admin')->name('admin.')->middleware('can:manage-groups')->group(function () {
            Route::apiResource('groups', AdminGroupController::class);
            Route::delete('groups/{group}/members/{member}', [GroupMemberController::class, 'destroy'])->name('groups.members.destroy');
            Route::post('groups/{group}/invites', [GroupInviteController::class, 'store'])->name('groups.invites.store');
            Route::delete('invites/{invite}', [GroupInviteController::class, 'destroy'])->name('invites.destroy');
        });
    });
});
