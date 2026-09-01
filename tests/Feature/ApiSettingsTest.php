<?php

namespace Tests\Feature;

use App\Filament\Pages\ApiSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_loads(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/api-settings')
            ->assertOk();
    }

    public function test_the_webhook_url_field_is_prefilled_with_the_current_value(): void
    {
        $this->actingAs(User::factory()->create());

        Setting::set(Setting::ACCOUNT_WEBHOOK_URL, 'https://example.com/hook');

        Livewire::test(ApiSettings::class)
            ->assertFormSet(['webhook_url' => 'https://example.com/hook']);
    }

    public function test_the_webhook_url_can_be_saved(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ApiSettings::class)
            ->fillForm(['webhook_url' => 'https://example.com/hook'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('https://example.com/hook', Setting::get(Setting::ACCOUNT_WEBHOOK_URL));
    }

    public function test_the_webhook_url_must_be_a_valid_url(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ApiSettings::class)
            ->fillForm(['webhook_url' => 'not-a-url'])
            ->call('save')
            ->assertHasFormErrors(['webhook_url' => 'url']);
    }

    public function test_clearing_the_webhook_url_unsets_it(): void
    {
        $this->actingAs(User::factory()->create());

        Setting::set(Setting::ACCOUNT_WEBHOOK_URL, 'https://example.com/hook');

        Livewire::test(ApiSettings::class)
            ->fillForm(['webhook_url' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(Setting::get(Setting::ACCOUNT_WEBHOOK_URL));
    }
}
