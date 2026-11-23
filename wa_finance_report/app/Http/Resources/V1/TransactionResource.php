<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'transaction_number' => $this->transaction_number,
            'type' => $this->type->value,
            'transaction_date' => $this->transaction_date->toIso8601String(),
            'financial_account' => $this->whenLoaded('financialAccount', fn () => $this->financialAccount ? [
                'id' => $this->financialAccount->id,
                'name' => $this->financialAccount->name,
            ] : null),
            'destination_financial_account' => $this->whenLoaded('destinationFinancialAccount', fn () => $this->destinationFinancialAccount ? [
                'id' => $this->destinationFinancialAccount->id,
                'name' => $this->destinationFinancialAccount->name,
            ] : null),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'amount' => $this->amount,
            'fee_amount' => $this->fee_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'description' => $this->description,
            'merchant' => $this->merchant,
            'reference_number' => $this->reference_number,
            'notes' => $this->notes,
            'payment_method' => $this->payment_method,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'external_id' => $this->external_id,
            'reversed_transaction_id' => $this->reversed_transaction_id,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
