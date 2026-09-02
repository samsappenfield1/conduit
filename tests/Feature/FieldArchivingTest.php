<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Fields\Pages\EditField;
use App\Filament\Resources\Fields\Pages\ListFields;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Field;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FieldArchivingTest extends TestCase
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

    public function test_archiving_a_field_soft_deletes_it(): void
    {
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $field->delete();

        $this->assertTrue($field->fresh()->trashed());
        $this->assertDatabaseHas('fields', ['id' => $field->id]);
    }

    public function test_an_archived_field_is_excluded_from_the_account_fields_list(): void
    {
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $field->delete();

        $this->assertFalse(Account::fields()->contains('id', $field->id));
    }

    public function test_an_archived_field_no_longer_appears_on_the_account_edit_form(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        // Archive it before ever setting a value, so there's no "Industry
        // updated" activity-log entry to confuse the assertion below — that
        // history is expected to stay visible even after archiving; only
        // the live form field itself should disappear.
        $field->delete();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertDontSee('Industry');
    }

    public function test_an_archived_field_no_longer_appears_on_the_contact_edit_form(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);
        $field = Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn']);
        $field->delete();

        Livewire::test(EditContact::class, ['record' => $contact->getKey()])
            ->assertDontSee('LinkedIn');
    }

    public function test_an_archived_field_is_excluded_from_the_contact_fields_list(): void
    {
        $field = Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn']);
        $field->delete();

        $this->assertFalse(Contact::fields()->contains('id', $field->id));
    }

    public function test_an_archived_fields_past_activity_log_entries_remain_visible(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'Manufacturing']);

        $field->delete();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertSee('Industry updated');
    }

    public function test_an_archived_field_is_excluded_from_the_default_fields_list(): void
    {
        $this->actingAs(User::factory()->create());

        $active = Field::create(['entity_type' => 'account', 'name' => 'ARR']);
        $archived = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $archived->delete();

        Livewire::test(ListFields::class)
            ->assertSee('ARR')
            ->assertDontSee('Industry');
    }

    public function test_the_trashed_filter_can_show_archived_fields(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $field->delete();

        Livewire::test(ListFields::class)
            ->filterTable('trashed', true)
            ->assertSee('Industry');
    }

    public function test_restore_action_is_available_only_for_an_archived_field(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->assertActionVisible('delete')
            ->assertActionHidden('restore');

        $field->delete();

        Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->assertActionHidden('delete')
            ->assertActionVisible('restore');
    }

    public function test_restoring_a_field_makes_it_active_again(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $field->delete();

        Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->callAction('restore');

        $this->assertFalse($field->fresh()->trashed());
        $this->assertTrue(Account::fields()->contains('id', $field->id));
    }

    public function test_the_archive_confirmation_states_how_many_records_have_a_value(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $accountOne = $this->makeAccount();
        $accountOne->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'Retail']);

        $accountTwo = $this->makeAccount();
        $accountTwo->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'Manufacturing']);

        $test = Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->mountAction('delete');

        $this->assertSame(
            '2 accounts have a value set for this field. Archiving it won\'t change or remove that data — you can restore it at any point.',
            (string) $test->instance()->getMountedAction()->getModalDescription(),
        );

        // Mounting (opening the modal) must not have archived it yet.
        $this->assertFalse($field->fresh()->trashed());
    }

    public function test_the_archive_confirmation_shows_zero_when_no_records_have_a_value(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $test = Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->mountAction('delete');

        $this->assertSame(
            '0 accounts have a value set for this field. Archiving it won\'t change or remove that data — you can restore it at any point.',
            (string) $test->instance()->getMountedAction()->getModalDescription(),
        );
    }

    public function test_archiving_does_not_require_clearing_values_first(): void
    {
        $this->actingAs(User::factory()->create());

        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);
        $account = $this->makeAccount();
        $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'Retail']);

        Livewire::test(EditField::class, ['record' => $field->getKey()])
            ->callAction('delete');

        $this->assertTrue($field->fresh()->trashed());
        $this->assertSame(
            'Retail',
            $account->fieldValues()->where('field_id', $field->id)->value('value'),
        );
    }

    public function test_typed_value_still_resolves_correctly_for_a_value_tied_to_an_archived_field(): void
    {
        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'ARR', 'type' => 'number']);

        $fieldValue = $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 50000]);

        $field->delete();

        $this->assertSame(50000.0, $fieldValue->fresh()->typed_value);
    }
}
