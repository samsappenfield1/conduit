<?php

namespace App\Observers;

use App\Models\Account;
use App\Services\AccountWebhookNotifier;
use Illuminate\Support\Facades\Log;

class AccountObserver
{
    /**
     * Handle the Account "updated" event.
     *
     * Placeholder for the external automation tool: stage transitions will
     * eventually be exposed to it via an API or event, not handled here.
     */
    public function updated(Account $account): void
    {
        if ($account->wasChanged('current_stage')) {
            Log::info('Account stage changed', [
                'account_id' => $account->id,
                'pipeline_id' => $account->pipeline_id,
                'from_stage' => $account->getOriginal('current_stage'),
                'to_stage' => $account->current_stage,
            ]);
        }

        foreach (Account::TRACKED_ATTRIBUTES as $attribute) {
            if ($account->wasChanged($attribute)) {
                AccountWebhookNotifier::notify($account, $attribute, $account->getOriginal($attribute), $account->{$attribute});
            }
        }
    }

    /**
     * Archiving an Account archives its contacts alongside it, so they
     * disappear from view together and can be restored together.
     */
    public function deleted(Account $account): void
    {
        $account->contacts()->get()->each->delete();
    }

    public function restored(Account $account): void
    {
        $account->contacts()->onlyTrashed()->get()->each->restore();
    }
}
