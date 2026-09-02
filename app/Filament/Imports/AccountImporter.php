<?php

namespace App\Filament\Imports;

use App\Models\Account;
use App\Models\Pipeline;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                ->options(Pipeline::query()->where('is_active', true)->pluck('name', 'id'))
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
            Placeholder::make('duplicateNamesWarning')
                ->hiddenLabel()
                ->visible(fn (Get $get): bool => static::getDuplicateAccountNamesCount($get) > 0)
                ->content(function (Get $get): HtmlString {
                    $count = static::getDuplicateAccountNamesCount($get);

                    $message = Number::format($count).' of these account names already '
                        .($count === 1 ? 'exists' : 'exist')
                        .' — continuing will create duplicates.';

                    return new HtmlString(
                        '<div class="rounded-lg bg-warning-50 px-4 py-3 text-sm text-warning-700 ring-1 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/30">'
                        .e($message)
                        .'</div>'
                    );
                }),
        ];
    }

    /**
     * Simple exact-name (case/whitespace-insensitive) duplicate check against
     * the mapped "name" column of the uploaded CSV, run while the import
     * form is still open so the warning is seen before the person commits.
     * This is intentionally not fuzzy or domain-based matching.
     */
    protected static function getDuplicateAccountNamesCount(Get $get): int
    {
        $file = $get('file');
        $nameColumn = $get('columnMap.name');

        if (! $file instanceof TemporaryUploadedFile || blank($nameColumn)) {
            return 0;
        }

        $stream = $file->readStream();

        if (! $stream) {
            return 0;
        }

        $csvReader = CsvReader::from($stream);
        $csvReader->setHeaderOffset(0);

        $csvNames = collect((new Statement)->process($csvReader)->getRecords())
            ->pluck($nameColumn)
            ->filter(fn (mixed $name): bool => filled($name))
            ->map(fn (string $name): string => Str::lower(trim($name)))
            ->unique();

        if ($csvNames->isEmpty()) {
            return 0;
        }

        $existingNames = Account::query()
            ->pluck('name')
            ->map(fn (string $name): string => Str::lower(trim($name)));

        return $csvNames->intersect($existingNames)->count();
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
        $body = 'Your account import has completed and '.Number::format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
