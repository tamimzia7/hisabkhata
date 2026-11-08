@extends('layouts.student')

@section('title', __('student.accounts.title'))
@section('heading', __('student.accounts.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach($accounts as $account)
            <div class="glass rounded-xl p-3">
                <p class="kpi-label font-bn">{{ $account->name }}</p>
                <p class="mt-1 text-lg font-bold {{ $account->balance() >= 0 ? 'text-emerald-400' : 'text-red-400' }}">৳{{ localize_money($account->balance()) }}</p>
                <p class="text-[9px] uppercase tracking-wide text-gray-500">{{ __('student.accounts.types.'.$account->type) }}</p>
            </div>
        @endforeach
        <div class="glass-strong rounded-xl p-3">
            <p class="kpi-label font-bn">{{ __('student.accounts.total_balance') }}</p>
            <p class="mt-1 text-lg font-bold text-teal-300">৳{{ localize_money($totalBalance) }}</p>
        </div>
    </div>

    @if($canManage)
        <div class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('student.mess.accounts.store', $mess) }}" class="glass rounded-2xl p-4">
                @csrf
                <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.accounts.add') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <input name="name" value="{{ old('name') }}" required maxlength="80" placeholder="{{ __('student.accounts.name') }}" class="field font-bn">
                    <select name="type" class="field font-bn">
                        @foreach(['cash', 'bank', 'bkash', 'nagad'] as $type)
                            <option value="{{ $type }}">{{ __('student.accounts.types.'.$type) }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0" name="opening_balance" value="{{ old('opening_balance', 0) }}" placeholder="{{ __('student.accounts.opening_balance') }}" class="field">
                    <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('student.mess.accounts.transfer', $mess) }}" class="glass rounded-2xl p-4">
                @csrf
                <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.accounts.transfer') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <select name="from_account_id" required class="field font-bn">
                        <option value="">{{ __('student.accounts.from') }}</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <select name="to_account_id" required class="field font-bn">
                        <option value="">{{ __('student.accounts.to') }}</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }}</option>
                        @endforeach
                    </select>
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="{{ __('student.common.amount') }}" class="field">
                    <input type="date" name="transaction_date" value="{{ now()->toDateString() }}" required class="field">
                    <input name="description" placeholder="{{ __('student.common.notes') }}" class="field font-bn sm:col-span-2">
                    <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn sm:col-span-2">{{ __('student.accounts.transfer') }}</button>
                </div>
                <p class="mt-2 text-[10px] text-gray-500 font-bn">{{ __('student.accounts.transfer_note') }}</p>
            </form>
        </div>
    @endif

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.accounts.recent_transactions') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr><th>{{ __('student.transactions.date') }}</th><th>{{ __('student.common.type') }}</th><th>{{ __('student.accounts.title') }}</th><th>{{ __('student.transactions.description') }}</th><th>{{ __('student.common.amount') }}</th></tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                            <td class="text-[11px] font-bn">{{ __('student.transactions.types.'.$transaction->type) }}</td>
                            <td class="text-[11px] font-bn">{{ $transaction->account?->name ?? '—' }}</td>
                            <td class="max-w-[240px] truncate text-[11px] text-gray-400">{{ $transaction->description }}</td>
                            <td class="font-bold {{ $transaction->direction === 'credit' ? 'text-emerald-400' : 'text-orange-400' }}">{{ $transaction->direction === 'credit' ? '+' : '-' }}৳{{ localize_money($transaction->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-5 text-center text-gray-500 font-bn">{{ __('student.transactions.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
