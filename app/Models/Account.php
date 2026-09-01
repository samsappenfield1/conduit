<?php

namespace App\Models;

use App\Models\Concerns\HasFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Account extends Model
{
    use HasFields;
    use HasUuid;
    use LogsActivity;

    protected $fillable = [
        'pipeline_id',
        'name',
        'current_stage',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'current_stage', 'pipeline_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
