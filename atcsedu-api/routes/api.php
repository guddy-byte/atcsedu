<?php

use App\Http\Controllers\Api\V1\Admin\AdminAttemptController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminExamController;
use App\Http\Controllers\Api\V1\Admin\AdminMaterialController;
use App\Http\Controllers\Api\V1\Admin\AdminQuestionController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExamAccessController;
use App\Http\Controllers\Api\V1\ExamAttemptController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\MaterialController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/materials', [MaterialController::class, 'index']);
    Route::get('/materials/{material}', [MaterialController::class, 'show']);

    Route::get('/exams', [ExamController::class, 'index']);
    Route::get('/exams/{exam}', [ExamController::class, 'show']);

    Route::post('/payments/paystack/webhook', [PaymentController::class, 'webhook']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/student/dashboard/stats', [StudentDashboardController::class, 'stats']);
        Route::get('/student/attempts', [StudentDashboardController::class, 'attempts']);

        Route::post('/exams/{exam}/attempts', [ExamAttemptController::class, 'store']);
        Route::get('/exams/{exam}/access', [ExamAccessController::class, 'show']);

        Route::post('/payments/paystack/initialize', [PaymentController::class, 'initialize']);
        Route::get('/payments/{reference}/verify', [PaymentController::class, 'verify']);

        Route::prefix('admin')->middleware('admin')->group(function (): void {
            Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

            Route::apiResource('/materials', AdminMaterialController::class);
            Route::apiResource('/exams', AdminExamController::class);

            Route::post('/exams/{exam}/questions', [AdminQuestionController::class, 'store']);
            Route::put('/questions/{question}', [AdminQuestionController::class, 'update']);
            Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy']);

            Route::post('/attempts/{attempt}/grade-theory', [AdminAttemptController::class, 'gradeTheory']);
        });
    });
});
