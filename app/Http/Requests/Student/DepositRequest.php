<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessAccount;
use App\Models\MessDeposit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DepositRequest extends FormRequest
{
    /**
     * Determine whether the user may record deposits.
     *
     * Members may submit their own deposits; finance managers may record any type.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        if (! $mess instanceof Mess) {
            return false;
        }

        if ($this->user()->can('manageFinance', $mess)) {
            return true;
        }

        $own = $mess->memberFor($this->user());

        return $own !== null
            && (int) $this->input('mess_member_id') === (int) $own->getKey();
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
            'deposit_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'type' => ['required', Rule::in(MessDeposit::TYPES)],
            'payment_method' => ['required', Rule::in(MessAccount::TYPES)],
            'account_id' => [
                'nullable',
                Rule::exists('mess_accounts', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Prevent accidental duplicate deposit records.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mess = $this->route('mess');

            if (! $mess instanceof Mess) {
                return;
            }

            $query = MessDeposit::query()
                ->where('mess_id', $mess->getKey())
                ->where('status', MessDeposit::STATUS_ACTIVE)
                ->where('mess_member_id', $this->input('mess_member_id'))
                ->whereDate('deposit_date', $this->input('deposit_date'))
                ->where('amount', $this->input('amount'))
                ->where('type', $this->input('type'));

            $existing = $this->route('deposit');

            if ($existing instanceof MessDeposit) {
                $query->whereKeyNot($existing->getKey());
            }

            if ($query->exists()) {
                $validator->errors()->add('amount', __('student.errors.duplicate_deposit'));
            }
        });
    }
}
