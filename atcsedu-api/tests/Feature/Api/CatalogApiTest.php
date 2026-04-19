<?php

namespace Tests\Feature\Api;

use App\Models\Exam;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_authenticated_student_can_see_download_url_for_a_paid_material_they_purchased(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $material = Material::query()->create([
            'title' => 'Premium Biology Pack',
            'category' => 'Science',
            'access_type' => 'paid',
            'price' => 3500,
            'format' => 'PDF Guide',
            'description' => 'Premium revision pack',
            'download_url' => '/materials/premium-biology-pack.pdf',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Purchase::query()->create([
            'user_id' => $student->id,
            'item_type' => 'material',
            'item_id' => $material->id,
            'amount' => 3500,
            'status' => 'paid',
            'provider_reference' => 'TEST-PURCHASE-REF-001',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($student);

        $this->getJson("/api/v1/materials/{$material->id}")
            ->assertOk()
            ->assertJsonPath('data.material.download_url', '/materials/premium-biology-pack.pdf');
    }

    public function test_authenticated_student_cannot_download_a_paid_material_even_after_purchase(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $student = User::factory()->create([
            'role' => User::ROLE_STUDENT,
        ]);

        $material = Material::query()->create([
            'title' => 'Premium Chemistry Pack',
            'category' => 'Science',
            'access_type' => 'paid',
            'price' => 4000,
            'format' => 'PDF Guide',
            'description' => 'Premium chemistry revision pack',
            'download_url' => '/materials/premium-chemistry-pack.pdf',
            'is_published' => true,
            'created_by' => $admin->id,
        ]);

        Purchase::query()->create([
            'user_id' => $student->id,
            'item_type' => 'material',
            'item_id' => $material->id,
            'amount' => 4000,
            'status' => 'paid',
            'provider_reference' => 'TEST-PURCHASE-REF-002',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($student);

        $this->getJson("/api/v1/materials/{$material->id}/download")
            ->assertForbidden()
            ->assertJsonPath('message', 'Paid materials are view-only and cannot be downloaded.');
    }
}
