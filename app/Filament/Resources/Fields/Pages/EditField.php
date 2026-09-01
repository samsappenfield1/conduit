<?php

namespace App\Filament\Resources\Fields\Pages;

use App\Filament\Resources\Fields\FieldResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditField extends EditRecord
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
