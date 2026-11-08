@extends('layouts.student')

@section('title', __('student.members.title'))
@section('heading', __('student.members.title'))
@section('subheading', $mess->name)

@section('content')
<div class="space-y-4">
    <div class="grid gap-2 sm:grid-cols-3">
        <div class="glass rounded-xl p-3"><p class="kpi-label font-bn">{{ __('student.members.title') }}</p><p class="text-xl font-bold text-emerald-400">{{ localize_digits($members->count()) }}</p></div>
        <div class="glass rounded-xl p-3"><p class="kpi-label font-bn">{{ __('student.members.active') }}</p><p class="text-xl font-bold text-teal-400">{{ localize_digits($members->where('status', 'active')->count()) }}</p></div>
        <div class="glass rounded-xl p-3"><p class="kpi-label font-bn">{{ __('student.members.pending_invitations') }}</p><p class="text-xl font-bold text-orange-400">{{ localize_digits($invitations->count()) }}</p></div>
    </div>

    @if($canManage)
        <div class="grid gap-4 lg:grid-cols-2">
            <form method="POST" action="{{ route('student.mess.members.store', $mess) }}" enctype="multipart/form-data" class="glass rounded-2xl p-4">
                @csrf
                <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.members.add') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <input name="name" value="{{ old('name') }}" required maxlength="120" placeholder="{{ __('student.members.name') }} *" class="field font-bn">
                    </div>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('student.members.email') }}" class="field">
                    <input name="phone" value="{{ old('phone') }}" maxlength="30" placeholder="{{ __('student.members.phone') }}" class="field">
                    <input type="date" name="joining_date" value="{{ old('joining_date', now()->toDateString()) }}" class="field">
                    <select name="role" class="field font-bn">
                        <option value="member">{{ __('student.members.roles.member') }}</option>
                        <option value="manager">{{ __('student.members.roles.manager') }}</option>
                        <option value="accountant">{{ __('student.members.roles.accountant') }}</option>
                    </select>
                    <input name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="{{ __('student.members.emergency_contact') }}" class="field font-bn">
                    <div class="flex items-center gap-2">
                        <input type="file" name="photo" accept="image/*" class="field text-xs">
                    </div>
                </div>
                <button type="submit" class="btn-emerald mt-3 rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.members.add') }}</button>
            </form>

            <form method="POST" action="{{ route('student.mess.invitations.store', $mess) }}" class="glass rounded-2xl p-4">
                @csrf
                <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.invitations.send') }}</h3>
                <div class="grid gap-2 sm:grid-cols-2">
                    <input type="email" name="email" required placeholder="{{ __('student.invitations.email') }}" class="field">
                    <select name="role" class="field font-bn">
                        <option value="member">{{ __('student.members.roles.member') }}</option>
                        <option value="manager">{{ __('student.members.roles.manager') }}</option>
                        <option value="accountant">{{ __('student.members.roles.accountant') }}</option>
                    </select>
                </div>
                <button type="submit" class="btn-emerald mt-3 rounded-lg px-4 py-2 text-xs font-bold font-bn">{{ __('student.invitations.send') }}</button>

                <div class="mt-4 space-y-1.5">
                    @forelse($invitations as $invitation)
                        <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white/5 px-2.5 py-1.5 text-[11px]">
                            <span class="min-w-0 flex-1 truncate text-gray-200">{{ $invitation->email }}</span>
                            <span class="rounded bg-white/10 px-1.5 py-0.5 text-gray-400 font-bn">{{ __('student.members.roles.'.$invitation->role) }}</span>
                            <button type="button" onclick="navigator.clipboard && navigator.clipboard.writeText(@js(route('student.invitations.show', $invitation->token)))" class="text-emerald-400">{{ __('student.invitations.copy_link') }}</button>
                            <form method="POST" action="{{ route('student.mess.invitations.destroy', [$mess, $invitation]) }}">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400">{{ __('student.common.delete') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-[11px] text-gray-500 font-bn">{{ __('student.invitations.none') }}</p>
                    @endforelse
                </div>
            </form>
        </div>
    @endif

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.members.title') }}</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.members.name') }}</th>
                        <th>{{ __('student.members.role') }}</th>
                        <th>{{ __('student.members.status') }}</th>
                        <th>{{ __('student.members.phone') }}</th>
                        <th>{{ __('student.members.joining_date') }}</th>
                        <th>{{ __('student.reports.columns.meals') }}</th>
                        <th>{{ __('student.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($members as $member)
                        <tr>
                            <td>
                                <div class="flex items-center gap-2">
                                    @if($member->photo_path)
                                        <img src="{{ Storage::disk('public')->url($member->photo_path) }}" alt="" class="h-7 w-7 rounded-full object-cover">
                                    @else
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500/20 text-[10px] font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-xs font-bold text-white font-bn">{{ $member->name }}</p>
                                        <p class="truncate text-[10px] text-gray-500">{{ $member->email ?: '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td><span class="rounded-full bg-white/5 px-2 py-0.5 text-[10px] font-bn">{{ __('student.members.roles.'.$member->role) }}</span></td>
                            <td><span class="text-[10px] {{ $member->status === 'active' ? 'text-emerald-400' : 'text-gray-500' }} font-bn">{{ __('student.members.statuses.'.$member->status) }}</span></td>
                            <td class="text-[11px]">{{ $member->phone ?: '—' }}</td>
                            <td class="text-[11px]">{{ $member->joining_date?->format('d M Y') ?? '—' }}</td>
                            <td class="text-[11px] font-bold text-emerald-400">{{ localize_number((float) $member->total_meals) }}</td>
                            <td>
                                @if($canManage && $member->role !== 'owner')
                                    <details class="relative">
                                        <summary class="cursor-pointer text-[11px] text-emerald-400 font-bn">{{ __('student.common.edit') }}</summary>
                                        <div class="mt-2 w-56 space-y-2 rounded-lg border border-white/10 bg-[#07130d] p-3">
                                            <form method="POST" action="{{ route('student.mess.members.update', [$mess, $member]) }}" enctype="multipart/form-data" class="space-y-2">
                                                @csrf @method('PUT')
                                                <input name="name" value="{{ $member->name }}" required class="field text-xs font-bn">
                                                <input name="email" value="{{ $member->email }}" class="field text-xs">
                                                <input name="phone" value="{{ $member->phone }}" class="field text-xs">
                                                <input type="date" name="joining_date" value="{{ $member->joining_date?->toDateString() }}" class="field text-xs">
                                                <select name="role" class="field text-xs font-bn">
                                                    @foreach(['manager' => __('student.members.roles.manager'), 'accountant' => __('student.members.roles.accountant'), 'member' => __('student.members.roles.member')] as $value => $label)
                                                        <option value="{{ $value }}" @selected($member->role === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="status" class="field text-xs font-bn">
                                                    @foreach(['active' => __('student.members.statuses.active'), 'inactive' => __('student.members.statuses.inactive')] as $value => $label)
                                                        <option value="{{ $value }}" @selected($member->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <input name="emergency_contact" value="{{ $member->emergency_contact }}" placeholder="{{ __('student.members.emergency_contact') }}" class="field text-xs font-bn">
                                                <input type="file" name="photo" accept="image/*" class="field text-[10px]">
                                                <button type="submit" class="btn-emerald w-full rounded-lg py-1.5 text-[11px] font-bold font-bn">{{ __('student.common.save') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('student.mess.members.destroy', [$mess, $member]) }}" onsubmit="return confirm(@js(__('student.common.confirm_delete')))">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full rounded-lg bg-red-500/10 py-1.5 text-[11px] text-red-300 font-bn">{{ __('student.common.delete') }}</button>
                                            </form>
                                        </div>
                                    </details>
                                @else
                                    <span class="text-[10px] text-gray-600">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-5 text-center text-gray-500 font-bn">{{ __('student.members.no_members') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
