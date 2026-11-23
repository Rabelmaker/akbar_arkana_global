<?php

namespace App\Http\Requests\User;

class UpdateBudgetRequest extends StoreBudgetRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('budget'));
    }
}
