<?php

namespace App\Services;

use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountWebhookNotifier
{
    /**
     * POST a tracked field change to the account webhook configured in
     * Settings. Never lets a webhook failure interrupt the caller's save.
     */
    public static function notify(Account $account, string $field, mixed $old, mixed $new): void
    {
        $url = Setting::get(Setting::ACCOUNT_WEBHOOK_URL);

        if (blank($url)) {
            return;
        }

        $payload = [
            'field' => $field,
            'old' => $old,
            'new' => $new,
            'account' => (new AccountResource(
                $account->loadMissing(['pipeline', 'owner', 'contacts', 'fieldValues.field'])
            ))->resolve(),
        ];

        try {
            $response = Http::timeout(5)->post($url, $payload);

            if ($response->failed()) {
                Log::warning('Account webhook responded with a failure status.', [
                    'account_id' => $account->id,
                    'field' => $field,
                    'url' => $url,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Account webhook request failed.', [
                'account_id' => $account->id,
                'field' => $field,
                'url' => $url,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
