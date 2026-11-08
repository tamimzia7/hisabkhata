<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    /**
     * Determine whether the user may invite members.
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
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([MessMember::ROLE_MANAGER, MessMember::ROLE_ACCOUNTANT, MessMember::ROLE_MEMBER])],
        ];
    }
}
