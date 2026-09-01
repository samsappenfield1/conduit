<?php

namespace App\Filament\Support;

use App\Models\Field;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Utilities\Set;

class FieldValueInput
{
    /**
     * A tags-style input constrained to a single pill: typing and pressing
     * enter replaces whatever pill is already there instead of adding a
     * second one, since a Field can only ever hold one value.
     */
    public static function make(Field $field): TagsInput
    {
        return TagsInput::make("fieldValues.{$field->id}")
            ->label($field->name)
            ->placeholder('Type a value and press enter')
            ->live()
            ->afterStateUpdated(function (Set $set, ?array $state) use ($field): void {
                $set("fieldValues.{$field->id}", $state ? [end($state)] : []);
            })
            ->dehydrateStateUsing(fn (?array $state): ?string => $state ? end($state) : null);
    }
}
