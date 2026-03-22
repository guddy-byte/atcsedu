<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AdminExamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $exams = Exam::query()
            ->withCount('questions')
            ->latest()
            ->paginate($request->integer('per_page', 50))
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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:exams,slug'],
            'category' => ['nullable', 'string', 'max:255'],
            'access_type' => ['required', 'in:free,paid'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'attempt_limit' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $exam = Exam::create([
            ...$data,
            'slug' => $data['slug'] ?? Str::slug($data['title']),
            'price' => $data['access_type'] === 'free' ? 0 : ($data['price'] ?? 0),
            'duration_minutes' => $data['duration_minutes'] ?? 45,
            'attempt_limit' => $data['attempt_limit'] ?? 0,
            'is_published' => $data['is_published'] ?? true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Exam created',
            'data' => [
                'exam' => $this->formatExamSummary($exam),
            ],
        ], Response::HTTP_CREATED);
    }

    public function show(Exam $exam): JsonResponse
    {
        $exam->load(['questions.options']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'exam' => $this->formatExamDetail($exam),
            ],
        ]);
    }

    public function update(Request $request, Exam $exam): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:exams,slug,'.$exam->id],
            'category' => ['nullable', 'string', 'max:255'],
            'access_type' => ['sometimes', 'required', 'in:free,paid'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'attempt_limit' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('title', $data) && ! array_key_exists('slug', $data)) {
            $data['slug'] = Str::slug($data['title']);
        }

        if (($data['access_type'] ?? $exam->access_type) === 'free') {
            $data['price'] = 0;
        }

        $exam->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Exam updated',
            'data' => [
                'exam' => $this->formatExamDetail($exam->fresh()->load(['questions.options'])),
            ],
        ]);
    }

    public function destroy(Exam $exam): JsonResponse
    {
        $exam->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Exam deleted',
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
            'is_published' => (bool) $exam->is_published,
            'questions_count' => $exam->questions_count ?? $exam->questions()->count(),
        ];
    }

    private function formatExamDetail(Exam $exam): array
    {
        return [
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
                    'is_correct' => (bool) $option->is_correct,
                    'position' => $option->position,
                ])->all(),
            ])->all(),
        ];
    }
}
