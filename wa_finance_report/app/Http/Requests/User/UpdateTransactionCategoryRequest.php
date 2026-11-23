<?php

namespace App\Http\Requests\User;

use App\Enums\TransactionCategoryType;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $category = $this->route('transaction_category');

        return $category instanceof TransactionCategory
            && $this->user()->can('update', $category);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(TransactionCategoryType::class)],
            'parent_id' => [
                'nullable',
                Rule::notIn([$this->route('transaction_category')?->id]),
                Rule::exists('transaction_categories', 'id')
                    ->where('account_id', $this->user()->active_account_id)
                    ->whereNull('deleted_at'),
            ],
            'icon' => ['required', 'string', 'max:64'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'between:0,65535'],
        ];
    }
}
