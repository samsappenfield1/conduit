<?php

namespace App\Models\Concerns;

use App\Models\Field;
use App\Models\FieldValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFields
{
    public static function fieldEntityType(): string
    {
        return strtolower(class_basename(static::class));
    }

    public function fieldValues(): MorphMany
    {
        return $this->morphMany(FieldValue::class, 'customizable');
    }

    public static function fields(): Collection
    {
        return Field::where('entity_type', static::fieldEntityType())
            ->orderBy('id')
            ->get();
    }
}
