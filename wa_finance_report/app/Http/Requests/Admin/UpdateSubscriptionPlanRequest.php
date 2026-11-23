<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class UpdateSubscriptionPlanRequest extends StoreSubscriptionPlanRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = [
            'required',
            'string',
            'max:64',
            Rule::unique('subscription_plans', 'code')->ignore($this->route('subscription_plan')),
        ];

        return $rules;
    }
}
