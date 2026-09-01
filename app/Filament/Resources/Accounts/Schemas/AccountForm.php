<?php

namespace App\Filament\Resources\Accounts\Schemas;

use App\Filament\Support\FieldValueInput;
use App\Models\Account;
use App\Models\Pipeline;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pipeline_id')
                    ->relationship('pipeline', 'name')
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (Set $set) => $set('current_stage', null)),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('current_stage')
                    ->options(function (Get $get): array {
                        $stages = Pipeline::find($get('pipeline_id'))?->stages ?? [];

                        return array_combine($stages, array_map(
                            fn (string $stage): string => str($stage)->replace('_', ' ')->headline(),
                            $stages,
                        ));
                    })
                    ->required(),
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled()
                    ->dehydrated(false)
                    ->hiddenOn('create')
                    ->helperText('Generated automatically. Used by external systems to reference this account.'),
                Section::make('Fields')
                    ->hiddenOn('create')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(fn (): array => Account::fields()
                        ->map(fn ($field) => FieldValueInput::make($field))
                        ->all()),
            ]);
    }
}
