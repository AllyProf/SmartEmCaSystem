<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\FollowUpController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Forgot Password with OTP
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendOtp'])->name('password.otp.send');
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtpForm'])->name('password.otp.verify');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('password.otp.check');
Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Customers
    Route::resource('customers', CustomerController::class);

    // SMS
    Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    Route::get('/sms/create', [SmsController::class, 'create'])->name('sms.create');
    Route::post('/sms', [SmsController::class, 'store'])->name('sms.store');
    Route::get('/sms/logs', [SmsController::class, 'logs'])->name('sms.logs');
    Route::get('/sms/templates', [SmsController::class, 'getTemplates'])->name('sms.templates');

    // Follow-ups
    Route::resource('follow-ups', FollowUpController::class);

    // User Management
    Route::get('/users', [AuthController::class, 'users'])->name('users.index');
    Route::get('/users/create', [AuthController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AuthController::class, 'storeUser'])->name('users.store');
    Route::get('/users/{user}/edit', [AuthController::class, 'editUser'])->name('users.edit');
    Route::put('/users/{user}', [AuthController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{user}', [AuthController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [AuthController::class, 'toggleUserStatus'])->name('users.toggle_status');
    Route::post('/users/{user}/reset-device', [AuthController::class, 'resetDevice'])->name('users.reset_device');

    // Admin View for Visit Confirmations
    Route::get('/confirmations', [App\Http\Controllers\VisitorController::class, 'index'])->name('visits.index');
    Route::get('/confirmations/{id}', [App\Http\Controllers\VisitorController::class, 'show'])->name('visits.show');

    // Attendance (CEO view)
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [App\Http\Controllers\AttendanceController::class, 'index'])->name('index');
        Route::post('/settings', [App\Http\Controllers\AttendanceController::class, 'saveSettings'])->name('settings.save');
    });
});

// Visitor Confirmation Routes (Semi-Protected by Staff Verify Session)
Route::controller(App\Http\Controllers\VisitorController::class)->prefix('visits')->name('visits.')->group(function () {
    Route::get('/verify', 'showVerifyPage')->name('verify');
    Route::post('/verify', 'verifyStaff')->name('verify.check');
    
    Route::get('/selection', 'showSelectionPage')->name('selection');
    
    Route::get('/single', 'showSingleForm')->name('single');
    Route::get('/group', 'showGroupForm')->name('group');
    
    Route::post('/store', 'store')->name('store');
    Route::get('/success', 'showSuccessPage')->name('success');
});
