<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\DashboardController;

// Welcome page
Route::get('/', function () {
    return view('welcome');
});

// Dev Quick-Switch (remove before production)
Route::post('/dev-login', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    if (Auth::attempt(['email' => $request->email, 'password' => 'password'])) {
        $request->session()->regenerate();
        return redirect()->route('dashboard');
    }

    return back()->with('error', 'Switch failed. Check your seeder!');
})->name('dev.login');

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

});


require __DIR__.'/auth.php';
