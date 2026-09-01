<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Filament\Support\FieldValueInput;
use App\Models\Contact;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('account_id')
                    ->relationship('account', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled()
                    ->dehydrated(false)
                    ->hiddenOn('create')
                    ->helperText('Generated automatically. Used by external systems to reference this contact.'),
                Section::make('Fields')
                    ->hiddenOn('create')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema(fn (): array => Contact::fields()
                        ->map(fn ($field) => FieldValueInput::make($field))
                        ->all()),
            ]);
    }
}
