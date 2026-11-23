<?php

namespace App\Http\Requests\User;

use App\Enums\FinancialAccountType;
use App\Models\FinancialAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinancialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $financialAccount = $this->route('financial_account');

        return $financialAccount instanceof FinancialAccount
            && $this->user()->can('update', $financialAccount);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('financial_accounts')
                    ->where('account_id', $this->user()->active_account_id)
                    ->ignore($this->route('financial_account')),
            ],
            'type' => ['required', Rule::enum(FinancialAccountType::class)],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'opening_balance' => ['required', 'numeric', 'between:-999999999999999999.99,999999999999999999.99'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:64'],
            'is_active' => ['required', 'boolean'],
            'include_in_total' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
