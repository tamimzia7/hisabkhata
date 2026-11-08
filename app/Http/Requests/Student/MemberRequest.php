<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
{
    /**
     * Determine whether the user may manage members of this mess.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        return $mess instanceof Mess && $this->user()->can('manageMembers', $mess);
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
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'joining_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(MessMember::STATUSES)],
            'role' => ['required', Rule::in([MessMember::ROLE_MANAGER, MessMember::ROLE_ACCOUNTANT, MessMember::ROLE_MEMBER])],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
        ];
    }
}
