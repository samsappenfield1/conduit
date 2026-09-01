<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Livewire\Component;

class ApiTokensTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Auth::user()->tokens()->getQuery())
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('last_used_at')
                    ->label('Last used')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                $this->generateTokenAction(),
            ])
            ->recordActions([
                Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon(Heroicon::OutlinedTrash)
                    ->requiresConfirmation()
                    ->modalDescription('This token will stop working immediately.')
                    ->action(fn (PersonalAccessToken $record) => $record->delete())
                    ->successNotificationTitle('Token revoked'),
            ])
            ->emptyStateHeading('No API tokens')
            ->emptyStateDescription('Generate one to start authenticating requests to the API.');
    }

    protected function generateTokenAction(): Action
    {
        return Action::make('generateToken')
            ->label('Generate token')
            ->icon(Heroicon::OutlinedPlus)
            ->schema([
                TextInput::make('name')
                    ->label('Token name')
                    ->required()
                    ->maxLength(255)
                    ->default('api'),
            ])
            ->action(function (array $data): void {
                $token = Auth::user()->createToken($data['name']);

                $this->replaceMountedAction('revealToken', ['plainTextToken' => $token->plainTextToken]);
            });
    }

    public function revealTokenAction(): Action
    {
        return Action::make('revealToken')
            ->label('New API token')
            ->modalHeading('New API token')
            ->modalDescription('Copy this token now. For your security, it will not be shown again.')
            ->schema(fn (array $arguments) => [
                TextInput::make('token')
                    ->label('Token')
                    ->default($arguments['plainTextToken'] ?? null)
                    ->disabled()
                    ->copyable(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done')
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false);
    }

    public function render(): View
    {
        return view('livewire.api-tokens-table');
    }
}
