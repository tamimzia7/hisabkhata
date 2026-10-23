<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClientSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemUserController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/admin/profile', [DashboardController::class, 'profile'])->name('admin.profile');
    Route::put('/admin/profile', [DashboardController::class, 'updateProfile'])->name('admin.profile.update');

    Route::post('/admin/system-users', [SystemUserController::class, 'store'])->name('admin.system-users.store');
    Route::delete('/admin/system-users/{user}', [SystemUserController::class, 'destroy'])->name('admin.system-users.destroy');

    Route::put('/admin/client-settings', [ClientSettingController::class, 'update'])->name('admin.client-settings.update');

    Route::delete('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Language switching must work for guests too (home, login, register).
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session(['locale' => $locale]);
        session()->save();
    }

    return redirect()->back();
})->name('lang.switch');
