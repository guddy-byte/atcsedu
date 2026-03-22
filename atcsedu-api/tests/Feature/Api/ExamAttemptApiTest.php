<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamAttemptApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_a_free_exam_attempt(): void
    {
        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $exam = Exam::query()->create([
            'title' => 'English Mock',
            'slug' => 'english-mock',
            'category' => 'Languages',
            'access_type' => 'free',
            'price' => 0,
            'duration_minutes' => 45,
            'attempt_limit' => 0,
            'description' => 'English mock exam',
            'is_published' => true,
        ]);

        $question = Question::query()->create([
            'exam_id' => $exam->id,
            'type' => 'objective',
            'prompt' => 'Nigeria gained independence in which year?',
            'points' => 1,
            'position' => 1,
        ]);

        $wrongOption = $question->options()->create([
            'label' => 'A',
            'option_text' => '1957',
            'is_correct' => false,
            'position' => 1,
        ]);

        $correctOption = $question->options()->create([
            'label' => 'B',
            'option_text' => '1960',
            'is_correct' => true,
            'position' => 2,
        ]);

        Sanctum::actingAs($student);

        $this->postJson("/api/v1/exams/{$exam->id}/attempts", [
            'answers' => [
                [
                    'question_id' => $question->id,
                    'selected_option_id' => $correctOption->id,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.attempt.objective_score', 1)
            ->assertJsonPath('data.summary.objective.correct', 1);

        $this->assertDatabaseCount('exam_attempts', 1);
        $this->assertDatabaseCount('exam_attempt_answers', 1);
        $this->assertDatabaseMissing('question_options', [
            'id' => $wrongOption->id,
            'is_correct' => true,
        ]);
    }
}
