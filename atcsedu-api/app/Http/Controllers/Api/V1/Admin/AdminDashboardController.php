<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
                'materials' => Material::query()->count(),
                'exams' => Exam::query()->count(),
                'attempts' => ExamAttempt::query()->count(),
                'revenue_total' => (float) Purchase::query()->where('status', 'paid')->sum('amount'),
                'pending_theory_reviews' => \App\Models\ExamAttemptAnswer::query()
                    ->whereNull('is_correct')
                    ->whereNotNull('theory_answer')
                    ->count(),
            ],
        ]);
    }
}
