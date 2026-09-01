<?php

namespace App\Observers;

use App\Models\Account;
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
        if (! $account->wasChanged('current_stage')) {
            return;
        }

        Log::info('Account stage changed', [
            'account_id' => $account->id,
            'pipeline_id' => $account->pipeline_id,
            'from_stage' => $account->getOriginal('current_stage'),
            'to_stage' => $account->current_stage,
        ]);
    }
}
