<?php

namespace App\Http\Requests\User;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'transaction_date' => ['required', 'date'],
            'financial_account_id' => ['required', Rule::exists('financial_accounts', 'id')->where('account_id', $this->user()->active_account_id)->where('is_active', true)->whereNull('deleted_at')],
            'destination_financial_account_id' => ['nullable', 'different:financial_account_id', Rule::exists('financial_accounts', 'id')->where('account_id', $this->user()->active_account_id)->where('is_active', true)->whereNull('deleted_at')],
            'category_id' => ['nullable', Rule::exists('transaction_categories', 'id')->where('account_id', $this->user()->active_account_id)->where('is_active', true)->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999999999.99'],
            'fee_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'merchant' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['nullable', 'string', 'max:64'],
            'status' => ['required', Rule::in([TransactionStatus::Draft->value, TransactionStatus::Posted->value])],
            'idempotency_key' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isTransfer = $this->string('type')->toString() === TransactionType::Transfer->value;
                $destination = $this->input('destination_financial_account_id');

                if ($isTransfer && ! $destination) {
                    $validator->errors()->add('destination_financial_account_id', 'Rekening tujuan wajib dipilih untuk transfer.');
                }
                if (! $isTransfer && $destination) {
                    $validator->errors()->add('destination_financial_account_id', 'Rekening tujuan hanya digunakan untuk transfer.');
                }
                if ($isTransfer && $this->input('category_id')) {
                    $validator->errors()->add('category_id', 'Transfer tidak menggunakan kategori transaksi.');
                }
            },
        ];
    }
}
