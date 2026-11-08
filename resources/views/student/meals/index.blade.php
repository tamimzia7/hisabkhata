@extends('layouts.student')

@section('title', __('student.meals.title'))
@section('heading', __('student.meals.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <form method="GET" action="{{ route('student.mess.meals.index', $mess) }}" class="glass flex flex-wrap items-end gap-2 rounded-xl p-3">
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.common.month') }}</label>
            <input type="month" name="month" value="{{ $month }}" class="field">
        </div>
        <div>
            <label class="mb-1 block text-[10px] text-gray-500 font-bn">{{ __('student.meals.member') }}</label>
            <select name="member" class="field font-bn">
                <option value="">{{ __('student.common.all') }}</option>
                @foreach($members as $member)
                    <option value="{{ $member->id }}" @selected(request('member') == $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.common.apply') }}</button>
        @if($locked)
            <span class="ml-auto rounded-full bg-red-500/15 px-3 py-1 text-[10px] font-bold text-red-300 font-bn">🔒 {{ __('student.meals.locked') }}</span>
        @endif
    </form>

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        @foreach([__('student.meals.breakfast'), __('student.meals.lunch'), __('student.meals.dinner'), __('student.meals.total')] as $index => $label)
            @php($keys = ['breakfast', 'lunch', 'dinner', 'total'])
            <div class="glass rounded-xl p-3"><p class="kpi-label font-bn">{{ $label }}</p><p class="text-lg font-bold text-emerald-400">{{ localize_number($totals[$keys[$index]]) }}</p></div>
        @endforeach
    </div>

    @unless($locked)
        @if($canManage || $ownMember)
        <form id="meal-form" method="POST" action="{{ route('student.mess.meals.store', $mess) }}" class="glass rounded-2xl p-4">
            @csrf
            <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.meals.add') }}</h3>
            <div class="grid gap-2 sm:grid-cols-7">
                @if($canManage)
                    <select name="mess_member_id" required class="field font-bn sm:col-span-2">
                        <option value="">{{ __('student.meals.member') }}</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}" @selected(old('mess_member_id') == $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                @else
                    <input type="hidden" name="mess_member_id" value="{{ $ownMember->getKey() }}">
                    <div class="field flex items-center font-bn sm:col-span-2">{{ $ownMember->name }}</div>
                @endif
                <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}" required class="field sm:col-span-2">
                <input type="number" step="0.5" min="0" max="30" name="breakfast" value="{{ old('breakfast', 0) }}" placeholder="{{ __('student.meals.breakfast') }}" class="field">
                <input type="number" step="0.5" min="0" max="30" name="lunch" value="{{ old('lunch', 0) }}" placeholder="{{ __('student.meals.lunch') }}" class="field">
                <input type="number" step="0.5" min="0" max="30" name="dinner" value="{{ old('dinner', 0) }}" placeholder="{{ __('student.meals.dinner') }}" class="field">
                <input name="note" value="{{ old('note') }}" maxlength="255" placeholder="{{ __('student.meals.note') }}" class="field font-bn sm:col-span-5">
                <button type="submit" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn sm:col-span-2">{{ __('student.meals.add') }}</button>
            </div>
        </form>
        @endif
    @endunless

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.meals.monthly_summary') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.members.name') }}</th>
                        <th>{{ __('student.meals.breakfast') }}</th>
                        <th>{{ __('student.meals.lunch') }}</th>
                        <th>{{ __('student.meals.dinner') }}</th>
                        <th>{{ __('student.meals.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        @php($row = $summary[$member->id] ?? null)
                        <tr>
                            <td class="font-bn font-bold text-white">{{ $member->name }}</td>
                            <td>{{ localize_number((float) ($row->breakfast ?? 0)) }}</td>
                            <td>{{ localize_number((float) ($row->lunch ?? 0)) }}</td>
                            <td>{{ localize_number((float) ($row->dinner ?? 0)) }}</td>
                            <td class="font-bold text-emerald-400">{{ localize_number((float) ($row->total ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-gray-500 font-bn">{{ __('student.members.no_members') }}</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="border-t border-emerald-400/20">
                        <td class="font-bn font-bold text-white">{{ __('student.common.total') }}</td>
                        <td class="font-bold">{{ localize_number($totals['breakfast']) }}</td>
                        <td class="font-bold">{{ localize_number($totals['lunch']) }}</td>
                        <td class="font-bold">{{ localize_number($totals['dinner']) }}</td>
                        <td class="font-bold text-emerald-400">{{ localize_number($totals['total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.meals.history') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.meals.entry_date') }}</th>
                        <th>{{ __('student.meals.member') }}</th>
                        <th>{{ __('student.meals.breakfast') }}</th>
                        <th>{{ __('student.meals.lunch') }}</th>
                        <th>{{ __('student.meals.dinner') }}</th>
                        <th>{{ __('student.meals.total') }}</th>
                        <th>{{ __('student.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                            <td class="font-bn">{{ $entry->member?->name ?? '—' }}</td>
                            <td>{{ localize_number($entry->breakfast) }}</td>
                            <td>{{ localize_number($entry->lunch) }}</td>
                            <td>{{ localize_number($entry->dinner) }}</td>
                            <td class="font-bold text-emerald-400">{{ localize_number($entry->total_meals) }}</td>
                            <td>
                                @if(! $locked)
                                    <details class="relative">
                                        <summary class="cursor-pointer text-[11px] text-emerald-400 font-bn">{{ __('student.common.edit') }}</summary>
                                        <div class="mt-2 w-56 space-y-2 rounded-lg border border-white/10 student-popover p-3">
                                            <form method="POST" action="{{ route('student.mess.meals.update', [$mess, $entry]) }}" class="space-y-2">
                                                @csrf @method('PUT')
                                                <input type="date" name="entry_date" value="{{ $entry->entry_date?->toDateString() }}" class="field text-xs">
                                                <input type="number" step="0.5" min="0" name="breakfast" value="{{ $entry->breakfast }}" class="field text-xs" placeholder="{{ __('student.meals.breakfast') }}">
                                                <input type="number" step="0.5" min="0" name="lunch" value="{{ $entry->lunch }}" class="field text-xs" placeholder="{{ __('student.meals.lunch') }}">
                                                <input type="number" step="0.5" min="0" name="dinner" value="{{ $entry->dinner }}" class="field text-xs" placeholder="{{ __('student.meals.dinner') }}">
                                                <input name="note" value="{{ $entry->note }}" class="field text-xs font-bn" placeholder="{{ __('student.meals.note') }}">
                                                <button type="submit" class="btn-emerald w-full rounded-lg py-1.5 text-[11px] font-bold font-bn">{{ __('student.common.save') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('student.mess.meals.destroy', [$mess, $entry]) }}" onsubmit="return confirm(@js(__('student.common.confirm_delete')))">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full rounded-lg bg-red-500/10 py-1.5 text-[11px] text-red-300 font-bn">{{ __('student.common.delete') }}</button>
                                            </form>
                                        </div>
                                    </details>
                                @else
                                    <span class="text-[10px] text-gray-600 font-bn">{{ __('student.common.locked') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-gray-500 font-bn">{{ __('student.meals.no_entries') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>
@endsection
