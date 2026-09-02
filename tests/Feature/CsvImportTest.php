<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The column-mapping Select's options for a given field, after a real
     * file upload has been simulated on the mounted import action — this
     * goes through Filament's actual schema resolution (Get, visibility,
     * the Fieldset's schema() closure), not a shortcut.
     *
     * @return array<string, string>
     */
    protected function columnMapOptionsFor(mixed $testable, string $fieldName): array
    {
        $instance = $testable->instance();

        $method = new ReflectionMethod($instance, 'getMountedActionSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($instance);

        foreach ($schema->getComponents() as $component) {
            if (! str_contains(get_class($component), 'Fieldset')) {
                continue;
            }

            foreach ($component->getChildSchema()?->getComponents() ?? [] as $child) {
                if ($child->getName() === $fieldName) {
                    return $child->getOptions();
                }
            }
        }

        return [];
    }

    /**
     * A top-level (non-Fieldset-nested) component from the mounted import
     * action's real, resolved schema — e.g. the duplicate-names warning
     * Placeholder, which lives alongside "file" and the options fields.
     */
    protected function mountedActionSchemaComponent(mixed $testable, string $fieldName): ?object
    {
        $instance = $testable->instance();

        $method = new ReflectionMethod($instance, 'getMountedActionSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($instance);

        foreach ($schema->getComponents(withHidden: true) as $component) {
            if (method_exists($component, 'getName') && $component->getName() === $fieldName) {
                return $component;
            }
        }

        return null;
    }

    public function test_accounts_can_be_imported_from_csv_with_pipeline_and_stage_picked_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "Account Name\nAcme Import Co\nSecond Import Co\n",
        );

        Livewire::test(ListAccounts::class)
            ->callAction('import', data: [
                'file' => $csv,
                'pipeline_id' => $pipeline->id,
                'current_stage' => 'signed_up',
                'columnMap' => [
                    'name' => 'Account Name',
                ],
            ]);

        $accounts = Account::whereIn('name', ['Acme Import Co', 'Second Import Co'])->get();

        $this->assertCount(2, $accounts);

        foreach ($accounts as $account) {
            $this->assertSame($pipeline->id, $account->pipeline_id);
            $this->assertSame('signed_up', $account->current_stage);
            $this->assertNotNull($account->uuid);
        }
    }

    public function test_contacts_can_be_imported_from_csv_with_account_picked_once(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Acme Widgets',
            'current_stage' => 'signed_up',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'contacts.csv',
            "Email\njamie.rivera@acmewidgets.example\ntaylor@acmewidgets.example\n",
        );

        Livewire::test(ListContacts::class)
            ->callAction('import', data: [
                'file' => $csv,
                'account_id' => $account->id,
                'columnMap' => [
                    'email' => 'Email',
                ],
            ]);

        $contacts = Contact::whereIn('email', ['jamie.rivera@acmewidgets.example', 'taylor@acmewidgets.example'])->get();

        $this->assertCount(2, $contacts);

        $jamie = $contacts->firstWhere('email', 'jamie.rivera@acmewidgets.example');
        $this->assertSame($account->id, $jamie->account_id);
        $this->assertSame('Jamie Rivera', $jamie->name);
        $this->assertNotNull($jamie->uuid);
    }

    public function test_the_import_accounts_modal_has_no_download_example_csv_link(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListAccounts::class)
            ->mountAction('import')
            ->assertDontSee('Download example CSV file');
    }

    public function test_the_import_contacts_modal_has_no_download_example_csv_link(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListContacts::class)
            ->mountAction('import')
            ->assertDontSee('Download example CSV file');
    }

    public function test_uploading_an_accounts_csv_populates_the_column_mapping_dropdown_with_its_real_headers(): void
    {
        $this->actingAs(User::factory()->create());

        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "account name,pipeline,pipeline stage\nAcme Widgets,Self serve,signed_up\n",
        );

        $test = Livewire::test(ListAccounts::class)
            ->mountAction('import')
            ->set('mountedActions.0.data.file', $csv);

        $options = $this->columnMapOptionsFor($test, 'name');

        $this->assertSame([
            'account name' => 'account name',
            'pipeline' => 'pipeline',
            'pipeline stage' => 'pipeline stage',
        ], $options);
    }

    public function test_a_warning_is_shown_when_uploaded_account_names_already_exist(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Acme Widgets',
            'current_stage' => 'signed_up',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "name\nAcme Widgets\nBrand New Co\n",
        );

        $test = Livewire::test(ListAccounts::class)
            ->mountAction('import')
            ->set('mountedActions.0.data.file', $csv);

        $warning = $this->mountedActionSchemaComponent($test, 'duplicateNamesWarning');

        $this->assertNotNull($warning);
        $this->assertTrue($warning->isVisible());
        $this->assertStringContainsString(
            '1 of these account names already exists',
            $warning->getContent()->toHtml(),
        );
    }

    public function test_no_warning_is_shown_when_uploaded_account_names_are_all_new(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Acme Widgets',
            'current_stage' => 'signed_up',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "name\nBrand New Co\nAnother New Co\n",
        );

        $test = Livewire::test(ListAccounts::class)
            ->mountAction('import')
            ->set('mountedActions.0.data.file', $csv);

        $warning = $this->mountedActionSchemaComponent($test, 'duplicateNamesWarning');

        $this->assertNotNull($warning);
        $this->assertFalse($warning->isVisible());
    }

    public function test_duplicate_named_accounts_can_still_be_imported_after_the_warning(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pipeline = Pipeline::create([
            'name' => 'Test Pipeline',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Acme Widgets',
            'current_stage' => 'signed_up',
        ]);

        $csv = UploadedFile::fake()->createWithContent(
            'accounts.csv',
            "name\nAcme Widgets\n",
        );

        Livewire::test(ListAccounts::class)
            ->callAction('import', data: [
                'file' => $csv,
                'pipeline_id' => $pipeline->id,
                'current_stage' => 'signed_up',
                'columnMap' => [
                    'name' => 'name',
                ],
            ]);

        $this->assertSame(2, Account::where('name', 'Acme Widgets')->count());
    }

    public function test_uploading_a_contacts_csv_populates_the_column_mapping_dropdown_with_its_real_headers(): void
    {
        $this->actingAs(User::factory()->create());

        $csv = UploadedFile::fake()->createWithContent(
            'contacts.csv',
            "full name,email address,job title\nJamie Rivera,jamie@example.com,Manager\n",
        );

        $test = Livewire::test(ListContacts::class)
            ->mountAction('import')
            ->set('mountedActions.0.data.file', $csv);

        $options = $this->columnMapOptionsFor($test, 'email');

        $this->assertSame([
            'full name' => 'full name',
            'email address' => 'email address',
            'job title' => 'job title',
        ], $options);
    }

    public function test_the_admin_panel_has_database_notifications_enabled(): void
    {
        // Filament's import ImportAction only shows an immediate toast when
        // QUEUE_CONNECTION is "sync"; otherwise (as in this app, which uses
        // the "database" queue driver) the completion message is delivered
        // as a database notification, which requires this to be enabled for
        // the bell icon that displays it to even render.
        $this->assertTrue(Filament::getPanel('admin')->hasDatabaseNotifications());
    }

    public function test_an_import_completion_notification_can_be_delivered_to_the_database(): void
    {
        // Regression test: this failed with "no such table: notifications"
        // until the standard Laravel notifications table migration existed,
        // which meant an import that finished after the page's initial
        // "processing" toast (i.e. any non-"sync" queue connection) never
        // surfaced a success or failure message to the user.
        $user = User::factory()->create();

        Notification::make()
            ->title('Import completed')
            ->body('Your account import has completed and 2 rows imported.')
            ->success()
            ->sendToDatabase($user);

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => $user->getMorphClass(),
        ]);
    }
}
