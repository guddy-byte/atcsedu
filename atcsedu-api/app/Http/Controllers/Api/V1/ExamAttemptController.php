<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SubmitExamAttemptRequest;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ExamAttemptController extends Controller
{
    public function store(SubmitExamAttemptRequest $request, Exam $exam): JsonResponse
    {
        $user = $request->user();

        if (! $this->userHasExamAccess($user, $exam)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have access to this exam.',
            ], Response::HTTP_FORBIDDEN);
        }

        $exam->load(['questions.options']);

        $questions = $exam->questions->keyBy('id');
        $submittedAnswers = collect($request->validated('answers'));

        $invalidQuestionIds = $submittedAnswers
            ->pluck('question_id')
            ->reject(fn ($questionId) => $questions->has($questionId));

        if ($invalidQuestionIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'answers' => ['One or more answers do not belong to the selected exam.'],
            ]);
        }

        $objectiveTotal = (int) $exam->questions->where('type', 'objective')->sum('points');
        $theoryTotal = (int) $exam->questions->where('type', 'theory')->sum('points');
        $maxScore = $objectiveTotal + $theoryTotal;
        $objectiveAnswered = 0;
        $objectiveCorrect = 0;
        $theoryAnswered = 0;
        $objectiveScore = 0;
        $reviewPayload = [];

        $attempt = DB::transaction(function () use (
            $user,
            $exam,
            $questions,
            $submittedAnswers,
            $objectiveTotal,
            $theoryTotal,
            $maxScore,
            &$objectiveAnswered,
            &$objectiveCorrect,
            &$theoryAnswered,
            &$objectiveScore,
            &$reviewPayload
        ): ExamAttempt {
            $attempt = ExamAttempt::create([
                'user_id' => $user->id,
                'exam_id' => $exam->id,
                'status' => 'submitted',
                'objective_total' => $objectiveTotal,
                'theory_total' => $theoryTotal,
                'max_score' => $maxScore,
                'started_at' => now(),
                'submitted_at' => now(),
            ]);

            foreach ($submittedAnswers as $answerPayload) {
                $question = $questions->get($answerPayload['question_id']);
                $selectedOptionId = $answerPayload['selected_option_id'] ?? null;
                $theoryAnswer = $answerPayload['theory_answer'] ?? null;
                $isCorrect = null;
                $awardedPoints = 0;

                if ($question->type === 'objective') {
                    $objectiveAnswered++;

                    $option = $question->options->firstWhere('id', $selectedOptionId);

                    if (! $option) {
                        throw ValidationException::withMessages([
                            'answers' => ['An objective answer used an option that does not belong to its question.'],
                        ]);
                    }

                    $isCorrect = (bool) $option->is_correct;
                    $awardedPoints = $isCorrect ? $question->points : 0;
                    $objectiveScore += $awardedPoints;
                    $objectiveCorrect += $isCorrect ? 1 : 0;

                    $reviewPayload[] = [
                        'question_id' => $question->id,
                        'correct_option_id' => $question->options->firstWhere('is_correct', true)?->id,
                        'correct_option_text' => $question->options->firstWhere('is_correct', true)?->option_text,
                    ];
                }

                if ($question->type === 'theory' && filled($theoryAnswer)) {
                    $theoryAnswered++;
                }

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected_option_id' => $selectedOptionId,
                    'theory_answer' => $theoryAnswer,
                    'is_correct' => $isCorrect,
                    'awarded_points' => $awardedPoints,
                ]);
            }

            $scorePercent = $maxScore > 0
                ? round(($objectiveScore / $maxScore) * 100, 2)
                : 0;

            $attempt->update([
                'objective_score' => $objectiveScore,
                'theory_score' => 0,
                'total_score' => $objectiveScore,
                'score_percent' => $scorePercent,
                'passed' => $scorePercent >= 50,
            ]);

            return $attempt->fresh();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Exam submitted successfully',
            'data' => [
                'attempt' => [
                    'id' => $attempt->id,
                    'exam_id' => $attempt->exam_id,
                    'status' => $attempt->status,
                    'objective_score' => $attempt->objective_score,
                    'objective_total' => $attempt->objective_total,
                    'theory_score' => $attempt->theory_score,
                    'theory_total' => $attempt->theory_total,
                    'total_score' => $attempt->total_score,
                    'max_score' => $attempt->max_score,
                    'score_percent' => (float) $attempt->score_percent,
                    'passed' => (bool) $attempt->passed,
                    'submitted_at' => Carbon::parse($attempt->submitted_at)->toISOString(),
                ],
                'summary' => [
                    'objective' => [
                        'answered' => $objectiveAnswered,
                        'correct' => $objectiveCorrect,
                        'total' => $exam->questions->where('type', 'objective')->count(),
                    ],
                    'theory' => [
                        'answered' => $theoryAnswered,
                        'pending_review' => $theoryAnswered > 0,
                    ],
                ],
                'review' => $reviewPayload,
            ],
        ], Response::HTTP_CREATED);
    }

    private function userHasExamAccess(User $user, Exam $exam): bool
    {
        if ($exam->access_type === 'free' || $user->role === User::ROLE_ADMIN) {
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
