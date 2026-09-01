<?php

namespace App\Filament\Resources\Pipelines\Pages;

use App\Filament\Resources\Pipelines\PipelineResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePipeline extends CreateRecord
{
    protected static string $resource = PipelineResource::class;

    public function canCreateAnother(): bool
    {
        return false;
    }
}
