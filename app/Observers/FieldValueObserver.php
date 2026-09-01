<?php

namespace App\Observers;

use App\Models\FieldValue;

class FieldValueObserver
{
    public function created(FieldValue $value): void
    {
        $this->log($value, null, $value->value);
    }

    public function updated(FieldValue $value): void
    {
        if (! $value->wasChanged('value')) {
            return;
        }

        $this->log($value, $value->getOriginal('value'), $value->value);
    }

    protected function log(FieldValue $value, ?string $old, ?string $new): void
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
    }
}
