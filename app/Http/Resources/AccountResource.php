<?php

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'current_stage' => $this->current_stage,
            'pipeline' => new PipelineResource($this->pipeline),
            'owner' => $this->owner ? [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ] : null,
            'fields' => $this->fieldsWithValues(),
            'contacts' => ContactResource::collection($this->contacts),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Every Field defined for accounts, paired with this account's value
     * (null when it has never been set).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fieldsWithValues(): array
    {
        return Account::fields()
            ->map(fn (Field $field): array => [
                'name' => $field->name,
                'type' => $field->type,
                'value' => $this->fieldValues->firstWhere('field_id', $field->id)?->typed_value,
            ])
            ->values()
            ->all();
    }
}
