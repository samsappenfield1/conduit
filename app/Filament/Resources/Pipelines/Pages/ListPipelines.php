<?php

namespace App\Filament\Resources\Pipelines\Pages;

use App\Filament\Resources\Pipelines\PipelineResource;
use Filament\Resources\Pages\ListRecords;

class ListPipelines extends ListRecords
{
    protected static string $resource = PipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
