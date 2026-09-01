<?php

namespace App\Filament\Resources\Fields\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entity_type')
                    ->label('Applies to')
                    ->options([
                        'account' => 'Account',
                        'contact' => 'Contact',
                    ])
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'number' => 'Number',
                        'date' => 'Date',
                        'boolean' => 'Boolean',
                    ])
                    ->default('text')
                    ->required(),
            ]);
    }
}
