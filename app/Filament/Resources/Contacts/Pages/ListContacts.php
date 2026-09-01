<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Imports\ContactImporter;
use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->importer(ContactImporter::class)
                ->modalDescription(null),
            CreateAction::make(),
        ];
    }
}
