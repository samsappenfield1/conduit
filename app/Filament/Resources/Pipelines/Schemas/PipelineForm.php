<?php

namespace App\Filament\Resources\Pipelines\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PipelineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'self_serve' => 'self serve',
                        'enterprise' => 'enterprise',
                    ])
                    ->default('self_serve')
                    ->required(),
                TagsInput::make('stages')
                    ->required()
                    ->placeholder('Add a stage and press enter')
                    ->columnSpanFull(),
            ]);
    }
}
