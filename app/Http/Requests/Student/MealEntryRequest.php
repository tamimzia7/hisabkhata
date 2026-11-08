<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MealEntryRequest extends FormRequest
{
    /**
     * Determine whether the user may write a meal entry.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        return $mess instanceof Mess && $this->user()->can('view', $mess);
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
            'mess_member_id' => [
                'required',
                Rule::exists('mess_members', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'entry_date' => ['required', 'date'],
            'breakfast' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'lunch' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'dinner' => ['nullable', 'numeric', 'min:0', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Ensure at least one meal was entered.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $total = (float) $this->input('breakfast', 0)
                + (float) $this->input('lunch', 0)
                + (float) $this->input('dinner', 0);

            if ($total <= 0) {
                $validator->errors()->add('breakfast', __('student.errors.meals_required'));
            }
        });
    }
}
