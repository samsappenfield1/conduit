<?php

namespace App\Filament\Resources\Pipelines\Tables;

use App\Models\Pipeline;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PipelinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' '))
                    ->color(fn (string $state): string => $state === 'self_serve' ? 'success' : 'gray'),
                TextColumn::make('stages')
                    ->label('Stages')
                    ->getStateUsing(fn (Pipeline $record): string => implode(' → ', $record->stages))
                    ->wrap(),
                TextColumn::make('accounts_count')
                    ->label('Accounts')
                    ->counts('accounts')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
