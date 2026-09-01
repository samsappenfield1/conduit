<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class FieldsAndActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAccount(): Account
    {
        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        return Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);
    }

    public function test_fields_are_scoped_per_entity_type(): void
    {
        Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn']);

        $accountFields = Account::fields();
        $contactFields = Contact::fields();

        $this->assertCount(1, $accountFields);
        $this->assertSame('Industry', $accountFields->first()->name);

        $this->assertCount(1, $contactFields);
        $this->assertSame('LinkedIn', $contactFields->first()->name);
    }

    public function test_deleting_a_field_removes_its_values_across_all_records(): void
    {
        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $account->fieldValues()->create([
            'field_id' => $field->id,
            'value' => 'Manufacturing',
        ]);

        $this->assertDatabaseCount('field_values', 1);

        $field->delete();

        $this->assertDatabaseCount('field_values', 0);
    }

    public function test_uuid_is_never_exposed_as_a_field_and_cannot_be_overwritten_by_one(): void
    {
        $account = $this->makeAccount();
        $originalUuid = $account->uuid;

        // Even a maliciously/confusingly named field can't touch the real column,
        // because field values live in an entirely separate table.
        $field = Field::create(['entity_type' => 'account', 'name' => 'uuid']);

        $account->fieldValues()->create([
            'field_id' => $field->id,
            'value' => 'not-a-real-uuid',
        ]);

        $account->refresh();

        $this->assertSame($originalUuid, $account->uuid);
        $this->assertNotSame('not-a-real-uuid', $account->uuid);

        // The Field itself carries no notion of "uuid" as a system field;
        // it's just a row in the fields table, structurally inert to the model.
        $this->assertDatabaseHas('fields', [
            'id' => $field->id,
            'entity_type' => 'account',
            'name' => 'uuid',
        ]);
    }

    public function test_stage_change_is_logged_with_old_and_new_value(): void
    {
        $account = $this->makeAccount();

        $account->update(['current_stage' => 'activated']);

        $activity = Activity::forSubject($account)->where('description', 'updated')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('activated', $activity->attribute_changes->get('attributes')['current_stage']);
        $this->assertSame('signed_up', $activity->attribute_changes->get('old')['current_stage']);
    }

    public function test_domain_change_is_logged_with_old_and_new_value(): void
    {
        $account = $this->makeAccount();

        $account->update(['domain' => 'acme.example']);

        $activity = Activity::forSubject($account)->where('description', 'updated')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame('acme.example', $activity->attribute_changes->get('attributes')['domain']);
        $this->assertNull($activity->attribute_changes->get('old')['domain']);
    }

    public function test_owner_change_is_logged_with_old_and_new_value(): void
    {
        $account = $this->makeAccount();
        $owner = User::factory()->create();

        $account->update(['owner_id' => $owner->id]);

        $activity = Activity::forSubject($account)->where('description', 'updated')->latest()->first();

        $this->assertNotNull($activity);
        $this->assertSame($owner->id, $activity->attribute_changes->get('attributes')['owner_id']);
        $this->assertNull($activity->attribute_changes->get('old')['owner_id']);
    }

    public function test_field_value_change_is_logged_against_the_owning_account(): void
    {
        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $value = $account->fieldValues()->create([
            'field_id' => $field->id,
            'value' => 'Retail',
        ]);

        $value->update(['value' => 'Manufacturing']);

        $activities = Activity::forSubject($account)
            ->where('description', 'Industry updated')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $activities);

        $this->assertSame('Retail', $activities[0]->attribute_changes->get('attributes')['Industry']);
        $this->assertNull($activities[0]->attribute_changes->get('old')['Industry']);

        $this->assertSame('Manufacturing', $activities[1]->attribute_changes->get('attributes')['Industry']);
        $this->assertSame('Retail', $activities[1]->attribute_changes->get('old')['Industry']);
    }

    public function test_domain_saves_through_the_normal_account_edit_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = $this->makeAccount();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['domain' => 'acme.example'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('acme.example', $account->fresh()->domain);
    }

    public function test_field_values_save_through_the_normal_account_edit_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'fieldValues' => [
                    $field->id => ['Manufacturing'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'Manufacturing',
            $account->fieldValues()->where('field_id', $field->id)->value('value'),
        );
    }

    public function test_a_second_pill_replaces_the_first_instead_of_both_being_saved(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        // Simulates the raw tags-input state before the live afterStateUpdated
        // collapse has run: both the old and newly-typed pill are present.
        // Dehydration must still only persist the most recently added one.
        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'fieldValues' => [
                    $field->id => ['Retail', 'Manufacturing'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'Manufacturing',
            $account->fieldValues()->where('field_id', $field->id)->value('value'),
        );
    }

    public function test_clearing_a_field_value_saves_null_and_is_logged(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $account->fieldValues()->create([
            'field_id' => $field->id,
            'value' => 'Manufacturing',
        ]);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm([
                'fieldValues' => [
                    $field->id => [],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(
            $account->fieldValues()->where('field_id', $field->id)->value('value'),
        );

        $activity = Activity::forSubject($account)
            ->where('description', 'Industry updated')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->attribute_changes->get('attributes')['Industry']);
        $this->assertSame('Manufacturing', $activity->attribute_changes->get('old')['Industry']);
    }

    public function test_a_field_defined_for_contacts_does_not_appear_on_accounts(): void
    {
        Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn']);

        $this->assertCount(0, Account::fields());
    }
}
