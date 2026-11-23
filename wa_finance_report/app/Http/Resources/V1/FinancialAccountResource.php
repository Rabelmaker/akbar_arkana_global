<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'institution_name' => $this->institution_name,
            'account_number' => $this->account_number,
            'account_holder_name' => $this->account_holder_name,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'currency' => $this->currency,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'include_in_total' => $this->include_in_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
