<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use SoftDeletes;

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

    /**
     * Confirmation copy for archiving, naming how many records currently
     * hold a value for this field — informational only, since archiving
     * never touches that data.
     */
    public function archiveWarning(): string
    {
        $count = $this->values()->count();
        $noun = str($this->entity_type)->plural($count);
        $verb = $count === 1 ? 'has' : 'have';

        return "{$count} {$noun} {$verb} a value set for this field. Archiving it won't change or remove that data — you can restore it at any point.";
    }
}
