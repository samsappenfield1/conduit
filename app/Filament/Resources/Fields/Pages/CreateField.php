<?php

namespace App\Filament\Resources\Fields\Pages;

use App\Filament\Resources\Fields\FieldResource;
use Filament\Resources\Pages\CreateRecord;

class CreateField extends CreateRecord
{
    protected static string $resource = FieldResource::class;

    public function canCreateAnother(): bool
    {
        return false;
    }
}
