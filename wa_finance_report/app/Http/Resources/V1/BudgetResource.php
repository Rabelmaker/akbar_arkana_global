<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BudgetResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'financial_account' => $this->whenLoaded('financialAccount', fn () => $this->financialAccount ? [
                'id' => $this->financialAccount->id,
                'name' => $this->financialAccount->name,
            ] : null),
            'period_type' => $this->period_type->value,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'amount' => $this->amount,
            'used_amount' => number_format($this->usedAmount(), 2, '.', ''),
            'remaining_amount' => number_format($this->remainingAmount(), 2, '.', ''),
            'usage_percentage' => $this->usagePercentage(),
            'alert_percentage' => $this->alert_percentage,
            'rollover_enabled' => $this->rollover_enabled,
            'status' => $this->status->value,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
