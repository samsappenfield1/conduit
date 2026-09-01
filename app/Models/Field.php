<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    protected $fillable = [
        'entity_type',
        'name',
        'type',
    ];

    protected $attributes = [
        'type' => 'text',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(FieldValue::class);
    }
}
