@props(['status'])

@php
    $styles = [
        'open' => 'bg-sky-500/10 text-sky-400 border-sky-500/25',
        'assigned' => 'bg-violet-500/10 text-violet-400 border-violet-500/25',
        'in_progress' => 'bg-amber-500/10 text-amber-400 border-amber-500/25',
        'waiting_for_client' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/25',
        'resolved' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/25',
        'closed' => 'bg-gray-500/10 text-gray-400 border-gray-500/25',
    ];
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold font-bn '.($styles[$status] ?? $styles['open']),
]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    {{ __('tickets.status.'.$status) }}
</span>
