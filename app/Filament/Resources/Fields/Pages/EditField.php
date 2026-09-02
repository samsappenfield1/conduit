<?php

namespace App\Filament\Resources\Fields\Pages;

use App\Filament\Resources\Fields\FieldResource;
use App\Models\Field;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditField extends EditRecord
{
    protected static string $resource = FieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Archive')
                ->modalHeading('Archive field')
                ->modalDescription(fn (Field $record): string => $record->archiveWarning())
                ->modalSubmitActionLabel('Archive'),
            RestoreAction::make(),
        ];
    }
}
