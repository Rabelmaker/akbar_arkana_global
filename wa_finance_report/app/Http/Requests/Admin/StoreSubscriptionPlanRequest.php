<?php

namespace App\Http\Requests\Admin;

use App\Enums\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage plans');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => Str::slug($this->string('code')->toString())]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64', Rule::unique('subscription_plans', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'billing_period' => ['required', Rule::enum(BillingPeriod::class)],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:36500'],
            'max_financial_accounts' => ['nullable', 'integer', 'min:1'],
            'max_monthly_transactions' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'api_access' => ['required', 'boolean'],
            'whatsapp_integration' => ['required', 'boolean'],
            'google_sheets_integration' => ['required', 'boolean'],
            'report_export' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
