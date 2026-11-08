@extends('layouts.student')

@section('title', __('student.mess.workspaces'))
@section('heading', __('student.mess.workspaces'))
@section('subheading', __('student.mess.workspaces_subtitle'))

@section('content')
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-gray-400 font-bn">
            {{ __('student.mess.owned_count', ['count' => $ownedCount, 'max' => $maxMesses > 0 ? $maxMesses : '∞']) }}
        </p>
        @if($maxMesses <= 0 || $ownedCount < $maxMesses)
            <a href="{{ route('student.messes.create') }}" class="btn-emerald rounded-lg px-4 py-2 text-xs font-bold font-bn">＋ {{ __('student.mess.create') }}</a>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($memberships as $membership)
            @php($workspace = $membership->mess)
            <div class="glass flex flex-col rounded-2xl p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold font-bn text-white">{{ $workspace->name }}</h3>
                        <p class="mt-0.5 truncate text-[11px] text-gray-500 font-bn">{{ $workspace->address ?: __('student.mess.no_address') }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $membership->role === 'owner' ? 'bg-emerald-500/15 text-emerald-400' : 'bg-white/10 text-gray-300' }} font-bn">
                        {{ __('student.members.roles.'.$membership->role) }}
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-1.5 text-[10px] text-gray-400 font-bn">
                    <span>{{ __('student.mess.billing_month') }}: <b class="text-gray-200">{{ $workspace->billing_month ?: '—' }}</b></span>
                    <span>{{ __('student.mess.monthly_budget') }}: <b class="text-gray-200">৳{{ localize_money($workspace->monthly_budget) }}</b></span>
                </div>

                <div class="mt-4 flex gap-2">
                    <form method="POST" action="{{ route('student.messes.switch', $workspace) }}">
                        @csrf
                        <button type="submit" class="btn-emerald rounded-lg px-3 py-1.5 text-[11px] font-bold font-bn">{{ __('student.mess.open') }}</button>
                    </form>
                    @if($workspace->owner_id === auth()->id())
                        <a href="{{ route('student.messes.edit', $workspace) }}" class="btn-ghost rounded-lg px-3 py-1.5 text-[11px] font-bold text-gray-300 font-bn">{{ __('student.common.edit') }}</a>
                    @endif
                </div>
            </div>
        @empty
            <div class="glass rounded-2xl p-8 text-center sm:col-span-2 lg:col-span-3">
                <p class="text-sm text-gray-300 font-bn">{{ __('student.mess.no_mess') }}</p>
                <a href="{{ route('student.messes.create') }}" class="btn-emerald mt-3 inline-block rounded-lg px-5 py-2 text-sm font-bold font-bn">{{ __('student.mess.create_first') }}</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
