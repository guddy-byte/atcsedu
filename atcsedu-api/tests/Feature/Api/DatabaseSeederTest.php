<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_keeps_only_the_admin_seeded_by_default(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'admin@atcsedu.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'student.demo@atcsedu.com',
            'role' => User::ROLE_STUDENT,
        ]);
    }
}
