<?php

namespace App\Filament\Resources\Pipelines\Tables;

use App\Models\Pipeline;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PipelinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Locked to exactly two system-defined pipelines — pagination,
            // search, and column customization are all meaningless here and
            // never apply to any of the other resource tables. No column is
            // searchable() or toggleable() below, which removes the search
            // bar and column-manager button entirely rather than just
            // hiding them.
            ->paginated(false)
            // Fixed display order — self serve on top, enterprise on bottom
            // — regardless of id/creation order or any column's values.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->orderByRaw("CASE WHEN type = 'self_serve' THEN 0 ELSE 1 END"))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' '))
                    ->color(fn (string $state): string => $state === 'self_serve' ? 'success' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('stages')
                    ->label('Stages')
                    ->getStateUsing(fn (Pipeline $record): string => implode(' → ', $record->stages))
                    ->wrap(),
                TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->counts('accounts'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
