<?php

namespace Tests\Feature;

use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Accounts\RelationManagers\ContactsRelationManager;
use App\Models\Account;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountArchivingTest extends TestCase
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

    public function test_archiving_an_account_soft_deletes_it_and_its_contacts(): void
    {
        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);

        $account->delete();

        $this->assertTrue($account->fresh()->trashed());
        $this->assertTrue($contact->fresh()->trashed());

        // Still physically present, not hard-deleted.
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_restoring_an_account_restores_its_contacts(): void
    {
        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);

        $account->delete();
        $account->fresh()->restore();

        $this->assertFalse($account->fresh()->trashed());
        $this->assertFalse($contact->fresh()->trashed());
    }

    public function test_archived_accounts_are_excluded_from_the_default_list_view(): void
    {
        $this->actingAs(User::factory()->create());

        $active = $this->makeAccount();
        $active->update(['name' => 'Active Account']);

        $archived = $this->makeAccount();
        $archived->update(['name' => 'Archived Account']);
        $archived->delete();

        Livewire::test(ListAccounts::class)
            ->assertSee('Active Account')
            ->assertDontSee('Archived Account');
    }

    public function test_the_trashed_filter_can_show_archived_accounts(): void
    {
        $this->actingAs(User::factory()->create());

        $archived = $this->makeAccount();
        $archived->update(['name' => 'Archived Account']);
        $archived->delete();

        Livewire::test(ListAccounts::class)
            ->filterTable('trashed', true)
            ->assertSee('Archived Account');

        Livewire::test(ListAccounts::class)
            ->filterTable('trashed', false)
            ->assertSee('Archived Account');
    }

    public function test_the_trashed_filter_uses_archive_terminology(): void
    {
        $this->actingAs(User::factory()->create());

        $filter = Livewire::test(ListAccounts::class)
            ->instance()
            ->getTable()
            ->getFilter('trashed');

        $this->assertSame('Archived accounts', $filter->getLabel());
        $this->assertSame('Active only', $filter->getPlaceholder());
        $this->assertSame('All (active + archived)', $filter->getTrueLabel());
        $this->assertSame('Archived only', $filter->getFalseLabel());
    }

    public function test_an_archived_account_is_still_viewable_and_editable_with_its_activity_log(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $account->update(['current_stage' => 'activated']);
        $account->delete();

        $this->get("/admin/accounts/{$account->id}/edit")->assertOk();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertSee('Test Account')
            ->fillForm(['name' => 'Renamed While Archived'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed While Archived', $account->fresh()->name);
    }

    public function test_restore_action_is_available_only_for_an_archived_account(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertActionVisible('delete')
            ->assertActionHidden('restore');

        $account->delete();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertActionHidden('delete')
            ->assertActionVisible('restore');
    }

    public function test_restoring_from_the_edit_page_restores_contacts_too(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);
        $account->delete();

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->callAction('restore');

        $this->assertFalse($account->fresh()->trashed());
        $this->assertFalse($contact->fresh()->trashed());
    }

    public function test_the_archive_confirmation_message_is_shown_before_the_action_completes(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();

        $test = Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->mountAction('delete');

        $this->assertSame(
            Account::ARCHIVE_WARNING,
            (string) $test->instance()->getMountedAction()->getModalDescription(),
        );

        // Mounting (opening the modal) must not have archived it yet.
        $this->assertFalse($account->fresh()->trashed());
    }

    public function test_contacts_of_an_archived_account_are_visible_in_the_relation_manager(): void
    {
        $this->actingAs(User::factory()->create());

        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie Rivera', 'email' => 'jamie@example.com']);
        $account->delete();

        Livewire::test(ContactsRelationManager::class, [
            'ownerRecord' => $account->fresh(),
            'pageClass' => EditAccount::class,
        ])->assertSee($contact->name);
    }
}
