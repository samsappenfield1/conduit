<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Imports\AccountImporter;
use App\Filament\Resources\Accounts\AccountResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListAccounts extends ListRecords
{
    protected static string $resource = AccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(AccountImporter::class),
            CreateAction::make(),
        ];
    }
}
