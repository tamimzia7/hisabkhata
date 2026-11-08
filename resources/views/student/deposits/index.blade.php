@extends('layouts.student')

@section('title', __('student.deposits.title'))
@section('heading', __('student.deposits.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.deposits.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.deposits.member') }}</label>
            <select name="member" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" @selected(request('member') == $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.type') }}</label>
            <select name="type" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('student.deposits.types.'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        <span class="ml-auto rounded-full bg-emerald-500/10 px-3 py-1 text-[10px] text-emerald-300 font-bn">{{ __('student.deposits.month_total') }}: ৳{{ localize_money($monthTotal) }}</span>
        @if($locked)
            <span class="rounded-full bg-red-500/15 px-3 py-1 text-[10px] font-bold text-red-300 font-bn">🔒 {{ __('student.common.locked') }}</span>
        @endif
    </form>

    @if(! $locked && ($canManage || $ownMember))
        <form id="deposit-form" method="POST" action="{{ route('student.mess.deposits.store', $mess) }}" class="glass rounded-2xl p-4">
            @csrf
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.deposits.add') }}</h3>
            <div class="grid gap-2 sm:grid-cols-4">
                @if($canManage)
                    <select name="mess_member_id" required class="field font-bn">
                        <option value="">{{ __('student.deposits.member') }}</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected(old('mess_member_id') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="mess_member_id" value="{{ $ownMember->getKey() }}">
                    <div class="field flex items-center font-bn">{{ $ownMember->name }}</div>
                @endif
                <input type="date" name="deposit_date" value="{{ old('deposit_date', now()->toDateString()) }}" required class="field">
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required placeholder="{{ __('student.deposits.amount') }}" class="field">
                @if($canManage)
                    <select name="type" class="field font-bn">
                        @foreach($types as $type)
                            <option value="{{ $type }}">{{ __('student.deposits.types.'.$type) }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="type" value="deposit">
                    <div class="field flex items-center font-bn">{{ __('student.deposits.types.deposit') }}</div>
                @endif
                <select name="payment_method" class="field font-bn">
                    @foreach(['cash', 'bank', 'bkash', 'nagad'] as $method)
                        <option value="{{ $method }}">{{ __('student.accounts.types.'.$method) }}</option>
                    @endforeach
                </select>
                <select name="account_id" class="field font-bn">
                    <option value="">{{ __('student.deposits.account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                <input name="reference" value="{{ old('reference') }}" placeholder="{{ __('student.deposits.reference') }}" class="field">
                <input name="notes" value="{{ old('notes') }}" placeholder="{{ __('student.deposits.notes') }}" class="field font-bn">
            </div>
            <button type="submit" class="btn-emerald mt-3 rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
        </form>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="glass rounded-2xl p-4">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.deposits.summary') }}</h3>
            <div class="space-y-1.5">
                @forelse($members as $member)
                    <div class="flex items-center justify-between rounded-lg bg-white/5 px-3 py-1.5 text-xs">
                        <span class="truncate text-gray-300 font-bn">{{ $member->name }}</span>
                        <span class="font-bold {{ ($byMember[$member->id] ?? 0) >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money(abs((float) ($byMember[$member->id] ?? 0))) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass rounded-2xl p-4 lg:col-span-2">
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.deposits.history') }}</h3>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('student.deposits.deposit_date') }}</th>
                            <th>{{ __('student.deposits.member') }}</th>
                            <th>{{ __('student.common.type') }}</th>
                            <th>{{ __('student.deposits.amount') }}</th>
                            <th>{{ __('student.deposits.payment_method') }}</th>
                            <th>{{ __('student.deposits.reference') }}</th>
                            <th>{{ __('student.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($deposits as $deposit)
                            <tr>
                                <td>{{ $deposit->deposit_date?->format('d M Y') }}</td>
                                <td class="font-bn">{{ $deposit->member?->name ?? '—' }}</td>
                                <td class="text-[11px] font-bn">{{ __('student.deposits.types.'.$deposit->type) }}</td>
                                <td class="font-bold text-emerald-400">৳{{ localize_money($deposit->amount) }}</td>
                                <td class="text-[11px] font-bn">{{ __('student.accounts.types.'.$deposit->payment_method) }}</td>
                                <td class="text-[11px]">{{ $deposit->reference ?: '—' }}</td>
                                <td>
                                    @if($canManage && ! $locked)
                                        <details class="relative">
                                            <summary class="cursor-pointer text-[11px] text-emerald-400 font-bn">{{ __('student.common.edit') }}</summary>
                                            <div class="mt-2 w-56 space-y-2 rounded-lg border border-white/10 bg-[#07130d] p-3">
                                                <form method="POST" action="{{ route('student.mess.deposits.update', [$mess, $deposit]) }}" class="space-y-2">
                                                    @csrf @method('PUT')
                                                    <input type="date" name="deposit_date" value="{{ $deposit->deposit_date?->toDateString() }}" class="field text-xs">
                                                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ $deposit->amount }}" class="field text-xs">
                                                    <select name="type" class="field text-xs font-bn">
                                                        @foreach($types as $type)
                                                            <option value="{{ $type }}" @selected($deposit->type === $type)>{{ __('student.deposits.types.'.$type) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="payment_method" class="field text-xs font-bn">
                                                        @foreach(['cash', 'bank', 'bkash', 'nagad'] as $method)
                                                            <option value="{{ $method }}" @selected($deposit->payment_method === $method)>{{ __('student.accounts.types.'.$method) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="account_id" class="field text-xs font-bn">
                                                        <option value="">{{ __('student.deposits.account') }}</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->id }}" @selected($deposit->account_id === $account->id)>{{ $account->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="reference" value="{{ $deposit->reference }}" placeholder="{{ __('student.deposits.reference') }}" class="field text-xs">
                                                    <input name="notes" value="{{ $deposit->notes }}" placeholder="{{ __('student.deposits.notes') }}" class="field text-xs font-bn">
                                                    <button type="submit" class="btn-emerald w-full rounded-lg py-1.5 text-[11px] font-bold font-bn">{{ __('student.common.save') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('student.mess.deposits.destroy', [$mess, $deposit]) }}" onsubmit="return confirm(@js(__('student.common.confirm_delete')))">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-full rounded-lg bg-red-500/10 py-1.5 text-[11px] text-red-300 font-bn">{{ __('student.common.delete') }}</button>
                                                </form>
                                            </div>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-5 text-center text-gray-500 font-bn">{{ __('student.common.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $deposits->links() }}</div>
        </div>
    </div>
</div>
@endsection
