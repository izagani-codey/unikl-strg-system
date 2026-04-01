<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Staff2AdminController;
use App\Http\Controllers\FormTemplateController;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// Dev Quick-Switch (local environment only)
if (app()->environment('local')) {
    Route::post('/dev-login', function (Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if (Auth::attempt(['email' => $request->email, 'password' => 'password'])) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }

        return back()->with('error', 'Switch failed. Check your seeder!');
    })->name('dev.login');
}

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{id}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
    Route::patch('/users/{id}/toggle', [AdminController::class, 'toggleUserStatus'])->name('users.toggle');
    Route::get('/request-types', [AdminController::class, 'requestTypes'])->name('request-types');
    Route::post('/request-types', [AdminController::class, 'createRequestType'])->name('request-types.create');
    Route::patch('/request-types/{id}', [AdminController::class, 'updateRequestType'])->name('request-types.update');
    Route::patch('/request-types/{id}/toggle', [AdminController::class, 'toggleRequestTypeStatus'])->name('request-types.toggle');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'settings'])->name('settings.update');
    Route::get('/logs', [AdminController::class, 'logs'])->name('logs');
    Route::post('/notifications', [AdminController::class, 'sendNotification'])->name('notifications.send');
    Route::get('/stats', [AdminController::class, 'getStats'])->name('stats');
    Route::post('/clear-cache', [AdminController::class, 'clearCache'])->name('clear-cache');
});

// Dashboard AJAX Routes
Route::middleware(['auth', 'verified'])->prefix('dashboard/ajax')->name('dashboard.ajax.')->group(function () {
    Route::get('/stats', [DashboardController::class, 'getStats'])->name('stats');
    Route::get('/activity', [DashboardController::class, 'getActivity'])->name('activity');
    Route::get('/deadline-alerts', [DashboardController::class, 'getDeadlineAlerts'])->name('deadline-alerts');
    Route::get('/performance', [DashboardController::class, 'getPerformanceComparison'])->name('performance');
    Route::post('/clear-cache', [DashboardController::class, 'clearCache'])->name('clear-cache');
});

Route::middleware('auth')->group(function () {
    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Requests (all roles)
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/print', [RequestController::class, 'print'])->name('requests.print');
    Route::get('/requests/{id}/edit', [RequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    Route::delete('/requests/{id}', [RequestController::class, 'destroy'])->name('requests.delete');

    // Request actions (staff)
    Route::patch('/requests/{id}/status', [RequestController::class, 'updateStatus'])->name('requests.updateStatus');
    Route::post('/requests/{id}/comment', [RequestController::class, 'comment'])->name('requests.comment');
    Route::post('/requests/bulk-update', [RequestController::class, 'bulkUpdateStatus'])->name('requests.bulkUpdate');
    Route::get('/requests/export', [RequestController::class, 'export'])->name('requests.export');

    // Form Templates (staff2 admin)
    Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    Route::get('/form-templates/create', [FormTemplateController::class, 'create'])->name('form-templates.create');
    Route::post('/form-templates', [FormTemplateController::class, 'store'])->name('form-templates.store');
    Route::get('/form-templates/{id}/download', [FormTemplateController::class, 'download'])->name('form-templates.download');
    Route::delete('/form-templates/{id}', [FormTemplateController::class, 'destroy'])->name('form-templates.delete');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'delete'])->name('notifications.delete');

    // Audit Logs
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
});

require __DIR__.'/auth.php';
