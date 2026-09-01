<?php

namespace App\Filament\Imports;

use App\Models\Account;
use App\Models\Contact;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Filament\Forms\Components\Select;
use Illuminate\Support\Number;

class ContactImporter extends Importer
{
    protected static ?string $model = Contact::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('email')
                ->requiredMapping()
                ->rules(['required', 'email']),
        ];
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            Select::make('account_id')
                ->label('Account')
                ->options(Account::query()->pluck('name', 'id'))
                ->required(),
        ];
    }

    public function resolveRecord(): Contact
    {
        $email = $this->data['email'] ?? '';

        return new Contact([
            'account_id' => $this->options['account_id'],
            'name' => str($email)->before('@')->replace(['.', '_', '-'], ' ')->title(),
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your contact import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
