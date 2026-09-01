<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FieldValue extends Model
{
    protected $fillable = [
        'field_id',
        'customizable_type',
        'customizable_id',
        'value',
        'value_number',
        'value_boolean',
        'typed_value',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'float',
            'value_boolean' => 'boolean',
        ];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function customizable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * A single logical value backed by whichever physical column matches the
     * owning Field's type, so numbers and booleans are stored natively
     * instead of as text equivalents. Setting it requires field_id to
     * already be assigned, since that determines the target column.
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: fn (): float|bool|string|null => match ($this->field?->type) {
                'number' => $this->value_number,
                'boolean' => $this->value_boolean,
                default => $this->value,
            },
            set: fn (mixed $value): array => match ($this->field?->type) {
                'number' => [
                    'value' => null,
                    'value_number' => $value === null || $value === '' ? null : (float) $value,
                    'value_boolean' => null,
                ],
                'boolean' => [
                    'value' => null,
                    'value_number' => null,
                    'value_boolean' => $value === null ? null : (bool) $value,
                ],
                default => [
                    'value' => $value === null || $value === '' ? null : (string) $value,
                    'value_number' => null,
                    'value_boolean' => null,
                ],
            },
        );
    }

    public function originalTypedValue(): float|bool|string|null
    {
        return match ($this->field?->type) {
            'number' => $this->getOriginal('value_number'),
            'boolean' => $this->getOriginal('value_boolean'),
            default => $this->getOriginal('value'),
        };
    }
}
