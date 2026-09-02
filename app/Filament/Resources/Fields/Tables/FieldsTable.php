<?php

namespace App\Filament\Resources\Fields\Tables;

use App\Models\Field;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class FieldsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Applies to')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'account' ? 'success' : 'gray'),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'number' => 'info',
                        'date' => 'warning',
                        'boolean' => 'success',
                        default => 'gray',
                    }),
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
                TrashedFilter::make()
                    ->label('Archived fields')
                    ->placeholder('Active only')
                    ->trueLabel('All (active + archived)')
                    ->falseLabel('Archived only'),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Archive selected')
                        ->modalHeading('Archive fields')
                        ->modalDescription(function (Collection $records): string {
                            $count = $records->sum(fn (Field $field): int => $field->values()->count());
                            $noun = str('record')->plural($count);
                            $verb = $count === 1 ? 'has' : 'have';
                            $fieldWord = $records->count() === 1 ? 'this field' : 'these fields';

                            return "{$count} {$noun} {$verb} a value set for {$fieldWord}. Archiving won't change or remove that data. You can restore them at any point.";
                        })
                        ->modalSubmitActionLabel('Archive'),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
