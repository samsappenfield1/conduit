<?php

namespace Tests\Feature;

use App\Livewire\ApiTokensTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokensTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_the_current_users_tokens(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $user->createToken('ci');

        Livewire::test(ApiTokensTable::class)->assertSee('ci');
    }

    public function test_it_does_not_list_another_users_tokens(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $otherUser->createToken('someone-elses-token');

        Livewire::test(ApiTokensTable::class)->assertDontSee('someone-elses-token');
    }

    public function test_generating_a_token_creates_it_for_the_current_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(ApiTokensTable::class)
            ->callAction('generateToken', data: ['name' => 'ci']);

        $this->assertSame(1, $user->tokens()->where('name', 'ci')->count());
    }

    public function test_generating_a_token_reveals_a_working_plain_text_value_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = Livewire::test(ApiTokensTable::class)
            ->callAction('generateToken', data: ['name' => 'ci']);

        $response->assertActionMounted('revealToken');

        $mountedRevealAction = collect($response->instance()->mountedActions)
            ->firstWhere('name', 'revealToken');

        $plainTextToken = $mountedRevealAction['arguments']['plainTextToken'] ?? null;

        $this->assertNotEmpty($plainTextToken);

        $this->getJson('/api/pipelines', ['Authorization' => "Bearer {$plainTextToken}"])
            ->assertOk();
    }

    public function test_revoking_a_token_deletes_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $token = $user->createToken('ci');
        $tokenId = $token->accessToken->id;

        Livewire::test(ApiTokensTable::class)
            ->callTableAction('revoke', $tokenId);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_a_user_cannot_revoke_another_users_token_through_the_table(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        $token = $otherUser->createToken('someone-elses-token');
        $tokenId = $token->accessToken->id;

        Livewire::test(ApiTokensTable::class)
            ->assertTableActionDoesNotExist('revoke', record: $tokenId);

        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenId]);
    }
}
