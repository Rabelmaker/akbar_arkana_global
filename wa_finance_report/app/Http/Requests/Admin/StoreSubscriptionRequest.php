<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage subscriptions');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->whereNull('deleted_at')],
            'plan_id' => ['required', 'integer', Rule::exists('subscription_plans', 'id')->whereNull('deleted_at')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'payment_status' => ['required', Rule::enum(PaymentStatus::class)],
            'auto_renew' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $plan = SubscriptionPlan::find($this->integer('plan_id'));

                if ($plan?->billing_period->value !== 'lifetime' && ! $this->filled('end_date')) {
                    $validator->errors()->add('end_date', 'Tanggal berakhir wajib diisi untuk plan non-lifetime.');
                }
            },
        ];
    }
}
