<?php

namespace App\Http\Requests\User;

use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class SwitchActiveAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof Account
            && $this->user()->can('switch', $account);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
