@props(['count' => null, 'variant' => 'admin'])

@php
    $unread = $count ?? (auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
    $active = request()->routeIs('notifications.*');
    $isAdminChild = $variant === 'admin-child';
    $isClientChild = $variant === 'client-child';
    $isChild = $isAdminChild || $isClientChild;
@endphp

<a
    href="{{ route('notifications.index') }}"
    title="{{ __('notifications.sidebar_label') }}"
    @if($active) aria-current="page" @endif
    @class([
        'nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg font-bn text-sm' => $variant === 'admin',
        'nav-active' => $variant === 'admin' && $active,
        'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-bn transition-colors' => $variant === 'client' || $isClientChild,
        'bg-emerald-500/20 text-emerald-300' => ($variant === 'client' || $isClientChild) && $active,
        'text-gray-300 hover:bg-white/5' => ($variant === 'client' || $isClientChild) && ! $active,
        'nav-child flex items-center gap-2 px-3 py-2 rounded-r-lg text-sm font-bn' => $isAdminChild,
        'active' => $isAdminChild && $active,
        'text-gray-400' => ($variant === 'admin' || $isAdminChild) && ! $active,
    ])
>
    <svg class="{{ $isChild ? 'w-4 h-4' : 'w-5 h-5' }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
    </svg>

    <span class="{{ $isChild ? '' : 'nav-label' }}">{{ __('notifications.sidebar_label') }}</span>

    @if($unread > 0)
        <span class="{{ $isChild ? 'ml-auto' : 'nav-label ml-auto' }} text-xs font-bold font-bn text-emerald-400 bg-emerald-500/15 px-2 py-0.5 rounded-full">
            {{ $unread > 99 ? '99+' : localize_digits($unread) }}
        </span>
    @endif
</a>
