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

Route::middleware('auth')->group(function () {

    // Profile (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admission only
    Route::middleware('role:admission')->group(function () {
        Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
        Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
        Route::get('/requests/{id}/edit', [RequestController::class, 'edit'])->name('requests.edit');
        Route::patch('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    });

    // Staff 1 + Staff 2 only
    Route::middleware('role:staff1,staff2')->group(function () {
        Route::patch('/requests/{id}/status', [RequestController::class, 'updateStatus'])->name('requests.updateStatus');
        Route::post('/requests/{id}/comments', [RequestController::class, 'addComment'])->name('requests.comment');
    });

    // All roles — view requests
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/print', [RequestController::class, 'printSummary'])->name('requests.print');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    Route::middleware('role:staff1,staff2')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });


    Route::middleware('role:staff1,staff2')->group(function () {
        // Both staff roles can review/download blank templates.
        Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    });

    Route::middleware('role:staff2')->group(function () {
        Route::get('/staff2/admin-panel', [Staff2AdminController::class, 'index'])->name('staff2.admin');
        Route::get('/staff2/requests/export', [RequestController::class, 'exportCsv'])->name('requests.exportCsv');
        Route::post('/form-templates', [FormTemplateController::class, 'store'])->name('form-templates.store');
        Route::delete('/form-templates/{id}', [FormTemplateController::class, 'destroy'])->name('form-templates.destroy');
    });


});


require __DIR__.'/auth.php';
