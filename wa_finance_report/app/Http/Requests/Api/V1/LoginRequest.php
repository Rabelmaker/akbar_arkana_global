<?php

namespace App\Http\Requests\Api\V1;

use App\Support\ApiTokenAbility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array', 'min:1'],
            'abilities.*' => ['string', Rule::in(ApiTokenAbility::values()), 'distinct'],
            'expires_at' => ['nullable', 'date', 'after:today', 'before_or_equal:'.now()->addYear()->toDateString()],
        ];
    }
}
