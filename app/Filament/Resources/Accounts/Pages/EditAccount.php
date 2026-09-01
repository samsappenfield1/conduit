<?php

namespace App\Filament\Resources\Accounts\Pages;

use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Support\FieldValueInput;
use App\Livewire\ActivityTimeline;
use App\Models\Account;
use App\Models\FieldValue;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class EditAccount extends EditRecord
{
    protected static string $resource = AccountResource::class;

    /** @var array<int, mixed> */
    protected array $pendingFieldValues = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label('Archive')
                ->modalHeading('Archive account')
                ->modalDescription(Account::ARCHIVE_WARNING)
                ->modalSubmitActionLabel('Archive'),
            RestoreAction::make(),
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
