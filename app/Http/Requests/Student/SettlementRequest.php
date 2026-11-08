<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;

class SettlementRequest extends FormRequest
{
    /**
     * Determine whether the user may finalize settlements.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        return $mess instanceof Mess && $this->user()->can('manageSettlements', $mess);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'date_format:Y-m'],
        ];
    }
}
