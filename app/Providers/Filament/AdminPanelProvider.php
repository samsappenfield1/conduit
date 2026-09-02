<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Pages\Login;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->brandName('Conduit')
            // Inlined (not a plain asset URL) so the wordmark's
            // `fill="currentColor"` can pick up the `.fi-logo` text color
            // set in theme.css and adapt to light/dark mode — an <img src>
            // can't be reached by page CSS the same way.
            ->brandLogo(fn (): HtmlString => new HtmlString(file_get_contents(public_path('images/conduit-logo-final-v.svg'))))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('images/conduit-icon-final-v.svg'))
            ->login(Login::class)
            // Needed so completed background work (e.g. a CSV import that
            // finishes after the page's initial "processing" toast) has a
            // visible place to report success/failure — CSV imports queue
            // their completion notice as a database notification once
            // QUEUE_CONNECTION isn't "sync".
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Zinc,
            ])
            ->themeSwitcher(false)
            ->userMenuItems([
                'profile' => fn (Action $action) => $action->hidden(),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('filament.theme-toggle'),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
