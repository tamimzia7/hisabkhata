@extends('layouts.student')

@section('title', __('student.bazar.title'))
@section('heading', __('student.bazar.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.bazar.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.bazar.category') }}</label>
            <select name="category" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.bazar.purchased_by') }}</label>
            <select name="member" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" @selected(request('member') == $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        <span class="ml-auto rounded-full bg-white/5 px-3 py-1 text-[10px] text-gray-300 font-bn">{{ __('student.bazar.total') }}: ৳{{ localize_money($monthTotal) }}</span>
        @if($locked)
            <span class="rounded-full bg-red-500/15 px-3 py-1 text-[10px] font-bold text-red-300 font-bn">🔒 {{ __('student.common.locked') }}</span>
        @endif
    </form>

    @if($canManage && ! $locked)
        <form method="POST" action="{{ route('student.mess.bazar.store', $mess) }}" enctype="multipart/form-data" class="glass rounded-2xl p-4"
              x-data="{ items: [{ name: '', qty: 1, unit: 'kg', price: 0 }], addItem() { this.items.push({ name: '', qty: 1, unit: 'kg', price: 0 }); }, removeItem(i) { this.items.splice(i, 1); }, get total() { return this.items.reduce((s, i) => s + ((+i.qty || 0) * (+i.price || 0)), 0); } }">
            @csrf
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold font-bn">{{ __('student.bazar.add') }}</h3>
                <button type="button" @click="addItem()" class="text-[11px] text-emerald-400 font-bn">＋ {{ __('student.bazar.add_item') }}</button>
            </div>
            <div class="grid gap-2 sm:grid-cols-4">
                <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->toDateString()) }}" required class="field">
                <select name="purchased_by_member_id" class="field font-bn">
                    <option value="">{{ __('student.bazar.purchased_by') }}</option>
                    @foreach($members as $member)
                        <option value="{{ $member->id }}" @selected(old('purchased_by_member_id') == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
                <input name="category" value="{{ old('category', 'general') }}" maxlength="60" placeholder="{{ __('student.bazar.category') }}" class="field font-bn">
                <select name="payment_source" class="field font-bn">
                    @foreach(['cash' => __('student.accounts.types.cash'), 'bank' => __('student.accounts.types.bank'), 'bkash' => __('student.accounts.types.bkash'), 'nagad' => __('student.accounts.types.nagad')] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                <select name="account_id" class="field font-bn sm:col-span-2">
                    <option value="">{{ __('student.bazar.account') }}</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" @selected(old('account_id') == $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
                <input name="notes" value="{{ old('notes') }}" placeholder="{{ __('student.bazar.notes') }}" class="field font-bn sm:col-span-2">
                <input type="file" name="receipt" accept="image/*" class="field text-[10px] sm:col-span-2">
            </div>

            <div class="mt-3 space-y-2">
                <template x-for="(item, index) in items" :key="index">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-12">
                        <input :name="'items[' + index + '][name]'" x-model="item.name" placeholder="{{ __('student.bazar.item_name') }}" class="field font-bn sm:col-span-4">
                        <input type="number" step="0.01" min="0" :name="'items[' + index + '][quantity]'" x-model="item.qty" placeholder="{{ __('student.bazar.quantity') }}" class="field sm:col-span-2">
                        <input :name="'items[' + index + '][unit]'" x-model="item.unit" placeholder="{{ __('student.bazar.unit') }}" class="field sm:col-span-1">
                        <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_price]'" x-model="item.price" placeholder="{{ __('student.bazar.unit_price') }}" class="field sm:col-span-2">
                        <div class="flex items-center justify-between rounded-lg bg-white/5 px-3 text-xs text-emerald-400 sm:col-span-2">
                            <span x-text="'৳' + (((+item.qty || 0) * (+item.price || 0)).toFixed(2))"></span>
                            <button type="button" @click="removeItem(index)" class="text-red-400" x-show="items.length > 1">✕</button>
                        </div>
                    </div>
                </template>
                <div class="flex items-center justify-between border-t border-white/10 pt-3 text-sm">
                    <span class="text-gray-400 font-bn">{{ __('student.bazar.items_total') }}</span>
                    <div class="flex items-center gap-3">
                        <span class="font-bold text-emerald-400" x-text="'৳' + total.toFixed(2)"></span>
                        <input type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount') }}" placeholder="{{ __('student.bazar.total_amount') }}" class="field w-32">
                        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.bazar.history') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.bazar.purchase_date') }}</th>
                        <th>{{ __('student.bazar.category') }}</th>
                        <th>{{ __('student.bazar.purchased_by') }}</th>
                        <th>{{ __('student.bazar.items') }}</th>
                        <th>{{ __('student.common.amount') }}</th>
                        <th>{{ __('student.bazar.payment_source') }}</th>
                        <th>{{ __('student.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        <tr>
                            <td>{{ $record->purchase_date?->format('d M Y') }}</td>
                            <td class="font-bn">{{ $record->category }}</td>
                            <td class="font-bn">{{ $record->purchaser?->name ?? '—' }}</td>
                            <td class="max-w-[220px] truncate text-[11px] text-gray-400">{{ $record->items->pluck('name')->implode(', ') ?: ($record->notes ?: '—') }}</td>
                            <td class="font-bold text-orange-400">৳{{ localize_money($record->total_amount) }}</td>
                            <td class="text-[11px] font-bn">{{ __('student.accounts.types.'.$record->payment_source) }}</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if($record->receipt_path)
                                        <a href="{{ Storage::disk('public')->url($record->receipt_path) }}" target="_blank" class="text-[11px] text-emerald-400 font-bn">{{ __('student.common.receipt') }}</a>
                                    @endif
                                    @if($canManage && ! $locked)
                                        <details class="relative">
                                            <summary class="cursor-pointer text-[11px] text-emerald-400 font-bn">{{ __('student.common.edit') }}</summary>
                                            <div class="mt-2 w-56 space-y-2 rounded-lg border border-white/10 bg-[#07130d] p-3">
                                                <form method="POST" action="{{ route('student.mess.bazar.update', [$mess, $record]) }}" enctype="multipart/form-data" class="space-y-2">
                                                    @csrf @method('PUT')
                                                    <input type="date" name="purchase_date" value="{{ $record->purchase_date?->toDateString() }}" class="field text-xs">
                                                    <select name="purchased_by_member_id" class="field text-xs font-bn">
                                                        <option value="">{{ __('student.bazar.purchased_by') }}</option>
                                                        @foreach($members as $member)
                                                            <option value="{{ $member->id }}" @selected($record->purchased_by_member_id === $member->id)>{{ $member->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input name="category" value="{{ $record->category }}" class="field text-xs font-bn">
                                                    <select name="payment_source" class="field text-xs font-bn">
                                                        @foreach(['cash', 'bank', 'bkash', 'nagad'] as $method)
                                                            <option value="{{ $method }}" @selected($record->payment_source === $method)>{{ __('student.accounts.types.'.$method) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="account_id" class="field text-xs font-bn">
                                                        <option value="">{{ __('student.bazar.account') }}</option>
                                                        @foreach($accounts as $account)
                                                            <option value="{{ $account->id }}" @selected($record->account_id === $account->id)>{{ $account->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="number" step="0.01" min="0" name="total_amount" value="{{ $record->total_amount }}" class="field text-xs">
                                                    <input name="notes" value="{{ $record->notes }}" placeholder="{{ __('student.bazar.notes') }}" class="field text-xs font-bn">
                                                    <input type="file" name="receipt" accept="image/*" class="field text-[10px]">
                                                    <button type="submit" class="btn-emerald w-full rounded-lg py-1.5 text-[11px] font-bold font-bn">{{ __('student.common.save') }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('student.mess.bazar.destroy', [$mess, $record]) }}" onsubmit="return confirm(@js(__('student.common.confirm_delete')))">
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
                        <tr><td colspan="7" class="py-5 text-center text-gray-500 font-bn">{{ __('student.bazar.no_records') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $records->links() }}</div>
    </div>
</div>
@endsection
