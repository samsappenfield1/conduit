<?php

namespace App\Models;

use App\Models\Concerns\HasFields;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Contact extends Model
{
    use HasFields;
    use HasUuid;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'account_id',
        'name',
        'email',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'account_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
