@props(['href', 'icon', 'label', 'active' => false, 'badge' => null])

<a {{ $attributes->merge([
    'href' => $href,
    'class' => 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-bn transition '
        .($active ? 'bg-emerald-500/20 text-emerald-300' : 'text-gray-400 hover:bg-white/5 hover:text-white'),
]) }}
   @if($active) aria-current="page" @endif
   :class="collapsed ? 'md:justify-center md:px-2' : ''">
    <i data-lucide="{{ $icon }}" class="h-[18px] w-[18px] shrink-0"></i>
    <span class="truncate" :class="collapsed ? 'md:hidden' : ''">{{ $label }}</span>

    @if($badge !== null && (int) $badge > 0)
        <span class="ml-auto rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-400"
              :class="collapsed ? 'md:hidden' : ''">
            {{ $badge > 99 ? '99+' : localize_digits($badge) }}
        </span>
    @endif
</a>
