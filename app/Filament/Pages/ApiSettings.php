<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ApiSettings extends Page
{
    protected string $view = 'filament.pages.api-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'API';

    protected static ?int $navigationSort = 6;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'webhook_url' => Setting::get(Setting::ACCOUNT_WEBHOOK_URL),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('webhook_url')
                    ->label('Webhook URL')
                    ->url()
                    ->maxLength(255)
                    ->helperText('Called on any tracked Account field change (stage, owner, domain, or Field values).'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::set(Setting::ACCOUNT_WEBHOOK_URL, $data['webhook_url'] ?: null);

        Notification::make()
            ->success()
            ->title('Saved')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->action('save'),
        ];
    }
}
