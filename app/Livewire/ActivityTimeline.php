<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

/**
 * @property-read Collection<int, Activity> $activities
 */
class ActivityTimeline extends Component
{
    public Model $record;

    public function getActivitiesProperty(): Collection
    {
        return Activity::forSubject($this->record)
            ->with('causer')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.activity-timeline');
    }
}
