<div class="fi-section rounded-2xl">
    <div class="fi-section-content-ctn">
        <div class="fi-section-content flex flex-col gap-y-5 p-6">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Activity</h3>

            @forelse ($this->activities as $activity)
                @php
                    $newValues = collect($activity->attribute_changes?->get('attributes', []));
                    $oldValues = collect($activity->attribute_changes?->get('old', []));
                    $changedKeys = $newValues->keys()->merge($oldValues->keys())->unique();

                    $displayValue = function (string $key, mixed $value) {
                        if (blank($value)) {
                            return $value;
                        }

                        return match ($key) {
                            'pipeline_id' => \App\Models\Pipeline::find($value)?->name ?? $value,
                            'account_id' => \App\Models\Account::find($value)?->name ?? $value,
                            default => $value,
                        };
                    };
                @endphp

                <div
                    class="border-b border-gray-950/5 pb-5 last:border-b-0 last:pb-0 dark:border-white/5"
                    wire:key="activity-{{ $activity->id }}"
                >
                    <div class="flex items-center justify-between gap-x-4">
                        <span class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ ucfirst($activity->description) }}
                        </span>

                        <span
                            class="shrink-0 text-xs text-gray-500 dark:text-gray-400"
                            title="{{ $activity->created_at->format('M j, Y g:i A') }}"
                        >
                            {{ $activity->created_at->diffForHumans() }}
                        </span>
                    </div>

                    @if ($changedKeys->isNotEmpty())
                        <ul class="mt-2 flex flex-col gap-y-1 text-sm">
                            @foreach ($changedKeys as $key)
                                <li class="text-gray-600 dark:text-gray-300">
                                    <span class="fi-fo-field-label-content">
                                        {{ str($key)->replace('_id', '')->replace('_', ' ')->headline() }}:
                                    </span>

                                    @if (filled($oldValues->get($key)))
                                        <span class="text-gray-400 line-through dark:text-gray-500">{{ $displayValue($key, $oldValues->get($key)) }}</span>
                                        <span aria-hidden="true">→</span>
                                    @endif

                                    <span>{{ $displayValue($key, $newValues->get($key)) ?? '—' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        {{ $activity->causer?->name ?? 'System' }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
