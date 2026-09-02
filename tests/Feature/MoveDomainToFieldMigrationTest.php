<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Field;
use App\Models\Pipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MoveDomainToFieldMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function migration(): object
    {
        return include database_path('migrations/2026_09_02_014720_move_domain_from_accounts_to_field.php');
    }

    protected function makeAccount(?string $domain = null): Account
    {
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

        if ($domain !== null) {
            DB::table('accounts')->where('id', $account->id)->update(['domain' => $domain]);
        }

        return $account;
    }

    public function test_the_domain_field_exists_and_applies_to_accounts_only(): void
    {
        $field = Field::where('entity_type', 'account')->where('name', 'Domain')->first();

        $this->assertNotNull($field);
        $this->assertSame('text', $field->type);
    }

    public function test_the_accounts_table_no_longer_has_a_domain_column(): void
    {
        $this->assertFalse(Schema::hasColumn('accounts', 'domain'));
    }

    public function test_migrating_up_moves_an_existing_domain_column_value_into_the_field(): void
    {
        $migration = $this->migration();
        $migration->down();

        $account = $this->makeAccount(domain: 'acme.example');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('accounts', 'domain') === false);

        $field = Field::where('entity_type', 'account')->where('name', 'Domain')->first();
        $value = $account->fieldValues()->where('field_id', $field->id)->first();

        $this->assertNotNull($value);
        $this->assertSame('acme.example', $value->typed_value);
    }

    public function test_migrating_up_does_not_create_a_field_value_for_accounts_with_no_domain(): void
    {
        $migration = $this->migration();
        $migration->down();

        $account = $this->makeAccount(domain: null);

        $migration->up();

        $field = Field::where('entity_type', 'account')->where('name', 'Domain')->first();

        $this->assertNull($account->fieldValues()->where('field_id', $field->id)->first());
    }

    public function test_migrating_up_is_idempotent_when_the_domain_field_already_exists(): void
    {
        $migration = $this->migration();
        $migration->down();

        $account = $this->makeAccount(domain: 'acme.example');

        $migration->up();
        $migration->down();
        $migration->up();

        $fields = Field::where('entity_type', 'account')->where('name', 'Domain')->get();
        $this->assertCount(1, $fields);

        $this->assertSame(
            'acme.example',
            $account->fieldValues()->where('field_id', $fields->first()->id)->value('value'),
        );
    }

    public function test_migrating_down_restores_the_domain_column_value(): void
    {
        $account = $this->makeAccount();
        $field = Field::where('entity_type', 'account')->where('name', 'Domain')->first();
        $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'acme.example']);

        $migration = $this->migration();
        $migration->down();

        $this->assertSame('acme.example', DB::table('accounts')->where('id', $account->id)->value('domain'));

        // Restore schema to what every other test in the suite expects.
        $migration->up();
    }
}
