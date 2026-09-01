<?php

namespace App\Filament\Resources\Fields\Pages;

use App\Filament\Resources\Fields\FieldResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFields extends ListRecords
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
