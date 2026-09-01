<?php

namespace App\Filament\Imports;

use App\Models\Account;
use App\Models\Pipeline;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Number;

class AccountImporter extends Importer
{
    protected static ?string $model = Account::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('pipeline_id')
                ->label('Pipeline')
                ->options(Pipeline::query()->pluck('name', 'id'))
                ->live()
                ->required(),
            Select::make('current_stage')
                ->label('Stage')
                ->options(function (Get $get): array {
                    $stages = Pipeline::find($get('pipeline_id'))?->stages ?? [];

                    return array_combine($stages, array_map(
                        fn (string $stage): string => str($stage)->replace('_', ' ')->headline(),
                        $stages,
                    ));
                })
                ->required(),
        ];
    }

    public function resolveRecord(): Account
    {
        return new Account([
            'pipeline_id' => $this->options['pipeline_id'],
            'current_stage' => $this->options['current_stage'],
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your account import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
