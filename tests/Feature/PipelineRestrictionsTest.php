<?php

namespace Tests\Feature;

use App\Filament\Resources\Pipelines\Pages\EditPipeline;
use App\Filament\Resources\Pipelines\Pages\ListPipelines;
use App\Filament\Resources\Pipelines\PipelineResource;
use App\Models\Account;
use App\Models\Pipeline;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PipelineRestrictionsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSystemPipelines(): void
    {
        Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated', 'paying', 'churned'],
        ]);

        Pipeline::create([
            'name' => 'Enterprise',
            'type' => 'enterprise',
            'stages' => ['prospecting', 'qualified', 'negotiation', 'closed_won', 'churned'],
        ]);
    }

    public function test_creating_a_third_pipeline_is_blocked(): void
    {
        $this->makeSystemPipelines();

        $this->expectException(RuntimeException::class);

        Pipeline::create([
            'name' => 'Rogue Pipeline',
            'type' => 'self_serve',
            'stages' => ['a'],
        ]);
    }

    public function test_deleting_a_system_pipeline_is_blocked(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        $this->expectException(RuntimeException::class);

        $pipeline->delete();
    }

    public function test_stages_can_still_be_edited_on_a_system_pipeline(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['stages' => [
                ['stage' => 'signed_up'],
                ['stage' => 'activated'],
                ['stage' => 'paying'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['signed_up', 'activated', 'paying'], $pipeline->fresh()->stages);
    }

    public function test_stages_can_be_reordered_and_inserted_at_any_position(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'paying', 'churned'],
        ]);

        // Reversed order, plus a brand new stage inserted in the middle —
        // simulates what dragging items around and adding one produces.
        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['stages' => [
                ['stage' => 'churned'],
                ['stage' => 'renewing'],
                ['stage' => 'paying'],
                ['stage' => 'signed_up'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            ['churned', 'renewing', 'paying', 'signed_up'],
            $pipeline->fresh()->stages,
        );
    }

    public function test_the_move_up_and_move_down_buttons_reorder_stages_and_the_new_order_persists_on_save(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'paying', 'churned'],
        ]);

        $test = Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()]);

        $itemKeys = array_keys($test->instance()->getSchema('form')->getComponent('stages')->getItems());

        // Click "move up" on the middle item ("paying").
        $test->callAction(
            TestAction::make('moveUp')
                ->arguments(['item' => $itemKeys[1]])
                ->schemaComponent('stages'),
        );

        // Not saved yet — still just the in-memory form state.
        $this->assertSame(['signed_up', 'paying', 'churned'], $pipeline->fresh()->stages);

        $test->call('save')->assertHasNoFormErrors();

        // Persisted: "paying" moved ahead of "signed_up".
        $this->assertSame(['paying', 'signed_up', 'churned'], $pipeline->fresh()->stages);

        // Clicking "move down" on that same item and saving again reverts it.
        $test->callAction(
            TestAction::make('moveDown')
                ->arguments(['item' => $itemKeys[1]])
                ->schemaComponent('stages'),
        );
        $test->call('save')->assertHasNoFormErrors();

        $this->assertSame(['signed_up', 'paying', 'churned'], $pipeline->fresh()->stages);
    }

    public function test_reordering_stages_does_not_change_any_accounts_current_stage(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'paying', 'churned'],
        ]);

        $account = Account::create([
            'pipeline_id' => $pipeline->id,
            'name' => 'Test Account',
            'current_stage' => 'paying',
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['stages' => [
                ['stage' => 'churned'],
                ['stage' => 'paying'],
                ['stage' => 'signed_up'],
            ]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['churned', 'paying', 'signed_up'], $pipeline->fresh()->stages);
        $this->assertSame('paying', $account->fresh()->current_stage);
    }

    public function test_the_name_of_a_system_pipeline_can_be_changed_but_type_cannot(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated'],
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->fillForm(['name' => 'Renamed', 'type' => 'enterprise'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $pipeline->fresh()->name);
        $this->assertSame('self_serve', $pipeline->fresh()->type);
    }

    public function test_pipeline_resource_disallows_create_and_delete(): void
    {
        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        $this->assertFalse(PipelineResource::canCreate());
        $this->assertFalse(PipelineResource::canDelete($pipeline));
        $this->assertFalse(PipelineResource::canDeleteAny());
    }

    public function test_the_pipelines_create_route_no_longer_exists(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/pipelines/create')
            ->assertNotFound();
    }

    public function test_the_pipelines_list_page_has_no_create_action(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeSystemPipelines();

        Livewire::test(ListPipelines::class)
            ->assertActionDoesNotExist('create');
    }

    public function test_the_pipelines_list_page_has_no_pagination_controls(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeSystemPipelines();

        Livewire::test(ListPipelines::class)
            ->assertDontSee('Showing')
            ->assertDontSee('Per page');
    }

    public function test_the_pipelines_list_page_has_no_search_bar_or_column_manager(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeSystemPipelines();

        $html = Livewire::test(ListPipelines::class)->html();

        $this->assertStringNotContainsString('type="search"', $html);
        $this->assertStringNotContainsString('fi-ta-search', $html);
        $this->assertStringNotContainsString('column-manager', $html);
    }

    public function test_the_accounts_column_header_is_not_sortable(): void
    {
        $this->actingAs(User::factory()->create());
        $this->makeSystemPipelines();

        $html = Livewire::test(ListPipelines::class)->html();

        preg_match('/<th[^>]*fi-ta-header-cell-accounts-count[^>]*>.*?<\/th>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'Could not find the Accounts header cell.');
        $this->assertStringNotContainsString('wire:click', $matches[0]);
        $this->assertStringNotContainsString('<button', $matches[0]);
    }

    public function test_pipelines_always_display_self_serve_first_then_enterprise_regardless_of_order_or_account_counts(): void
    {
        $this->actingAs(User::factory()->create());

        // Reversed creation order, and Enterprise given more accounts than
        // Self serve — proves the display order isn't following id or the
        // accounts count.
        $enterprise = Pipeline::create([
            'name' => 'Enterprise',
            'type' => 'enterprise',
            'stages' => ['prospecting'],
        ]);
        $selfServe = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        foreach (range(1, 3) as $i) {
            Account::create(['pipeline_id' => $enterprise->id, 'name' => "Ent {$i}", 'current_stage' => 'prospecting']);
        }

        $html = Livewire::test(ListPipelines::class)->html();

        $this->assertTrue(
            strpos($html, 'Self serve') < strpos($html, 'Enterprise'),
            'Self serve should render before Enterprise.',
        );
    }

    public function test_the_pipeline_edit_page_has_no_delete_action(): void
    {
        $this->actingAs(User::factory()->create());

        $pipeline = Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up'],
        ]);

        Livewire::test(EditPipeline::class, ['record' => $pipeline->getKey()])
            ->assertActionDoesNotExist('delete');
    }
}
