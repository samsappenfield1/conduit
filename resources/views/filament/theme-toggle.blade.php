@php
    use Filament\Support\Icons\Heroicon;
@endphp

<button
    type="button"
    x-data
    x-on:click="window.dispatchEvent(new CustomEvent('theme-changed', { detail: ($store.theme === 'dark') ? 'light' : 'dark' }))"
    x-bind:aria-pressed="$store.theme === 'dark' ? 'true' : 'false'"
    aria-label="{{ __('filament-panels::layout.actions.theme_switcher.label') }}"
    class="fi-icon-btn conduit-theme-toggle"
>
    <span x-show="$store.theme !== 'dark'" x-cloak>
        {{ \Filament\Support\generate_icon_html(Heroicon::Sun) }}
    </span>

    <span x-show="$store.theme === 'dark'" x-cloak>
        {{ \Filament\Support\generate_icon_html(Heroicon::Moon) }}
    </span>
</button>
