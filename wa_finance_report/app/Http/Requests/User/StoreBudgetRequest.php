<?php

namespace App\Http\Requests\User;

use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\TransactionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Budget::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', Rule::exists('transaction_categories', 'id')->where('account_id', $this->user()->active_account_id)->where('is_active', true)->whereNull('deleted_at')],
            'financial_account_id' => ['nullable', Rule::exists('financial_accounts', 'id')->where('account_id', $this->user()->active_account_id)->where('is_active', true)->whereNull('deleted_at')],
            'period_type' => ['required', Rule::enum(BudgetPeriodType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999999999.99'],
            'alert_percentage' => ['required', 'integer', 'between:1,100'],
            'rollover_enabled' => ['required', 'boolean'],
            'status' => ['required', Rule::enum(BudgetStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('category_id')) {
                    $category = TransactionCategory::query()
                        ->where('account_id', $this->user()->active_account_id)
                        ->find($this->integer('category_id'));
                    if ($category && ! in_array($category->type->value, ['expense', 'both'], true)) {
                        $validator->errors()->add('category_id', 'Budget hanya dapat menggunakan kategori pengeluaran.');
                    }
                }

                if ($this->string('status')->toString() !== BudgetStatus::Active->value
                    || ! $this->input('start_date')
                    || ! $this->input('end_date')) {
                    return;
                }

                $query = Budget::query()
                    ->where('account_id', $this->user()->active_account_id)
                    ->where('status', BudgetStatus::Active->value)
                    ->whereDate('start_date', '<=', $this->input('end_date'))
                    ->whereDate('end_date', '>=', $this->input('start_date'));

                $this->applyNullableMatch($query, 'category_id', $this->input('category_id'));
                $this->applyNullableMatch($query, 'financial_account_id', $this->input('financial_account_id'));

                if ($current = $this->route('budget')) {
                    $query->whereKeyNot($current->id);
                }

                if ($query->exists()) {
                    $validator->errors()->add('start_date', 'Sudah ada budget aktif dengan cakupan dan periode yang bertumpang tindih.');
                }
            },
        ];
    }

    private function applyNullableMatch($query, string $column, mixed $value): void
    {
        $value ? $query->where($column, $value) : $query->whereNull($column);
    }
}
