<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClientSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\Student\BazarRecordController;
use App\Http\Controllers\Student\MealEntryController;
use App\Http\Controllers\Student\MessAccountController;
use App\Http\Controllers\Student\MessController;
use App\Http\Controllers\Student\MessDepositController;
use App\Http\Controllers\Student\MessExpenseController;
use App\Http\Controllers\Student\MessInvitationController;
use App\Http\Controllers\Student\MessMemberController;
use App\Http\Controllers\Student\MessSettingsController;
use App\Http\Controllers\Student\MessTransactionController;
use App\Http\Controllers\Student\MonthlySettlementController;
use App\Http\Controllers\Student\ReportController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentProfileController;
use App\Http\Controllers\Student\StudentSubscriptionController;
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

Route::middleware('auth')->prefix('student')->name('student.')->group(function () {
    Route::get('subscription', [StudentSubscriptionController::class, 'show'])->name('subscription.show');
    Route::post('subscription', [StudentSubscriptionController::class, 'activate'])->name('subscription.activate');

    Route::middleware(['student.subscription', 'student.mess'])->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');

        Route::get('messes', [MessController::class, 'index'])->name('messes.index');
        Route::get('messes/create', [MessController::class, 'create'])->name('messes.create');
        Route::post('messes', [MessController::class, 'store'])->name('messes.store');
        Route::get('messes/{mess}/edit', [MessController::class, 'edit'])->name('messes.edit');
        Route::put('messes/{mess}', [MessController::class, 'update'])->name('messes.update');
        Route::delete('messes/{mess}', [MessController::class, 'destroy'])->name('messes.destroy');
        Route::post('messes/{mess}/switch', [MessController::class, 'switch'])->name('messes.switch');

        Route::prefix('mess/{mess}')->name('mess.')->group(function () {
            Route::get('members', [MessMemberController::class, 'index'])->name('members.index');
            Route::post('members', [MessMemberController::class, 'store'])->name('members.store');
            Route::put('members/{member}', [MessMemberController::class, 'update'])->name('members.update');
            Route::delete('members/{member}', [MessMemberController::class, 'destroy'])->name('members.destroy');

            Route::post('invitations', [MessInvitationController::class, 'store'])->name('invitations.store');
            Route::delete('invitations/{invitation}', [MessInvitationController::class, 'destroy'])->name('invitations.destroy');

            Route::get('meals', [MealEntryController::class, 'index'])->name('meals.index');
            Route::post('meals', [MealEntryController::class, 'store'])->name('meals.store');
            Route::put('meals/{entry}', [MealEntryController::class, 'update'])->name('meals.update');
            Route::delete('meals/{entry}', [MealEntryController::class, 'destroy'])->name('meals.destroy');

            Route::get('bazar', [BazarRecordController::class, 'index'])->name('bazar.index');
            Route::post('bazar', [BazarRecordController::class, 'store'])->name('bazar.store');
            Route::put('bazar/{bazar}', [BazarRecordController::class, 'update'])->name('bazar.update');
            Route::delete('bazar/{bazar}', [BazarRecordController::class, 'destroy'])->name('bazar.destroy');

            Route::get('deposits', [MessDepositController::class, 'index'])->name('deposits.index');
            Route::post('deposits', [MessDepositController::class, 'store'])->name('deposits.store');
            Route::put('deposits/{deposit}', [MessDepositController::class, 'update'])->name('deposits.update');
            Route::delete('deposits/{deposit}', [MessDepositController::class, 'destroy'])->name('deposits.destroy');

            Route::get('expenses', [MessExpenseController::class, 'index'])->name('expenses.index');
            Route::post('expenses', [MessExpenseController::class, 'store'])->name('expenses.store');
            Route::put('expenses/{expense}', [MessExpenseController::class, 'update'])->name('expenses.update');
            Route::delete('expenses/{expense}', [MessExpenseController::class, 'destroy'])->name('expenses.destroy');

            Route::get('accounts', [MessAccountController::class, 'index'])->name('accounts.index');
            Route::post('accounts', [MessAccountController::class, 'store'])->name('accounts.store');
            Route::put('accounts/{account}', [MessAccountController::class, 'update'])->name('accounts.update');
            Route::post('transfers', [MessAccountController::class, 'transfer'])->name('accounts.transfer');

            Route::get('transactions', [MessTransactionController::class, 'index'])->name('transactions.index');

            Route::get('settlements', [MonthlySettlementController::class, 'index'])->name('settlements.index');
            Route::get('settlements/{settlement}', [MonthlySettlementController::class, 'show'])->name('settlements.show');
            Route::post('settlements/finalize', [MonthlySettlementController::class, 'finalize'])->name('settlements.finalize');
            Route::post('settlements/{settlement}/reopen', [MonthlySettlementController::class, 'reopen'])->name('settlements.reopen');

            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/{type}', [ReportController::class, 'show'])->name('reports.show');

            Route::get('settings', [MessSettingsController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [MessSettingsController::class, 'update'])->name('settings.update');
            Route::get('permissions', [MessSettingsController::class, 'permissions'])->name('permissions.index');
            Route::put('permissions', [MessSettingsController::class, 'updatePermissions'])->name('permissions.update');
        });

        Route::get('invitations/{token}', [MessInvitationController::class, 'show'])->name('invitations.show');
        Route::post('invitations/{token}/accept', [MessInvitationController::class, 'accept'])->name('invitations.accept');

        Route::get('profile', [StudentProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [StudentProfileController::class, 'update'])->name('profile.update');
    });
});

// Language switching must work for guests too (home, login, register).
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session(['locale' => $locale]);
        session()->save();
    }

    return redirect()->back();
})->name('lang.switch');
