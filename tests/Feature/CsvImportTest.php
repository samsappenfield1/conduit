<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CsvImportTest extends TestCase
{
    use RefreshDatabase;

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
}
