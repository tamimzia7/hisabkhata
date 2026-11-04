<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClientSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SystemUserController;
use App\Http\Controllers\TicketController;
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

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/create', [NotificationController::class, 'create'])->name('notifications.create');
    Route::get('/notifications/feed', [NotificationController::class, 'feed'])->name('notifications.get');
    Route::post('/notifications', [NotificationController::class, 'mark'])->name('notifications.mark');
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications', [NotificationController::class, 'destroySelected'])->name('notifications.destroy-selected');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

    Route::get('/admin/profile', [DashboardController::class, 'profile'])->name('admin.profile');
    Route::put('/admin/profile', [DashboardController::class, 'updateProfile'])->name('admin.profile.update');
    Route::delete('/admin/profile/avatar', [DashboardController::class, 'destroyAvatar'])->name('admin.profile.avatar.destroy');
    Route::delete('/admin/profile', [DashboardController::class, 'destroyProfile'])->name('admin.profile.destroy');

    Route::post('/admin/system-users', [SystemUserController::class, 'store'])->name('admin.system-users.store');
    Route::put('/admin/system-users/{user}', [SystemUserController::class, 'update'])->name('admin.system-users.update');
    Route::delete('/admin/system-users/{user}', [SystemUserController::class, 'destroy'])->name('admin.system-users.destroy');

    Route::post('/admin/roles-permission', [RolePermissionController::class, 'update'])->name('admin.roles-permission.update');

    Route::put('/admin/client-settings', [ClientSettingController::class, 'update'])->name('admin.client-settings.update');

    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{ticket}/messages', [TicketController::class, 'fetchMessages'])->name('tickets.messages.fetch');
    Route::post('/tickets/{ticket}/messages', [TicketController::class, 'storeMessage'])->name('tickets.messages.store');
    Route::post('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status.update');
    Route::post('/tickets/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('tickets.priority.update');
    Route::post('/tickets/{ticket}/assignee', [TicketController::class, 'updateAssignee'])->name('tickets.assignee.update');
    Route::get('/tickets/{ticket}/attachments/{attachment}', [TicketController::class, 'downloadAttachment'])->name('tickets.attachments.download');
    Route::get('/tickets/{ticket}/attachments/{attachment}/preview', [TicketController::class, 'previewAttachment'])->name('tickets.attachments.preview');

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
