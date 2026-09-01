<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Models\Account;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['fieldValues', 'owner']))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('domain')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pipeline.name')
                    ->label('Pipeline')
                    ->searchable(),
                TextColumn::make('current_stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => str($state)->replace('_', ' ')->headline())
                    ->searchable(),
                TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->counts('contacts')
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ...static::getFieldColumns(),
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
                    ->label('Archived accounts')
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
                        ->modalHeading('Archive accounts')
                        ->modalDescription(Account::ARCHIVE_WARNING)
                        ->modalSubmitActionLabel('Archive'),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<TextColumn>
     */
    protected static function getFieldColumns(): array
    {
        return Account::fields()
            ->map(fn ($field) => TextColumn::make("field_{$field->id}")
                ->label($field->name)
                ->getStateUsing(fn (Model $record) => $record->fieldValues->firstWhere('field_id', $field->id)?->typed_value)
                ->formatStateUsing(fn ($state): string => $field->type === 'boolean' ? ($state ? 'Yes' : 'No') : (string) $state)
                ->toggleable(isToggledHiddenByDefault: true))
            ->all();
    }
}
