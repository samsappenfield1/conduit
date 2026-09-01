<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Webhook
        </x-slot>

        {{ $this->form }}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            API tokens
        </x-slot>

        @livewire(\App\Livewire\ApiTokensTable::class)
    </x-filament::section>
</x-filament-panels::page>
