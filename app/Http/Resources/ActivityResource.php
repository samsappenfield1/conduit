<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'description' => $this->description,
            'event' => $this->event,
            'changes' => [
                'attributes' => $this->attribute_changes->get('attributes', []),
                'old' => $this->attribute_changes->get('old', []),
            ],
            'causer' => $this->causer ? [
                'id' => $this->causer->id,
                'name' => $this->causer->name,
                'email' => $this->causer->email,
            ] : null,
            'created_at' => $this->created_at,
        ];
    }
}
