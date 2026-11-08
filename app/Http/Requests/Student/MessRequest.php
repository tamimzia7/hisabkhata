<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessRequest extends FormRequest
{
    /**
     * Determine whether the user may create or update the mess.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        if ($mess instanceof Mess) {
            return $this->user()->can('update', $mess);
        }

        return $this->user()->hasActiveStudentSubscription();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_date' => ['nullable', 'date'],
            'billing_month' => ['nullable', 'date_format:Y-m'],
            'monthly_budget' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', Rule::in([Mess::STATUS_ACTIVE, Mess::STATUS_ARCHIVED])],
        ];
    }
}
