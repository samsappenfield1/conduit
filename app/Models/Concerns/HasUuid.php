<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model): void {
            $model->uuid = (string) Str::uuid();
        });

        static::saving(function ($model): void {
            if ($model->exists && $model->isDirty('uuid')) {
                $model->uuid = $model->getOriginal('uuid');
            }
        });
    }
}
