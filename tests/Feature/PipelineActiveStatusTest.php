<?php

namespace Tests\Feature;

use App\Filament\Imports\AccountImporter;
use App\Filament\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Resources\Accounts\Pages\EditAccount;
use App\Filament\Resources\Pipelines\Pages\EditPipeline;
use App\Filament\Resources\Pipelines\Pages\ListPipelines;
use App\Models\Account;
use App\Models\Pipeline;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PipelineActiveStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function makePipeline(string $name = 'Self serve', bool $active = true): Pipeline
    {
        return Pipeline::create([
            'name' => $name,
            'type' => 'self_serve',
            'stages' => ['signed_up', 'paying', 'churned'],
            'is_active' => $active,
        ]);
    }

    public function test_new_pipelines_default_to_active(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        $this->assertTrue($pipeline->is_active);
    }

    public function test_inactive_pipelines_are_excluded_from_the_account_create_pipeline_selector(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makePipeline('Self serve', active: true);
        $this->makePipeline('Enterprise', active: false);

        Livewire::test(CreateAccount::class)
            ->assertSee('Self serve')
            ->assertDontSee('Enterprise');
    }

    public function test_an_account_already_on_a_pipeline_that_later_goes_inactive_still_shows_it_on_edit(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = $this->makePipeline('Self serve', active: true);
        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        $pipeline->update(['is_active' => false]);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->assertSee('Self serve');
    }

    public function test_an_existing_account_on_an_inactive_pipeline_can_still_be_saved(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = $this->makePipeline('Self serve', active: false);
        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        Livewire::test(EditAccount::class, ['record' => $account->getKey()])
            ->fillForm(['name' => 'Renamed Account'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed Account', $account->fresh()->name);
    }

    public function test_webhook_still_fires_for_an_account_on_an_inactive_pipeline(): void
    {
        Setting::set(Setting::ACCOUNT_WEBHOOK_URL, 'https://webhook.example/account-changes');
        Http::fake();

        $pipeline = $this->makePipeline('Self serve', active: false);
        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        $account->update(['current_stage' => 'paying']);

        Http::assertSent(fn ($request) => $request['field'] === 'current_stage' && $request['new'] === 'paying');
    }

    public function test_inactive_pipelines_are_excluded_from_the_csv_import_pipeline_picker(): void
    {
        $active = $this->makePipeline('Self serve', active: true);
        $inactive = $this->makePipeline('Enterprise', active: false);

        $pipelineSelect = collect(AccountImporter::getOptionsFormComponents())
            ->first(fn ($component) => $component->getName() === 'pipeline_id');

        $options = $pipelineSelect->getOptions();

        $this->assertArrayHasKey($active->id, $options);
        $this->assertArrayNotHasKey($inactive->id, $options);
    }

    public function test_inactive_pipelines_still_appear_in_the_pipelines_list(): void
    {
        $this->actingAs(User::factory()->create());

        $this->makePipeline('Self serve', active: true);
        $this->makePipeline('Enterprise', active: false);

        Livewire::test(ListPipelines::class)
            ->assertSee('Self serve')
            ->assertSee('Enterprise');
    }

    public function test_deactivating_a_pipeline_with_accounts_shows_a_warning(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = $this->makePipeline('Self serve', active: true);
        Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['is_active' => false])
            ->assertSee('1 account');
    }

    public function test_deactivating_a_pipeline_with_no_accounts_shows_no_warning(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = $this->makePipeline('Self serve', active: true);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['is_active' => false])
            ->assertDontSee('this pipeline');
    }

    public function test_deactivating_a_pipeline_with_accounts_is_not_blocked(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = $this->makePipeline('Self serve', active: true);
        Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'signed_up',
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($pipeline->fresh()->is_active);
    }
}
