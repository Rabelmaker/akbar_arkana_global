<?php

namespace App\Http\Requests\User;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use App\Models\Budget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBudgetsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Budget::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(BudgetStatus::class)],
            'period_type' => ['nullable', Rule::enum(BudgetPeriodType::class)],
            'date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', Rule::in([6, 12, 24, 48])],
        ];
    }
}
