<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    /**
     * Determine whether the user may transfer money between accounts.
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
            'from_account_id' => [
                'required',
                Rule::exists('mess_accounts', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'to_account_id' => [
                'required', 'different:from_account_id',
                Rule::exists('mess_accounts', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
