<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Laravel\Sanctum\PersonalAccessToken;

class DatabaseSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@atcsedu.com';

    private const DEMO_STUDENT_EMAIL = 'student.demo@atcsedu.com';

    private const DEMO_STUDENT_PASSWORD = 'ATCSDemo2026';

    private const DEMO_MATERIAL_TITLES = [
        'English Essay Starter',
        'JAMB Grammar Essentials',
        'WAEC Math Accelerator',
        'Science Revision Blueprint',
        'Application Success Planner',
    ];

    private const DEMO_EXAM_SLUGS = [
        'english-language-cbt-demo',
        'waec-complete-mock-cbt',
    ];

    private const DEMO_PURCHASE_REFERENCES = [
        'ATCS-SEEDED-PAID-EXAM',
        'ATCS-SEEDED-PAID-MATERIAL',
    ];

    public function run(): void
    {
        $this->removeSeededDemoContent();

        User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'ATCS Admin',
                'password' => 'ATCSAdmin2026!',
                'role' => User::ROLE_ADMIN,
            ]
        );

        if ($this->shouldSeedDemoStudent()) {
            User::query()->updateOrCreate(
                ['email' => self::DEMO_STUDENT_EMAIL],
                [
                    'name' => 'Demo Student',
                    'password' => bcrypt(self::DEMO_STUDENT_PASSWORD),
                    'role' => User::ROLE_STUDENT,
                ]
            );
        }
    }

    private function shouldSeedDemoStudent(): bool
    {
        return filter_var((string) env('SEED_DEMO_STUDENT', false), FILTER_VALIDATE_BOOLEAN);
    }

    private function removeSeededDemoContent(): void
    {
        Purchase::query()
            ->whereIn('provider_reference', self::DEMO_PURCHASE_REFERENCES)
            ->delete();

        $demoExamIds = Exam::query()
            ->whereIn('slug', self::DEMO_EXAM_SLUGS)
            ->pluck('id');

        if ($demoExamIds->isNotEmpty()) {
            Purchase::query()
                ->where('item_type', 'exam')
                ->whereIn('item_id', $demoExamIds)
                ->delete();

            ExamAttempt::query()
                ->whereIn('exam_id', $demoExamIds)
                ->delete();

            Exam::query()
                ->whereIn('id', $demoExamIds)
                ->delete();
        }

        $demoMaterialIds = Material::query()
            ->whereIn('title', self::DEMO_MATERIAL_TITLES)
            ->pluck('id');

        if ($demoMaterialIds->isNotEmpty()) {
            Purchase::query()
                ->where('item_type', 'material')
                ->whereIn('item_id', $demoMaterialIds)
                ->delete();

            Material::query()
                ->whereIn('id', $demoMaterialIds)
                ->delete();
        }

        $demoStudent = User::query()
            ->where('email', self::DEMO_STUDENT_EMAIL)
            ->first();

        if (! $demoStudent) {
            return;
        }

        PersonalAccessToken::query()
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $demoStudent->id)
            ->delete();

        Purchase::query()
            ->where('user_id', $demoStudent->id)
            ->delete();

        ExamAttempt::query()
            ->where('user_id', $demoStudent->id)
            ->delete();

        $demoStudent->delete();
    }
}
