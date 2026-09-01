<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_pages_render(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/pipelines')->assertOk();
        $this->actingAs($user)->get('/admin/accounts')->assertOk();
        $this->actingAs($user)->get('/admin/accounts/create')->assertOk();
        $this->actingAs($user)->get('/admin/contacts')->assertOk();
        $this->actingAs($user)->get('/admin/contacts/create')->assertOk();
        $this->actingAs($user)->get('/admin/fields')->assertOk();
        $this->actingAs($user)->get('/admin/fields/create')->assertOk();
    }

    public function test_admin_root_redirects_to_accounts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertRedirect('/admin/accounts');
    }

    public function test_account_edit_page_renders_contacts_relation_manager(): void
    {
        $user = User::factory()->create();

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        $account->contacts()->create([
            'name' => 'Test Contact',
            'email' => 'test.contact@example.com',
        ]);

        $this->actingAs($user)
            ->get("/admin/accounts/{$account->id}/edit")
            ->assertOk();
    }
}
