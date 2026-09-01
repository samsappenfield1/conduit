<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Support\FieldValueInput;
use App\Livewire\ActivityTimeline;
use App\Models\FieldValue;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    /** @var array<int, mixed> */
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
                Livewire::make(ActivityTimeline::class, fn (): array => ['record' => $this->getRecord()]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['fieldValues'] = $this->getRecord()->fieldValues()
            ->with('field')
            ->get()
            ->mapWithKeys(fn (FieldValue $fieldValue): array => [
                $fieldValue->field_id => FieldValueInput::hydrate($fieldValue),
            ])
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
                ['typed_value' => $value],
            );
        }
    }
}
