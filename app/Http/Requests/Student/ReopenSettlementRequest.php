<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;

class ReopenSettlementRequest extends FormRequest
{
    /**
     * Determine whether the user may reopen settlements.
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
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
