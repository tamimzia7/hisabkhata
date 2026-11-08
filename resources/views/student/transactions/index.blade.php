@extends('layouts.student')

@section('title', __('student.transactions.title'))
@section('heading', __('student.transactions.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.transactions.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.type') }}</label>
            <select name="type" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ __('student.transactions.types.'.$type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.accounts.title') }}</label>
            <select name="account" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" @selected(request('account') == $account->id)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
    </form>

    <div class="glass rounded-2xl p-4">
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.transactions.date') }}</th>
                        <th>{{ __('student.common.type') }}</th>
                        <th>{{ __('student.transactions.account') }}</th>
                        <th>{{ __('student.transactions.description') }}</th>
                        <th>{{ __('student.transactions.direction') }}</th>
                        <th>{{ __('student.common.amount') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_date?->format('d M Y') }}</td>
                            <td class="text-[11px] font-bn">{{ __('student.transactions.types.'.$transaction->type) }}</td>
                            <td class="text-[11px] font-bn">{{ $transaction->account?->name ?? '—' }}</td>
                            <td class="max-w-[280px] truncate text-[11px] text-gray-400">{{ $transaction->description }}</td>
                            <td><span class="rounded-full px-2 py-0.5 text-[10px] font-bn {{ $transaction->direction === 'credit' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-orange-500/15 text-orange-400' }}">{{ __('student.transactions.'.$transaction->direction) }}</span></td>
                            <td class="font-bold {{ $transaction->direction === 'credit' ? 'text-emerald-400' : 'text-red-400' }}">{{ $transaction->direction === 'credit' ? '+' : '-' }}৳{{ localize_money($transaction->amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-5 text-center text-gray-500 font-bn">{{ __('student.transactions.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
