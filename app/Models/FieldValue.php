<?php

namespace App\Models;

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
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class);
    }

    public function customizable(): MorphTo
    {
        return $this->morphTo();
    }
}
