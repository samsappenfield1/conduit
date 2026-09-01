<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_usable_token_for_a_user(): void
    {
        $user = User::factory()->create(['email' => 'jamie@example.com']);

        $this->artisan('app:generate-api-token', ['email' => 'jamie@example.com'])
            ->assertSuccessful();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_it_fails_for_an_unknown_email(): void
    {
        $this->artisan('app:generate-api-token', ['email' => 'nobody@example.com'])
            ->assertFailed();
    }
}
