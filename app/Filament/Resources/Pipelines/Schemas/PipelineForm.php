<?php

namespace App\Filament\Resources\Pipelines\Schemas;

use App\Models\Pipeline;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                    ->required()
                    ->disabled()
                    ->helperText('System-defined pipelines cannot change type.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->live()
                    ->helperText(function (Get $get, ?Pipeline $record): ?string {
                        if ($get('is_active') || ! $record) {
                            return null;
                        }

                        $count = $record->accounts()->count();

                        if ($count === 0) {
                            return null;
                        }

                        return $count.' '.str('account')->plural($count).' on this pipeline — they won\'t be affected, but you won\'t be able to assign new accounts here.';
                    }),
                Repeater::make('stages')
                    ->required()
                    ->minItems(1)
                    ->simple(
                        TextInput::make('stage')
                            ->required()
                            ->maxLength(255),
                    )
                    ->addActionLabel('Add stage')
                    // The drag handle isn't a usable click target (it needs
                    // an actual drag gesture) and only confused things
                    // alongside the up/down buttons, so it's disabled here
                    // in favor of those buttons alone.
                    ->reorderableWithButtons()
                    ->reorderableWithDragAndDrop(false)
                    ->columnSpanFull(),
            ]);
    }
}
