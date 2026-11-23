<?php

namespace App\Http\Requests\User;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view reports');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_from' => $this->input('date_from', now()->startOfMonth()->toDateString()),
            'date_to' => $this->input('date_to', now()->endOfMonth()->toDateString()),
            'status' => $this->input('status', TransactionStatus::Posted->value),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $accountId = $this->user()->active_account_id;

        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'status' => ['required', Rule::enum(TransactionStatus::class)],
            'source' => ['nullable', Rule::enum(TransactionSource::class)],
            'financial_account_id' => [
                'nullable',
                'integer',
                Rule::exists('financial_accounts', 'id')
                    ->where('account_id', $accountId)
                    ->whereNull('deleted_at'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('transaction_categories', 'id')
                    ->where('account_id', $accountId)
                    ->whereNull('deleted_at'),
            ],
            'created_by' => [
                'nullable',
                'integer',
                Rule::exists('account_user', 'user_id')->where('account_id', $accountId),
            ],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'gte:amount_min'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 15, 25, 50])],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['date_from', 'date_to'])) {
                    return;
                }

                $from = CarbonImmutable::parse($this->input('date_from'));
                $to = CarbonImmutable::parse($this->input('date_to'));

                if ($from->diffInDays($to) > 731) {
                    $validator->errors()->add('date_to', 'Rentang laporan maksimal dua tahun.');
                }
            },
        ];
    }
}
