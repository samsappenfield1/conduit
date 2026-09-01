<?php

namespace App\Filament\Resources\Pipelines;

use App\Filament\Resources\Pipelines\Pages\EditPipeline;
use App\Filament\Resources\Pipelines\Pages\ListPipelines;
use App\Filament\Resources\Pipelines\Schemas\PipelineForm;
use App\Filament\Resources\Pipelines\Tables\PipelinesTable;
use App\Models\Pipeline;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PipelineResource extends Resource
{
    protected static ?string $model = Pipeline::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PipelineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PipelinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPipelines::route('/'),
            'edit' => EditPipeline::route('/{record}/edit'),
        ];
    }

    /**
     * Pipelines are limited to the two system-defined pipelines
     * (Self serve and Enterprise): no more can be created.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
