<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\AssignmentController;
use App\Http\Controllers\Api\SubmissionController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::apiResource('courses', CourseController::class);
Route::apiResource('students', StudentController::class);
Route::apiResource('enrollments', EnrollmentController::class);

Route::prefix('assignments')->group(function () {
    Route::get('/', [AssignmentController::class, 'index']);
    Route::get('/{id}', [AssignmentController::class, 'show']);
    Route::post('/', [AssignmentController::class, 'store']);
    Route::put('/{id}', [AssignmentController::class, 'update']);
    Route::delete('/{id}', [AssignmentController::class, 'destroy']);
});

Route::prefix('submissions')->group(function () {
    Route::get('/', [SubmissionController::class, 'index']);
    Route::get('/{id}', [SubmissionController::class, 'show']);
    Route::post('/', [SubmissionController::class, 'store']);
    Route::post('/mark-absent', [SubmissionController::class, 'markAbsent']);
    Route::post('/batch-grade', [SubmissionController::class, 'batchGrade']);
    Route::post('/{id}/grade', [SubmissionController::class, 'grade']);
    Route::put('/{id}', [SubmissionController::class, 'update']);
    Route::delete('/{id}', [SubmissionController::class, 'destroy']);
});

Route::apiResource('attendance', AttendanceController::class);
