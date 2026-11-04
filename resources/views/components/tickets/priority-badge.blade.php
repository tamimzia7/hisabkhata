@props(['priority'])

@php
    $styles = [
        'low' => 'bg-gray-500/10 text-gray-400 border-gray-500/25',
        'medium' => 'bg-sky-500/10 text-sky-400 border-sky-500/25',
        'high' => 'bg-orange-500/10 text-orange-400 border-orange-500/25',
        'urgent' => 'bg-red-500/10 text-red-400 border-red-500/25',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold font-bn '.($styles[$priority] ?? $styles['medium']),
]) }}>
    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
    </svg>
    {{ __('tickets.priorities.'.$priority) }}
</span>
