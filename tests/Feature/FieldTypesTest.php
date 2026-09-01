<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Fields\Pages\CreateField;
use App\Models\Account;
use App\Models\Field;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class FieldTypesTest extends TestCase
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

    public function test_a_field_created_without_a_type_defaults_to_text(): void
    {
        $field = Field::create(['entity_type' => 'account', 'name' => 'Industry']);

        $this->assertSame('text', $field->fresh()->type);
    }

    public function test_a_field_can_be_created_with_a_type(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateField::class)
            ->fillForm([
                'entity_type' => 'account',
                'name' => 'Headcount',
                'type' => 'number',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('fields', [
            'name' => 'Headcount',
            'type' => 'number',
        ]);
    }

    public function test_number_field_value_is_stored_as_a_real_number_not_a_string(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Headcount', 'type' => 'number']);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['fieldValues' => [$field->id => '42']])
            ->call('save')
            ->assertHasNoFormErrors();

        $fieldValue = $account->fieldValues()->where('field_id', $field->id)->first();

        $this->assertNull($fieldValue->value);
        $this->assertSame(42.0, $fieldValue->value_number);
        $this->assertIsFloat($fieldValue->typed_value);
    }

    public function test_number_field_rejects_non_numeric_input(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Headcount', 'type' => 'number']);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['fieldValues' => [$field->id => 'not-a-number']])
            ->call('save')
            ->assertHasFormErrors(["fieldValues.{$field->id}" => 'numeric']);

        $this->assertDatabaseMissing('field_values', ['field_id' => $field->id]);
    }

    public function test_boolean_field_value_is_stored_as_a_real_boolean_not_a_string(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Is VIP', 'type' => 'boolean']);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['fieldValues' => [$field->id => true]])
            ->call('save')
            ->assertHasNoFormErrors();

        $fieldValue = $account->fieldValues()->where('field_id', $field->id)->first();

        $this->assertNull($fieldValue->value);
        $this->assertTrue($fieldValue->value_boolean);
        $this->assertIsBool($fieldValue->typed_value);
    }

    public function test_date_field_value_is_stored_and_reloaded_into_the_form(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Renewal Date', 'type' => 'date']);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['fieldValues' => [$field->id => '2026-12-01']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            '2026-12-01',
            $account->fieldValues()->where('field_id', $field->id)->value('value'),
        );

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertFormSet(["fieldValues.{$field->id}" => '2026-12-01']);
    }

    public function test_boolean_field_change_is_logged_as_a_real_boolean_not_a_string(): void
    {
        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'Is VIP', 'type' => 'boolean']);

        $value = $account->fieldValues()->create([
            'field_id' => $field->id,
            'typed_value' => false,
        ]);

        $value->update(['typed_value' => true]);

        $activity = Activity::forSubject($account)
            ->where('description', 'Is VIP updated')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertTrue($activity->attribute_changes->get('attributes')['Is VIP']);
        $this->assertFalse($activity->attribute_changes->get('old')['Is VIP']);
    }
}
