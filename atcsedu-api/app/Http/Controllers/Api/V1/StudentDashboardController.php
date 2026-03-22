<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_exams_on_platform' => Exam::query()->where('is_published', true)->count(),
                'exams_attempted' => ExamAttempt::query()->where('user_id', $user->id)->count(),
                'exams_passed' => ExamAttempt::query()->where('user_id', $user->id)->where('passed', true)->count(),
                'exams_failed' => ExamAttempt::query()->where('user_id', $user->id)->where('passed', false)->count(),
                'free_exams_available' => Exam::query()->where('is_published', true)->where('access_type', 'free')->count(),
                'paid_exams_available' => Exam::query()->where('is_published', true)->where('access_type', 'paid')->count(),
                'paid_exams_unlocked' => Purchase::query()
                    ->where('user_id', $user->id)
                    ->where('item_type', 'exam')
                    ->where('status', 'paid')
                    ->distinct('item_id')
                    ->count('item_id'),
            ],
        ]);
    }

    public function attempts(Request $request): JsonResponse
    {
        $attempts = ExamAttempt::query()
            ->with('exam:id,title')
            ->where('user_id', $request->user()->id)
            ->latest('submitted_at')
            ->latest('id')
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $attempts->getCollection()->map(fn (ExamAttempt $attempt) => [
                    'attempt_id' => $attempt->id,
                    'exam_id' => $attempt->exam_id,
                    'exam_title' => $attempt->exam?->title,
                    'score_percent' => (float) $attempt->score_percent,
                    'passed' => (bool) $attempt->passed,
                    'status' => $attempt->status,
                    'submitted_at' => optional($attempt->submitted_at)->toISOString(),
                ])->all(),
                'meta' => [
                    'current_page' => $attempts->currentPage(),
                    'per_page' => $attempts->perPage(),
                    'total' => $attempts->total(),
                    'last_page' => $attempts->lastPage(),
                ],
            ],
        ]);
    }
}
