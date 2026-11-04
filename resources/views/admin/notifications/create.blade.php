@extends('layouts.admin')

@section('title', __('notifications.create_title'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-5">
        <nav class="flex flex-wrap items-center gap-2 text-xs text-gray-400 font-bn" aria-label="Breadcrumb">
            <a href="{{ route('notifications.index') }}" class="transition hover:text-emerald-300">
                {{ __('notifications.title') }}
            </a>
            <svg class="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <span class="text-white font-bold">{{ __('notifications.create_title') }}</span>
        </nav>

        <div>
            <h1 class="text-xl font-bold font-display font-bn text-white">{{ __('notifications.create_title') }}</h1>
            <p class="mt-1 text-sm text-gray-400 font-bn">{{ __('notifications.create_subtitle') }}</p>
        </div>

        @include('notifications._form')
    </div>
@endsection
