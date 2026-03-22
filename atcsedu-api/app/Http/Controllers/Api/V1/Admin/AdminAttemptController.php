<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GradeTheoryRequest;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminAttemptController extends Controller
{
    public function gradeTheory(GradeTheoryRequest $request, ExamAttempt $attempt): JsonResponse
    {
        $attempt->load(['exam.questions', 'answers.question']);
        $grades = collect($request->validated('grades'))->keyBy('question_id');

        DB::transaction(function () use ($attempt, $grades): void {
            foreach ($grades as $questionId => $grade) {
                $answer = $attempt->answers->firstWhere('question_id', (int) $questionId);
                $question = $attempt->exam->questions->firstWhere('id', (int) $questionId);

                if (! $answer || ! $question || $question->type !== 'theory') {
                    throw ValidationException::withMessages([
                        'grades' => ['One or more grades do not belong to theory answers on this attempt.'],
                    ]);
                }

                if ($grade['awarded_points'] > $question->points) {
                    throw ValidationException::withMessages([
                        'grades' => ["Question {$question->id} cannot receive more than {$question->points} points."],
                    ]);
                }

                $answer->update([
                    'awarded_points' => $grade['awarded_points'],
                    'review_comment' => $grade['review_comment'] ?? null,
                ]);
            }

            $attempt->refresh()->load(['exam.questions', 'answers.question']);

            $objectiveScore = (int) $attempt->answers
                ->filter(fn ($answer) => $answer->question?->type === 'objective')
                ->sum('awarded_points');
            $theoryScore = (int) $attempt->answers
                ->filter(fn ($answer) => $answer->question?->type === 'theory')
                ->sum('awarded_points');
            $maxScore = (int) $attempt->exam->questions->sum('points');
            $scorePercent = $maxScore > 0
                ? round((($objectiveScore + $theoryScore) / $maxScore) * 100, 2)
                : 0;

            $attempt->update([
                'status' => 'graded',
                'objective_score' => $objectiveScore,
                'theory_score' => $theoryScore,
                'total_score' => $objectiveScore + $theoryScore,
                'max_score' => $maxScore,
                'score_percent' => $scorePercent,
                'passed' => $scorePercent >= 50,
            ]);
        });

        $attempt = $attempt->fresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Theory grading completed',
            'data' => [
                'attempt_id' => $attempt->id,
                'score_percent' => (float) $attempt->score_percent,
                'passed' => (bool) $attempt->passed,
                'status' => $attempt->status,
            ],
        ]);
    }
}
