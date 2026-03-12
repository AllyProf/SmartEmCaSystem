<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StaffAttendanceController;

Route::post('/staff/login', [StaffAttendanceController::class, 'login']);

Route::middleware('staff.auth')->group(function () {
    Route::post('/staff/logout', [StaffAttendanceController::class, 'logout']);
    Route::get('/staff/profile', [StaffAttendanceController::class, 'getProfile']);
    
    Route::get('/staff/attendance/status', [StaffAttendanceController::class, 'getAttendanceStatus']);
    Route::post('/staff/attendance/sign-in', [StaffAttendanceController::class, 'signIn']);
    Route::post('/staff/attendance/sign-out', [StaffAttendanceController::class, 'signOut']);
    Route::get('/staff/attendance/history', [StaffAttendanceController::class, 'getAttendanceHistory']);
});
