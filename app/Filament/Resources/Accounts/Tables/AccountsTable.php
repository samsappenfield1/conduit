<?php

namespace App\Filament\Resources\Accounts\Tables;

use App\Models\Account;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('fieldValues'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
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

    /**
     * @return array<TextColumn>
     */
    protected static function getFieldColumns(): array
    {
        return Account::fields()
            ->map(fn ($field) => TextColumn::make("field_{$field->id}")
                ->label($field->name)
                ->getStateUsing(fn (Model $record) => $record->fieldValues->firstWhere('field_id', $field->id)?->value)
                ->toggleable(isToggledHiddenByDefault: true))
            ->all();
    }
}
