@extends('layouts.student')

@section('title', __('student.profile.title'))
@section('heading', __('student.profile.title'))
@section('subheading', __('student.profile.subtitle'))

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="glass rounded-2xl p-4">
        @csrf
        @method('PUT')
        <div class="flex flex-wrap items-center gap-4">
            @if($user->avatar_path)
                <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="" class="h-16 w-16 rounded-full object-cover ring-2 ring-emerald-400/40">
            @else
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/20 text-xl font-bold text-emerald-300">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold text-white font-bn">{{ $user->name }}</p>
                <p class="text-[11px] text-gray-500">{{ $user->email }}</p>
            </div>
            <input type="file" name="avatar" accept="image/*" class="field max-w-[220px] text-[10px]">
        </div>

        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.profile.name') }}</label>
                <input name="name" value="{{ old('name', $user->name) }}" required maxlength="120" class="field font-bn">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.profile.phone') }}</label>
                <input name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" class="field">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.profile.bio') }}</label>
                <textarea name="bio" rows="3" maxlength="500" class="field font-bn">{{ old('bio', $user->bio) }}</textarea>
            </div>
        </div>

        <button type="submit" class="btn-emerald mt-3 rounded-lg px-5 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
    </form>

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-2 text-sm font-bold font-bn">{{ __('student.profile.subscription') }}</h3>
        @if($subscription)
            <div class="grid gap-2 text-xs font-bn sm:grid-cols-3">
                <div class="rounded-lg bg-white/5 p-2"><p class="text-[10px] text-gray-500">{{ __('student.subscription.plan') }}</p><p class="font-bold text-white">{{ $subscription->plan?->name }}</p></div>
                <div class="rounded-lg bg-white/5 p-2"><p class="text-[10px] text-gray-500">{{ __('student.subscription.started') }}</p><p class="font-bold text-white">{{ $subscription->starts_at?->format('d M Y') }}</p></div>
                <div class="rounded-lg bg-white/5 p-2"><p class="text-[10px] text-gray-500">{{ __('student.subscription.expires') }}</p><p class="font-bold text-white">{{ $subscription->ends_at?->format('d M Y') }}</p></div>
            </div>
        @else
            <p class="text-xs text-gray-500 font-bn">{{ __('student.subscription.inactive') }}</p>
            <a href="{{ route('student.subscription.show') }}" class="mt-2 inline-block text-xs text-emerald-400 font-bn">{{ __('student.subscription.activate') }} →</a>
        @endif
    </div>
</div>
@endsection
