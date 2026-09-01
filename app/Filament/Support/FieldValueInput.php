<?php

namespace App\Filament\Support;

use App\Models\Field;
use App\Models\FieldValue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field as FormField;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;

class FieldValueInput
{
    public static function make(Field $field): FormField
    {
        return match ($field->type) {
            'number' => TextInput::make("fieldValues.{$field->id}")
                ->label($field->name)
                ->numeric(),
            'date' => DatePicker::make("fieldValues.{$field->id}")
                ->label($field->name),
            'boolean' => Toggle::make("fieldValues.{$field->id}")
                ->label($field->name),
            default => static::text($field),
        };
    }

    /**
     * Shape a stored FieldValue for the form's initial state: TagsInput
     * (Text fields) needs its value wrapped in a single-item array, every
     * other input takes the typed scalar directly.
     */
    public static function hydrate(FieldValue $fieldValue): mixed
    {
        $value = $fieldValue->typed_value;

        return match ($fieldValue->field?->type) {
            'number', 'date', 'boolean' => $value,
            default => filled($value) ? [$value] : [],
        };
    }

    /**
     * A tags-style input constrained to a single pill: typing and pressing
     * enter replaces whatever pill is already there instead of adding a
     * second one, since a Field can only ever hold one value.
     */
    protected static function text(Field $field): TagsInput
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
