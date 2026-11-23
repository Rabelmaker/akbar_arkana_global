<?php

namespace App\Http\Requests\User;

use App\Support\ApiTokenAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('access API token settings');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => ['required', 'string', Rule::in(ApiTokenAbility::values()), 'distinct'],
            'expires_at' => ['nullable', 'date', 'after:today', 'before_or_equal:'.now()->addYear()->toDateString()],
        ];
    }
}
