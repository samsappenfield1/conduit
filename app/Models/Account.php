<?php

namespace App\Models;

use App\Models\Concerns\HasFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Account extends Model
{
    use HasFields;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    const ARCHIVE_WARNING = 'Archiving this account will also archive its associated contacts. You can restore both at any point.';

    /**
     * Attributes tracked by the activity log and eligible to fire the
     * account webhook when they change.
     *
     * @var array<int, string>
     */
    const TRACKED_ATTRIBUTES = ['name', 'current_stage', 'pipeline_id', 'owner_id'];

    protected $fillable = [
        'pipeline_id',
        'owner_id',
        'name',
        'current_stage',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(self::TRACKED_ATTRIBUTES)
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
