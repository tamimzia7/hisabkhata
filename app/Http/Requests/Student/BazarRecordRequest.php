<?php

namespace App\Http\Requests\Student;

use App\Models\Mess;
use App\Models\MessAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BazarRecordRequest extends FormRequest
{
    /**
     * Determine whether the user may record bazar purchases.
     */
    public function authorize(): bool
    {
        $mess = $this->route('mess');

        return $mess instanceof Mess && $this->user()->can('manageBazar', $mess);
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
            'purchase_date' => ['required', 'date'],
            'purchased_by_member_id' => [
                'nullable',
                Rule::exists('mess_members', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'account_id' => [
                'nullable',
                Rule::exists('mess_accounts', 'id')->where('mess_id', $mess?->getKey()),
            ],
            'category' => ['nullable', 'string', 'max:60'],
            'payment_source' => ['required', Rule::in(MessAccount::TYPES)],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['required_with:items', 'string', 'max:120'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Ensure the purchase has a total.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = collect($this->input('items', []))->filter(fn ($item): bool => filled($item['name'] ?? null));
            $itemsTotal = $items->sum(fn (array $item): float => (float) ($item['total_price'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0))));

            if ($itemsTotal <= 0 && (float) $this->input('total_amount', 0) <= 0) {
                $validator->errors()->add('total_amount', __('student.errors.bazar_total_required'));
            }
        });
    }
}
