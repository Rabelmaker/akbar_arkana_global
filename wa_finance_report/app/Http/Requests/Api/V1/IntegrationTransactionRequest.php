<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IntegrationTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage transactions');
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('idempotency_key') && $this->header('Idempotency-Key')) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }

    public function rules(): array
    {
        return [
            'external_id' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'source' => ['required', Rule::in([
                TransactionSource::Whatsapp->value,
                TransactionSource::N8n->value,
                TransactionSource::Api->value,
                TransactionSource::Import->value,
            ])],
            'input_type' => ['required', Rule::in(['text', 'audio', 'image', 'document'])],
            'transaction' => ['required', 'array'],
            'transaction.type' => ['required', Rule::enum(TransactionType::class)],
            'transaction.transaction_date' => ['required', 'date'],
            'transaction.amount' => ['required', 'numeric', 'gt:0', 'max:999999999999999999.99'],
            'transaction.fee_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.99'],
            'transaction.category' => ['nullable', 'string', 'max:255'],
            'transaction.financial_account' => ['required', 'string', 'max:255'],
            'transaction.destination_financial_account' => ['nullable', 'string', 'max:255'],
            'transaction.description' => ['required', 'string', 'max:255'],
            'transaction.merchant' => ['nullable', 'string', 'max:255'],
            'transaction.reference_number' => ['nullable', 'string', 'max:100'],
            'transaction.payment_method' => ['nullable', 'string', 'max:64'],
            'transaction.notes' => ['nullable', 'string', 'max:2000'],
            'transaction.status' => ['nullable', Rule::in([
                TransactionStatus::Draft->value,
                TransactionStatus::Posted->value,
            ])],
            'sender' => ['nullable', 'array'],
            'sender.phone' => ['nullable', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
            'sender.name' => ['nullable', 'string', 'max:255'],
            'ai' => ['nullable', 'array'],
            'ai.model' => ['nullable', 'string', 'max:255'],
            'ai.confidence_score' => ['nullable', 'numeric', 'between:0,1'],
            'ai.raw_result' => ['nullable', 'array'],
        ];
    }
}
