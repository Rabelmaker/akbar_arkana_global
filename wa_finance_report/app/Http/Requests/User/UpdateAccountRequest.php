<?php

namespace App\Http\Requests\User;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->attributes->get('activeAccount');

        return $account instanceof Account
            && $this->user()->can('update', $account);
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
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', Rule::in(['IDR'])],
            'locale' => ['required', Rule::in(['id', 'en'])],
        ];
    }
}
