<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine whether the user may manage expenses.
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
        $mess = $this->route('mess');

        return [
            'title' => ['required', 'string', 'max:120'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'category' => ['nullable', 'string', 'max:60'],
            'is_meal_related' => ['nullable', 'boolean'],
            'allocation_method' => ['required', Rule::in(MessExpense::ALLOCATIONS)],
            'account_id' => [
                'nullable',
                Rule::exists('mess_accounts', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
