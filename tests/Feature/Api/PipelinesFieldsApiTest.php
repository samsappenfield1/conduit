<?php

namespace Tests\Feature\Api;

use App\Models\Field;
use App\Models\Pipeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelinesFieldsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipelines_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/pipelines')->assertUnauthorized();
    }

    public function test_pipelines_endpoint_returns_pipelines_with_stages(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Pipeline::create([
            'name' => 'Self serve',
            'type' => 'self_serve',
            'stages' => ['signed_up', 'activated', 'paying'],
        ]);

        $response = $this->getJson('/api/pipelines')->assertOk();

        $pipeline = $response->json('data.0');

        $this->assertSame('Self serve', $pipeline['name']);
        $this->assertSame('self_serve', $pipeline['type']);
        $this->assertSame(['signed_up', 'activated', 'paying'], $pipeline['stages']);
    }

    public function test_fields_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/fields')->assertUnauthorized();
    }

    public function test_fields_endpoint_returns_field_definitions(): void
    {
        Sanctum::actingAs(User::factory()->create());

        Field::create(['entity_type' => 'account', 'name' => 'ARR', 'type' => 'number']);
        Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn', 'type' => 'text']);

        $response = $this->getJson('/api/fields')->assertOk();

        $fields = collect($response->json('data'));

        $arr = $fields->firstWhere('name', 'ARR');
        $this->assertSame('number', $arr['type']);
        $this->assertSame('account', $arr['applies_to']);

        $linkedin = $fields->firstWhere('name', 'LinkedIn');
        $this->assertSame('contact', $linkedin['applies_to']);
    }
}
