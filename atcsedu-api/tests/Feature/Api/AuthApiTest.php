<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_register_and_fetch_profile(): void
    {
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Student',
            'email' => 'jane@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ]);

        $registerResponse
            ->assertCreated()
            ->assertJsonPath('data.user.role', 'student');

        $token = $registerResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'jane@example.com');
    }
}
