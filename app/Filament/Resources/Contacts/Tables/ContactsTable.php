<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('fieldValues'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('account.name')
                    ->label('Account')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
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
        return Contact::fields()
            ->map(fn ($field) => TextColumn::make("field_{$field->id}")
                ->label($field->name)
                ->getStateUsing(fn (Model $record) => $record->fieldValues->firstWhere('field_id', $field->id)?->typed_value)
                ->formatStateUsing(fn ($state): string => $field->type === 'boolean' ? ($state ? 'Yes' : 'No') : (string) $state)
                ->toggleable(isToggledHiddenByDefault: true))
            ->all();
    }
}
