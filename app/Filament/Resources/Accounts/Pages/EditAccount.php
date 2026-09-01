<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Livewire\ActivityTimeline;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    /** @var array<int, string|null> */
    protected array $pendingFieldValues = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getRelationManagersContentComponent(),
                Livewire::make(ActivityTimeline::class, fn (): array => ['record' => $this->getRecord()]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['fieldValues'] = $this->getRecord()->fieldValues()
            ->pluck('value', 'field_id')
            ->map(fn (?string $value): array => filled($value) ? [$value] : [])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingFieldValues = $data['fieldValues'] ?? [];
        unset($data['fieldValues']);

        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->pendingFieldValues as $fieldId => $value) {
            $this->record->fieldValues()->updateOrCreate(
                ['field_id' => $fieldId],
                ['value' => $value],
            );
        }
    }
}
