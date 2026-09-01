<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Field;
use App\Models\Pipeline;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AccountWebhookTest extends TestCase
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

    protected function fakeWebhookUrl(): string
    {
        $url = 'https://webhook.example/account-changes';
        Setting::set(Setting::ACCOUNT_WEBHOOK_URL, $url);

        return $url;
    }

    public function test_no_webhook_is_sent_when_no_url_is_configured(): void
    {
        Http::fake();

        $account = $this->makeAccount();
        $account->update(['current_stage' => 'activated']);

        Http::assertNothingSent();
    }

    public function test_webhook_fires_on_stage_change_with_old_new_and_full_account_state(): void
    {
        $url = $this->fakeWebhookUrl();
        Http::fake();

        $account = $this->makeAccount();
        $account->update(['current_stage' => 'activated']);

        Http::assertSent(function ($request) use ($url, $account) {
            return $request->url() === $url
                && $request['field'] === 'current_stage'
                && $request['old'] === 'signed_up'
                && $request['new'] === 'activated'
                && $request['account']['uuid'] === $account->uuid
                && $request['account']['current_stage'] === 'activated';
        });
    }

    public function test_webhook_fires_on_owner_change(): void
    {
        $url = $this->fakeWebhookUrl();
        Http::fake();

        $account = $this->makeAccount();
        $owner = User::factory()->create();
        $account->update(['owner_id' => $owner->id]);

        Http::assertSent(fn ($request) => $request['field'] === 'owner_id'
            && $request['new'] === $owner->id
            && $request['account']['owner']['id'] === $owner->id);
    }

    public function test_webhook_fires_on_domain_change(): void
    {
        $this->fakeWebhookUrl();
        Http::fake();

        $account = $this->makeAccount();
        $account->update(['domain' => 'acme.example']);

        Http::assertSent(fn ($request) => $request['field'] === 'domain' && $request['new'] === 'acme.example');
    }

    public function test_webhook_fires_on_field_value_change(): void
    {
        $this->fakeWebhookUrl();
        Http::fake();

        $account = $this->makeAccount();
        $field = Field::create(['entity_type' => 'account', 'name' => 'ARR', 'type' => 'number']);

        $account->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 1000]);

        Http::assertSent(fn ($request) => $request['field'] === 'ARR'
            && $request['old'] === null
            && $request['new'] === 1000.0);
    }

    public function test_webhook_does_not_fire_for_contact_field_value_changes(): void
    {
        $this->fakeWebhookUrl();
        Http::fake();

        $account = $this->makeAccount();
        $contact = $account->contacts()->create(['name' => 'Jamie', 'email' => 'jamie@example.com']);
        $field = Field::create(['entity_type' => 'contact', 'name' => 'LinkedIn', 'type' => 'text']);

        $contact->fieldValues()->create(['field_id' => $field->id, 'typed_value' => 'linkedin.com/jamie']);

        Http::assertNothingSent();
    }

    public function test_a_failed_webhook_response_does_not_break_the_save_and_is_logged(): void
    {
        $this->fakeWebhookUrl();
        Http::fake(fn () => Http::response('error', 500));
        Log::spy();

        $account = $this->makeAccount();
        $account->update(['current_stage' => 'activated']);

        $this->assertSame('activated', $account->fresh()->current_stage);

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_webhook_connection_exception_does_not_break_the_save_and_is_logged(): void
    {
        $this->fakeWebhookUrl();
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });
        Log::spy();

        $account = $this->makeAccount();
        $account->update(['current_stage' => 'activated']);

        $this->assertSame('activated', $account->fresh()->current_stage);

        Log::shouldHaveReceived('warning')->once();
    }
}
