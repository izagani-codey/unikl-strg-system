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

// ─── Welcome ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('welcome'));

// ─── Dev quick-switch (local only) ───────────────────────────────────────────
if (app()->environment('local')) {
    Route::post('/dev-login', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if (Auth::attempt(['email' => $request->email, 'password' => 'password'])) {
            $request->session()->regenerate();
            return redirect()->route('dashboard');
        }
        return back()->with('error', 'Switch failed.');
    })->name('dev.login');
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Request Management Routes ─────────────────────────────────────────────────────
    Route::middleware(['auth', 'can:create,request'])->group(function () {
        Route::get('/requests', [RequestManagementController::class, 'index'])->name('requests.index');
        Route::get('/requests/create', [RequestManagementController::class, 'create'])->name('requests.create');
        Route::post('/requests', [RequestManagementController::class, 'store'])->name('requests.store');
        Route::get('/requests/{id}', [RequestManagementController::class, 'show'])->name('requests.show');
        Route::get('/requests/{id}/edit', [RequestManagementController::class, 'edit'])->name('requests.edit');
        Route::patch('/requests/{id}', [RequestManagementController::class, 'update'])->name('requests.update');
        Route::delete('/requests/{id}', [RequestManagementController::class, 'destroy'])->name('requests.destroy');
    });

    // ── Staff 1 + 2 ──────────────────────────────────────────────────────────
    Route::middleware('role:staff1,staff2')->group(function () {
        Route::patch('/requests/{id}/status', [RequestWorkflowController::class, 'updateStatus'])->name('requests.updateStatus');
        Route::patch('/requests/{id}/priority', [RequestWorkflowController::class, 'updatePriority'])->name('requests.updatePriority');
        Route::post('/requests/{id}/comments', [RequestWorkflowController::class, 'addComment'])->name('requests.comment');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    });

    // ── All roles — view requests ─────────────────────────────────────────────
    Route::get('/requests/{id}/print', [RequestPdfController::class, 'printSummary'])->name('requests.print');
    Route::get('/requests/{id}/pdf', [RequestPdfController::class, 'downloadPdf'])->name('requests.pdf');
    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    // ── Staff 2 Override Routes ─────────────────────────────────────────────────
    Route::middleware('role:staff2')->group(function () {
        Route::post('/requests/{id}/override', [RequestWorkflowController::class, 'performOverride'])->name('requests.override');
        Route::post('/override/toggle', [RequestWorkflowController::class, 'toggleOverrideMode'])->name('override.toggle');
    });

    // ── Request PDF Routes ──────────────────────────────────────────────────────
    Route::middleware(['auth', 'can:view,request'])->group(function () {
        Route::get('/requests/{id}/pdf', [RequestPdfController::class, 'downloadPdf'])->name('requests.pdf');
        Route::post('/requests/{id}/fill-pdf-form', [RequestPdfController::class, 'fillPdfForm'])->name('requests.fill-pdf-form');
        Route::get('/requests/{id}/dean-check', [RequestWorkflowController::class, 'checkDeanApproval'])->name('requests.dean.check');
    });

    // ── Dean Routes ──────────────────────────────────────────────────────────────────
    // Commented out - Dean interface hidden for now
    /*
    Route::middleware('role:dean')->group(function () {
        Route::get('/dean/dashboard', [DeanController::class, 'dashboard'])->name('dean.dashboard');
        Route::get('/dean/requests/{id}', [DeanController::class, 'show'])->name('dean.requests.show');
        Route::post('/dean/requests/{id}/approve', [DeanController::class, 'approve'])->name('dean.requests.approve');
        Route::post('/dean/requests/{id}/reject', [DeanController::class, 'reject'])->name('dean.requests.reject');
        Route::post('/dean/requests/{id}/return-staff1', [DeanController::class, 'returnToStaff1'])->name('dean.requests.return-staff1');
        Route::post('/dean/requests/{id}/return-staff2', [DeanController::class, 'returnToStaff2'])->name('dean.requests.return-staff2');
    });
    */

    // ── Staff 2 Admin Panel ──────────────────────────────────────────────────────────
    Route::middleware('role:staff2')->group(function () {
        // Admin panel
        Route::get('/staff2/admin-panel', [Staff2AdminController::class, 'index'])->name('staff2.admin');
        Route::get('/staff2/admin/users', [Staff2AdminController::class, 'users'])->name('staff2.admin.users');
        Route::get('/staff2/admin/request-types', [Staff2AdminController::class, 'requestTypes'])->name('staff2.admin.request-types');
        Route::get('/staff2/deployment-playbook', [Staff2AdminController::class, 'deploymentPlaybook'])->name('staff2.deployment-playbook');
        Route::post('/staff2/admin/request-types', [Staff2AdminController::class, 'storeRequestType'])->name('staff2.admin.request-types.store');
        Route::put('/staff2/admin/request-types/{id}', [Staff2AdminController::class, 'updateRequestType'])->name('staff2.admin.request-types.update');
        Route::delete('/staff2/admin/request-types/{id}', [Staff2AdminController::class, 'destroyRequestType'])->name('staff2.admin.request-types.destroy');

        // Excel export (replaces CSV)
        Route::get('/staff2/requests/export', [RequestController::class, 'exportExcel'])->name('requests.exportExcel');

        // Form templates
        Route::post('/form-templates', [FormTemplateController::class, 'store'])->name('form-templates.store');
        Route::delete('/form-templates/{id}', [FormTemplateController::class, 'destroy'])->name('form-templates.destroy');
    });
});

require __DIR__ . '/auth.php';
