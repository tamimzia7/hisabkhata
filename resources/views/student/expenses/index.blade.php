@extends('layouts.student')

@section('title', __('student.expenses.title'))
@section('heading', __('student.expenses.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.expenses.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.type') }}</label>
            <select name="type" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                <option value="meal" @selected(request('type') === 'meal')>{{ __('student.expenses.meal') }}</option>
                <option value="other" @selected(request('type') === 'other')>{{ __('student.expenses.other') }}</option>
            </select>
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        <div class="ml-auto flex gap-2 text-[10px] font-bn">
            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-300">{{ __('student.expenses.meal_total') }}: ৳{{ localize_money($mealTotal) }}</span>
            <span class="rounded-full bg-orange-500/10 px-3 py-1 text-orange-300">{{ __('student.expenses.other_total') }}: ৳{{ localize_money($otherTotal) }}</span>
        </div>
        @if($locked)
            <span class="rounded-full bg-red-500/15 px-3 py-1 text-[10px] font-bold text-red-300 font-bn">🔒 {{ __('student.common.locked') }}</span>
        @endif
    </form>

    @if($canManage && ! $locked)
        <form method="POST" action="{{ route('student.mess.expenses.store', $mess) }}" enctype="multipart/form-data" class="glass rounded-2xl p-4"
              x-data="{ personal: {{ old('is_personal') ? 'true' : 'false' }} }">
            @csrf
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.expenses.add') }}</h3>
            <div class="grid gap-2 sm:grid-cols-4">
                <input name="title" value="{{ old('title') }}" required maxlength="120" placeholder="{{ __('student.expenses.expense_title') }}" class="field font-bn sm:col-span-2">
                <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required class="field">
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="{{ __('student.common.amount') }}" class="field">
                <input name="category" value="{{ old('category', 'other') }}" maxlength="60" placeholder="{{ __('student.expenses.category') }}" class="field font-bn">
                <select name="allocation_method" class="field font-bn">
                    <option value="equal">{{ __('student.expenses.by_equal') }}</option>
                    <option value="meals">{{ __('student.expenses.by_meals') }}</option>
                </select>
                <select name="account_id" class="field font-bn">
                    <option value="">{{ __('student.expenses.account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 rounded-lg bg-white/5 px-3 text-xs text-gray-300 font-bn">
                    <input type="checkbox" name="is_meal_related" value="1" class="rounded border-white/20 bg-black/30 text-emerald-500">
                    {{ __('student.expenses.meal_related') }}
                </label>
                <label class="flex items-center gap-2 rounded-lg bg-white/5 px-3 text-xs text-gray-300 font-bn">
                    <input type="checkbox" name="is_personal" value="1" x-model="personal" class="rounded border-white/20 bg-black/30 text-emerald-500">
                    {{ __('student.expenses.personal') }}
                </label>
                <select name="mess_member_id" x-show="personal" x-cloak class="field font-bn sm:col-span-2">
                    <option value="">{{ __('student.expenses.personal_for') }}</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected(old('mess_member_id') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <input name="notes" value="{{ old('notes') }}" placeholder="{{ __('student.expenses.notes') }}" class="field font-bn sm:col-span-2">
                <input type="file" name="receipt" accept="image/*" class="field text-[10px] sm:col-span-2">
            </div>
            <button type="submit" class="btn-emerald mt-3 rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
        </form>
    @endif

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.expenses.history') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.expenses.expense_date') }}</th>
                        <th>{{ __('student.expenses.expense_title') }}</th>
                        <th>{{ __('student.expenses.category') }}</th>
                        <th>{{ __('student.common.type') }}</th>
                        <th>{{ __('student.common.amount') }}</th>
                        <th>{{ __('student.expenses.account') }}</th>
                        <th>{{ __('student.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->expense_date?->format('d M Y') }}</td>
                            <td class="font-bn font-bold text-white">{{ $expense->title }}</td>
                            <td class="font-bn">{{ $expense->category }}</td>
                            <td>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bn {{ $expense->is_meal_related ? 'bg-emerald-500/15 text-emerald-400' : 'bg-orange-500/15 text-orange-400' }}">{{ $expense->is_meal_related ? __('student.expenses.meal') : __('student.expenses.other') }}</span>
                                @if($expense->is_personal)
                                    <span class="mt-1 block rounded-full bg-sky-500/15 px-2 py-0.5 text-[10px] text-sky-300 font-bn">{{ __('student.expenses.personal') }}: {{ $expense->member?->name ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="font-bold text-orange-400">৳{{ localize_money($expense->amount) }}</td>
                            <td class="text-[11px] font-bn">{{ $expense->account?->name ?? '—' }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if($expense->receipt_path)
                                        <a href="{{ Storage::disk('public')->url($expense->receipt_path) }}" target="_blank" class="text-[11px] text-emerald-400 font-bn">{{ __('student.common.receipt') }}</a>
                                    @endif
                                    @if($canManage && ! $locked)
                                        <details class="relative">
                                            <summary class="cursor-pointer text-[11px] text-emerald-400 font-bn">{{ __('student.common.edit') }}</summary>
                                            <div class="mt-2 w-56 space-y-2 rounded-lg border border-white/10 bg-[#07130d] p-3">
                                                <form method="POST" action="{{ route('student.mess.expenses.update', [$mess, $expense]) }}" enctype="multipart/form-data" class="space-y-2">
                                                    @csrf @method('PUT')
                                                    <input name="title" value="{{ $expense->title }}" required class="field text-xs font-bn">
                                                    <input type="date" name="expense_date" value="{{ $expense->expense_date?->toDateString() }}" class="field text-xs">
                                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ $expense->amount }}" class="field text-xs">
                                                    <input name="category" value="{{ $expense->category }}" class="field text-xs font-bn">
                                                    <select name="allocation_method" class="field text-xs font-bn">
                                                        <option value="equal" @selected($expense->allocation_method === 'equal')>{{ __('student.expenses.by_equal') }}</option>
                                                        <option value="meals" @selected($expense->allocation_method === 'meals')>{{ __('student.expenses.by_meals') }}</option>
                                                    </select>
                                                    <select name="account_id" class="field text-xs font-bn">
                                                        <option value="">{{ __('student.expenses.account') }}</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->id }}" @selected($expense->account_id === $account->id)>{{ $account->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label class="flex items-center gap-2 text-[11px] text-gray-300 font-bn"><input type="checkbox" name="is_meal_related" value="1" @checked($expense->is_meal_related) class="rounded border-white/20 bg-black/30 text-emerald-500">{{ __('student.expenses.meal_related') }}</label>
                                                    <label class="flex items-center gap-2 text-[11px] text-gray-300 font-bn"><input type="checkbox" name="is_personal" value="1" @checked($expense->is_personal) class="rounded border-white/20 bg-black/30 text-emerald-500">{{ __('student.expenses.personal') }}</label>
                                                    <select name="mess_member_id" class="field text-xs font-bn">
                                                        <option value="">{{ __('student.expenses.personal_for') }}</option>
                                                        @foreach($members as $member)
                                                            <option value="{{ $member->id }}" @selected($expense->mess_member_id === $member->id)>{{ $member->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="notes" value="{{ $expense->notes }}" class="field text-xs font-bn">
                                                    <input type="file" name="receipt" accept="image/*" class="field text-[10px]">
                                                    <button type="submit" class="btn-emerald w-full rounded-lg py-1.5 text-[11px] font-bold font-bn">{{ __('student.common.save') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('student.mess.expenses.destroy', [$mess, $expense]) }}" onsubmit="return confirm(@js(__('student.common.confirm_delete')))">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full rounded-lg bg-red-500/10 py-1.5 text-[11px] text-red-300 font-bn">{{ __('student.common.delete') }}</button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-5 text-center text-gray-500 font-bn">{{ __('student.common.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $expenses->links() }}</div>
    </div>
</div>
@endsection
