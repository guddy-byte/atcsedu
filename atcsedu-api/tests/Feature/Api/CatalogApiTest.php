<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_catalog_endpoints_return_materials_and_exams(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        Material::query()->create([
            'title' => 'Starter Pack',
            'category' => 'Languages',
            'access_type' => 'free',
            'price' => 0,
            'format' => 'PDF Guide',
            'description' => 'Free starter pack',
            'download_url' => '/materials/starter-pack.pdf',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Exam::query()->create([
            'title' => 'Mock Test',
            'slug' => 'mock-test',
            'category' => 'Core subject',
            'access_type' => 'free',
            'price' => 0,
            'duration_minutes' => 45,
            'attempt_limit' => 0,
            'description' => 'Mock exam',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        $this->getJson('/api/v1/materials')
            ->assertOk()
            ->assertJsonPath('data.items.0.title', 'Starter Pack');

        $this->getJson('/api/v1/exams')
            ->assertOk()
            ->assertJsonPath('data.items.0.slug', 'mock-test');
    }
}
