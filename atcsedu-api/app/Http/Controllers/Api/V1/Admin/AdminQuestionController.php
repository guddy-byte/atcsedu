<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreQuestionRequest;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AdminQuestionController extends Controller
{
    public function store(StoreQuestionRequest $request, Exam $exam): JsonResponse
    {
        $question = DB::transaction(function () use ($request, $exam): Question {
            $question = $exam->questions()->create([
                'type' => $request->validated('type'),
                'prompt' => $request->validated('prompt'),
                'points' => $request->validated('points') ?? 1,
                'position' => $request->validated('position') ?? (($exam->questions()->max('position') ?? 0) + 1),
            ]);

            $this->syncOptions($question, $request->validated('options', []));

            return $question->fresh('options');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Question created',
            'data' => [
                'question' => $this->formatQuestion($question),
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(StoreQuestionRequest $request, Question $question): JsonResponse
    {
        $question = DB::transaction(function () use ($request, $question): Question {
            $question->update([
                'type' => $request->validated('type'),
                'prompt' => $request->validated('prompt'),
                'points' => $request->validated('points') ?? $question->points,
                'position' => $request->validated('position') ?? $question->position,
            ]);

            $this->syncOptions($question, $request->validated('options', []));

            return $question->fresh('options');
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Question updated',
            'data' => [
                'question' => $this->formatQuestion($question),
            ],
        ]);
    }

    public function destroy(Question $question): JsonResponse
    {
        $question->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Question deleted',
        ]);
    }

    private function syncOptions(Question $question, array $options): void
    {
        $question->options()->delete();

        if ($question->type !== 'objective') {
            return;
        }

        foreach ($options as $index => $option) {
            $question->options()->create([
                'label' => $option['label'] ?? chr(65 + $index),
                'option_text' => $option['option_text'],
                'is_correct' => $option['is_correct'] ?? false,
                'position' => $option['position'] ?? ($index + 1),
            ]);
        }
    }

    private function formatQuestion(Question $question): array
    {
        return [
            'id' => $question->id,
            'exam_id' => $question->exam_id,
            'type' => $question->type,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'position' => $question->position,
            'options' => $question->options->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
                'option_text' => $option->option_text,
                'is_correct' => (bool) $option->is_correct,
                'position' => $option->position,
            ])->all(),
        ];
    }
}
