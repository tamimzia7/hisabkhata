<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountRequest extends FormRequest
{
    /**
     * Determine whether the user may manage accounts.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        return $mess instanceof Mess && $this->user()->can('manageFinance', $mess);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(MessAccount::TYPES)],
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
