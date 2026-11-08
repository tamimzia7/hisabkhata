@extends('layouts.student')

@section('title', __('student.invitations.title'))
@section('heading', __('student.invitations.title'))
@section('subheading', $invitation->mess?->name)

@section('content')
<div class="mx-auto max-w-lg">
    <div class="glass-strong rounded-2xl p-6 text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-xl font-bold text-black">৳</div>
        <h2 class="mt-3 text-lg font-bold font-bn text-white">{{ __('student.invitations.join') }} {{ $invitation->mess?->name }}</h2>
        <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('student.invitations.invited_role') }}: <b class="text-emerald-300">{{ __('student.members.roles.'.$invitation->role) }}</b></p>
        <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ $invitation->email }}</p>

        @if($invitation->accepted_at !== null)
            <p class="mt-4 rounded-xl bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300 font-bn">{{ __('student.invitations.already_accepted') }}</p>
            <a href="{{ route('student.messes.index') }}" class="btn-emerald mt-3 inline-block rounded-lg px-5 py-2 text-sm font-bold font-bn">{{ __('student.invitations.go_messes') }}</a>
        @elseif(! $invitation->isPending())
            <p class="mt-4 rounded-xl bg-red-500/10 px-4 py-3 text-sm text-red-300 font-bn">{{ __('student.invitations.expired') }}</p>
        @elseif($canAccept)
            <form method="POST" action="{{ route('student.invitations.accept', $invitation->token) }}" class="mt-4">
                @csrf
                <button type="submit" class="btn-emerald w-full rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('student.invitations.accept') }}</button>
            </form>
        @else
            <p class="mt-4 rounded-xl bg-orange-500/10 px-4 py-3 text-sm text-orange-300 font-bn">{{ __('student.invitations.email_mismatch') }}</p>
        @endif
    </div>
</div>
@endsection
