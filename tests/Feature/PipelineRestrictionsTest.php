<?php

namespace Tests\Feature;

use App\Filament\Resources\Pipelines\Pages\EditPipeline;
use App\Filament\Resources\Pipelines\Pages\ListPipelines;
use App\Filament\Resources\Pipelines\PipelineResource;
use App\Models\Pipeline;
use App\Models\User;
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
            ->fillForm(['stages' => ['signed_up', 'activated', 'paying']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['signed_up', 'activated', 'paying'], $pipeline->fresh()->stages);
    }

    public function test_the_name_and_type_of_a_system_pipeline_cannot_be_changed_through_the_edit_form(): void
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

        $this->assertSame('Self serve', $pipeline->fresh()->name);
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
