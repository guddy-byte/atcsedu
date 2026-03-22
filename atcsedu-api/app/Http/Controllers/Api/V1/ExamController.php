<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $exams = Exam::query()
            ->withCount('questions')
            ->where('is_published', true)
            ->when(
                $request->filled('access_type'),
                fn ($query) => $query->where('access_type', $request->string('access_type')->toString())
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();

                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 12))
            ->withQueryString();

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $exams->getCollection()->map(fn (Exam $exam) => $this->formatExamSummary($exam))->all(),
                'meta' => [
                    'current_page' => $exams->currentPage(),
                    'per_page' => $exams->perPage(),
                    'total' => $exams->total(),
                    'last_page' => $exams->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Exam $exam): JsonResponse
    {
        abort_unless($exam->is_published, 404);

        if ($exam->access_type === 'paid' && ! $this->userHasExamAccess($request->user(), $exam)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment is required before viewing this exam.',
            ], Response::HTTP_FORBIDDEN);
        }

        $exam->load(['questions.options']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'exam' => [
                    ...$this->formatExamSummary($exam),
                    'questions' => $exam->questions->map(fn ($question) => [
                        'id' => $question->id,
                        'type' => $question->type,
                        'prompt' => $question->prompt,
                        'points' => $question->points,
                        'position' => $question->position,
                        'options' => $question->options->map(fn ($option) => [
                            'id' => $option->id,
                            'label' => $option->label,
                            'option_text' => $option->option_text,
                        ])->all(),
                    ])->all(),
                ],
            ],
        ]);
    }

    private function formatExamSummary(Exam $exam): array
    {
        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'slug' => $exam->slug,
            'category' => $exam->category,
            'access_type' => $exam->access_type,
            'price' => (float) $exam->price,
            'duration_minutes' => $exam->duration_minutes,
            'attempt_limit' => $exam->attempt_limit,
            'description' => $exam->description,
            'questions_count' => $exam->questions_count ?? $exam->questions()->count(),
        ];
    }

    private function userHasExamAccess(?User $user, Exam $exam): bool
    {
        if ($exam->access_type === 'free') {
            return true;
        }

        if (! $user) {
            return false;
        }

        if ($user->role === User::ROLE_ADMIN) {
            return true;
        }

        return Purchase::query()
            ->where('user_id', $user->id)
            ->where('item_type', 'exam')
            ->where('item_id', $exam->id)
            ->where('status', 'paid')
            ->exists();
    }
}
