<?php

use App\Http\Controllers\ManageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChangePasswordController;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// After login, send each role to its own area
Route::middleware(['auth', EnsureUserIsActive::class])->get('dashboard', function (Request $request) {
    return redirect($request->user()->role === 'student' ? '/my-attendance' : '/school');
})->name('dashboard');

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::get('change-password', [ChangePasswordController::class, 'show']);
    Route::put('change-password', [ChangePasswordController::class, 'update']);
});

// Students
Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':student'])->group(function () {
    Route::get('my-attendance', [SchoolController::class, 'myAttendance']);
});

// Admin and teachers
Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':admin,teacher'])->prefix('school')->group(function () {
    Route::get('/', [ReportController::class, 'dashboard']);
    Route::get('students', [SchoolController::class, 'students']);
    Route::get('attendance', [SchoolController::class, 'attendance']);
    Route::post('attendance', [SchoolController::class, 'saveAttendance']);
    Route::get('records', [SchoolController::class, 'records']);

    Route::get('reports', [ReportController::class, 'index']);
    Route::get('reports/export', [ReportController::class, 'export']);

    Route::get('schedules', [ManageController::class, 'schedules']);
    Route::put('schedules/{schedule}', [ManageController::class, 'updateSchedule']);
});

// Admin only
Route::middleware(['auth', EnsureUserIsActive::class, EnsureRole::class.':admin'])->prefix('school')->group(function () {
    Route::post('students', [SchoolController::class, 'storeStudent']);
    Route::post('students/{student}/login', [SchoolController::class, 'createStudentLogin']);
    Route::delete('students/{student}', [SchoolController::class, 'destroyStudent']);

    Route::get('subjects', [ManageController::class, 'subjects']);
    Route::post('subjects', [ManageController::class, 'storeSubject']);
    Route::delete('subjects/{subject}', [ManageController::class, 'destroySubject']);

    Route::get('setup', [ManageController::class, 'setup']);
    Route::post('setup/sections', [ManageController::class, 'storeSection']);
    Route::post('setup/teachers', [ManageController::class, 'storeTeacher']);

    Route::post('schedules', [ManageController::class, 'storeSchedule']);
    Route::delete('schedules/{schedule}', [ManageController::class, 'destroySchedule']);

    Route::get('users', [UserController::class, 'index']);
    Route::put('users/{user}/role', [UserController::class, 'updateRole']);
    Route::put('users/{user}/status', [UserController::class, 'toggleActive']);
    Route::put('users/{user}/password', [UserController::class, 'resetPassword']);

    Route::get('audit', [SchoolController::class, 'auditLog']);
});

require __DIR__.'/settings.php';

Route::inertia('/vuetify-test', 'VuetifyTest');