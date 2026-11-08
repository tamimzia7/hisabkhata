@extends('layouts.student')

@section('title', __('student.permissions.title'))
@section('heading', __('student.permissions.title'))
@section('subheading', __('student.permissions.subtitle'))

@section('content')
@php
    $defaultsFor = fn (string $role): array => [
        'manage_members' => $role === 'manager',
        'manage_meals' => $role === 'manager',
        'manage_bazar' => $role === 'manager',
        'manage_finance' => $role === 'accountant',
        'manage_settlements' => $role === 'accountant',
        'view_reports' => true,
    ];
@endphp
<div class="space-y-4">
    <div class="glass rounded-2xl p-4">
        <p class="text-xs text-gray-400 font-bn">{{ __('student.permissions.owner_note') }}</p>
    </div>

    <form method="POST" action="{{ route('student.mess.permissions.update', $mess) }}" class="glass rounded-2xl p-4">
        @csrf
        @method('PUT')
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>{{ __('student.members.name') }}</th>
                        <th>{{ __('student.permissions.role') }}</th>
                        @foreach($abilities as $key => $label)
                            <th class="text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $member)
                        @php
                            $defaults = $defaultsFor($member->role);
                            $overrides = is_array($member->permissions) ? $member->permissions : [];
                            $isOwner = $member->role === 'owner';
                        @endphp
                        <tr>
                            <td class="font-bn font-bold text-white">{{ $member->name }}</td>
                            <td>
                                @if($isOwner)
                                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] text-emerald-400 font-bn">{{ __('student.members.roles.owner') }}</span>
                                @else
                                    <select name="roles[{{ $member->id }}]" class="field text-xs font-bn">
                                        @foreach(['manager', 'accountant', 'member'] as $role)
                                            <option value="{{ $role }}" @selected($member->role === $role)>{{ __('student.members.roles.'.$role) }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            @foreach(array_keys($abilities) as $ability)
                                <td class="text-center">
                                    @if($isOwner)
                                        <span class="text-emerald-400">✓</span>
                                    @else
                                        <input type="checkbox"
                                               name="permissions[{{ $member->id }}][{{ $ability }}]"
                                               value="1"
                                               @checked(array_key_exists($ability, $overrides) ? (bool) $overrides[$ability] : (bool) ($defaults[$ability] ?? false))
                                               class="rounded border-white/20 bg-black/30 text-emerald-500 focus:ring-emerald-500/40">
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex items-center justify-between gap-2">
            <p class="text-[10px] text-gray-500 font-bn">{{ __('student.permissions.hint') }}</p>
            <button type="submit" class="btn-emerald rounded-lg px-5 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
        </div>
    </form>
</div>
@endsection
