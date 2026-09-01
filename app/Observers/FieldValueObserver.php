<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\FieldValue;
use App\Services\AccountWebhookNotifier;

class FieldValueObserver
{
    public function created(FieldValue $value): void
    {
        $this->log($value, null, $value->typed_value);
    }

    public function updated(FieldValue $value): void
    {
        if (! $value->wasChanged(['value', 'value_number', 'value_boolean'])) {
            return;
        }

        $this->log($value, $value->originalTypedValue(), $value->typed_value);
    }

    protected function log(FieldValue $value, float|bool|string|null $old, float|bool|string|null $new): void
    {
        $label = $value->field?->name ?? 'Field';

        activity()
            ->performedOn($value->customizable)
            ->withChanges([
                'attributes' => [$label => $new],
                'old' => [$label => $old],
            ])
            ->event('updated')
            ->log("{$label} updated");

        if ($value->customizable instanceof Account) {
            AccountWebhookNotifier::notify($value->customizable, $label, $old, $new);
        }
    }
}
