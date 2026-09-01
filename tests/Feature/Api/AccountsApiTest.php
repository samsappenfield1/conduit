<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\Field;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function makePipeline(string $name = 'Self serve'): Pipeline
    {
        return Pipeline::create([
            'name' => $name,
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/accounts')->assertUnauthorized();
    }

    public function test_index_returns_accounts_with_pipeline_owner_fields_and_contacts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $pipeline = $this->makePipeline();
        $owner = User::factory()->create(['name' => 'Jamie Owner']);
        $field = Field::create(['entity_type' => 'account', 'name' => 'ARR', 'type' => 'number']);

        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'owner_id' => $owner->id,
            'name' => 'Acme Widgets',
            'domain' => 'acme.example',
            'current_stage' => 'signed_up',
        ]);
        $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 500000]);
        $account->contacts()->create(['name' => 'Taylor Rivera', 'email' => 'taylor@acme.example']);

        $response = $this->getJson('/api/accounts')->assertOk();

        $data = $response->json('data.0');

        $this->assertSame('Acme Widgets', $data['name']);
        $this->assertSame('acme.example', $data['domain']);
        $this->assertSame('signed_up', $data['current_stage']);
        $this->assertSame($pipeline->id, $data['pipeline']['id']);
        $this->assertSame('Jamie Owner', $data['owner']['name']);
        $this->assertSame('Taylor Rivera', $data['contacts'][0]['name']);

        $arrField = collect($data['fields'])->firstWhere('name', 'ARR');
        $this->assertSame('number', $arrField['type']);
        $this->assertEquals(500000, $arrField['value']);
    }

    public function test_index_can_be_filtered_by_pipeline_id_and_current_stage(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $pipelineA = $this->makePipeline('Pipeline A');
        $pipelineB = $this->makePipeline('Pipeline B');

        $matching = Account::create([
            'pipeline_id' => $pipelineA->id,
            'name' => 'Matching Account',
            'current_stage' => 'activated',
        ]);
        Account::create([
            'pipeline_id' => $pipelineA->id,
            'name' => 'Wrong Stage',
            'current_stage' => 'signed_up',
        ]);
        Account::create([
            'pipeline_id' => $pipelineB->id,
            'name' => 'Wrong Pipeline',
            'current_stage' => 'activated',
        ]);

        $response = $this->getJson("/api/accounts?pipeline_id={$pipelineA->id}&current_stage=activated")
            ->assertOk();

        $names = collect($response->json('data'))->pluck('name')->all();

        $this->assertSame([$matching->name], $names);
    }

    public function test_index_excludes_archived_accounts(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $pipeline = $this->makePipeline();

        Account::create(['pipeline_id' => $pipeline->id, 'name' => 'Active', 'current_stage' => 'signed_up']);

        $archived = Account::create(['pipeline_id' => $pipeline->id, 'name' => 'Archived', 'current_stage' => 'signed_up']);
        $archived->delete();

        $names = collect($this->getJson('/api/accounts')->json('data'))->pluck('name')->all();

        $this->assertSame(['Active'], $names);
    }

    public function test_show_returns_a_single_account_by_uuid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $pipeline = $this->makePipeline();
        $account = Account::create(['pipeline_id' => $pipeline->id, 'name' => 'Acme', 'current_stage' => 'signed_up']);

        $this->getJson("/api/accounts/{$account->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $account->uuid)
            ->assertJsonPath('data.name', 'Acme');
    }

    public function test_show_returns_404_for_an_unknown_uuid(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/accounts/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    public function test_show_returns_404_for_an_archived_account(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $pipeline = $this->makePipeline();
        $account = Account::create(['pipeline_id' => $pipeline->id, 'name' => 'Acme', 'current_stage' => 'signed_up']);
        $account->delete();

        $this->getJson("/api/accounts/{$account->uuid}")->assertNotFound();
    }

    public function test_activity_endpoint_returns_the_accounts_change_history(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $this->actingAs($user);

        $pipeline = $this->makePipeline();
        $account = Account::create(['pipeline_id' => $pipeline->id, 'name' => 'Acme', 'current_stage' => 'signed_up']);
        $account->update(['current_stage' => 'activated']);

        $response = $this->getJson("/api/accounts/{$account->uuid}/activity")->assertOk();

        $entry = collect($response->json('data'))->firstWhere('event', 'updated');

        $this->assertNotNull($entry);
        $this->assertSame('activated', $entry['changes']['attributes']['current_stage']);
        $this->assertSame('signed_up', $entry['changes']['old']['current_stage']);
        $this->assertArrayHasKey('created_at', $entry);
    }
}
