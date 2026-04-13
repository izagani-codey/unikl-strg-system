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
use App\Http\Controllers\RequestTypeController;
use App\Http\Controllers\DeanController;

// ─── Welcome ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => view('welcome'));

// ─── Dev quick-switch (local only) ───────────────────────────────────────────
if (app()->environment('local')) {
    Route::post('/dev-login', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);
        
        $email = $request->input('email');
        
        // Find user by email
        $user = \App\Models\User::where('email', $email)->first();
        
        if (!$user) {
            return back()->with('error', 'User not found');
        }
        
        // Log in as user
        Auth::login($user);
        
        return redirect()->intended('dashboard');
    })->name('dev.login');
}

// ─── Dashboard ───────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->name('profile.signature.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Requests ───────────────────────────────────────────────────────────────
    Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [RequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/edit', [RequestController::class, 'edit'])->name('requests.edit');
    Route::patch('/requests/{id}', [RequestController::class, 'update'])->name('requests.update');
    Route::get('/requests/{id}/pdf/inline', [RequestController::class, 'viewGeneratedPdf'])
    ->name('requests.pdf.inline');

    // ── Staff 1 + 2 + Dean ──────────────────────────────────────────────────────────
    Route::middleware('role:staff1,staff2,dean')->group(function () {
        Route::patch('/requests/{id}/status', [RequestController::class, 'updateStatus'])->name('requests.updateStatus');
        Route::patch('/requests/{id}/priority', [RequestController::class, 'updatePriority'])->name('requests.updatePriority');
        Route::post('/requests/{id}/comments', [RequestController::class, 'addComment'])->name('requests.comment');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/form-templates', [FormTemplateController::class, 'index'])->name('form-templates.index');
    });

    // ── All roles — view requests ─────────────────────────────────────────────
    Route::get('/requests/{id}/print', [RequestController::class, 'printSummary'])->name('requests.print');
    Route::get('/requests/{id}/pdf', [RequestController::class, 'downloadPdf'])->name('requests.pdf');
    // Backward-compatible alias for older view references.
    Route::get('/requests/{id}/download-pdf', [RequestController::class, 'downloadPdf'])->name('requests.downloadPdf');
    Route::get('/requests/{id}/document', [RequestController::class, 'showMainDocument'])->name('requests.document.main');
    Route::get('/requests/{id}/documents/additional/{index}', [RequestController::class, 'showAdditionalDocument'])->name('requests.document.additional');
    
    // ── Template Preview Route ─────────────────────────────────────────────
    Route::get('/request-types/{id}/template', [RequestTypeController::class, 'getTemplate'])->name('request-types.template');

    // ── API Routes for Dynamic Form Fields ─────────────────────────────────
    Route::get('/api/request-types/{id}/fields', [RequestController::class, 'getDynamicFields'])->name('api.request-types.fields');

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');

    // ── Dean Routes ──────────────────────────────────────────────────────────────────
    Route::middleware('role:dean')->group(function () {
        Route::get('/dean/requests/{id}', [DeanController::class, 'show'])->name('dean.requests.show');
        Route::post('/dean/requests/{id}/approve', [DeanController::class, 'approve'])->name('dean.requests.approve');
        Route::post('/dean/requests/{id}/reject', [DeanController::class, 'reject'])->name('dean.requests.reject');
        Route::post('/dean/requests/{id}/return-staff1', [DeanController::class, 'returnRequest']);
Route::post('/dean/requests/{id}/return-staff2', [DeanController::class, 'returnRequest']);
    });

    // ── Dean Routes (feature-flagged) ─────────────────────────────────────────
    if (config('system.features.dean_interface', false)) {
        Route::middleware('role:dean')->group(function () {
            Route::get('/dean/dashboard', [DeanController::class, 'dashboard'])->name('dean.dashboard');
        });
    }

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
