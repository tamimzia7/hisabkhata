<nav x-data="{ active: window.location.hash || '#top' }" aria-label="{{ __('site.nav.mobile_label') }}"
    class="md:hidden fixed inset-x-0 bottom-0 z-50 glass-nav border-t border-emerald-500/15"
    style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-5">
        <a href="#top" @click="active = '#top'"
            :class="active === '#top' ? 'text-emerald-400' : 'text-gray-400'"
            class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
            <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                :class="active === '#top' ? 'opacity-100' : 'opacity-0'"></span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5 12 3l9 7.5M5.25 9.75V21h13.5V9.75" />
            </svg>
            <span>{{ __('site.nav.home') }}</span>
        </a>
        <a href="#showcase" @click="active = '#showcase'"
            :class="active === '#showcase' ? 'text-emerald-400' : 'text-gray-400'"
            class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
            <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                :class="active === '#showcase' ? 'opacity-100' : 'opacity-0'"></span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
            </svg>
            <span>{{ __('site.nav.showcase_short') }}</span>
        </a>
        <a href="#features" @click="active = '#features'"
            :class="active === '#features' ? 'text-emerald-400' : 'text-gray-400'"
            class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
            <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                :class="active === '#features' ? 'opacity-100' : 'opacity-0'"></span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3v3m0 12v3M3 12h3m12 0h3M5.6 5.6l2.1 2.1m8.6 8.6 2.1 2.1m0-12.8-2.1 2.1m-8.6 8.6-2.1 2.1" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <span>{{ __('site.nav.features_short') }}</span>
        </a>
        <a href="#pricing" @click="active = '#pricing'"
            :class="active === '#pricing' ? 'text-emerald-400' : 'text-gray-400'"
            class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
            <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                :class="active === '#pricing' ? 'opacity-100' : 'opacity-0'"></span>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M20.25 13.5 13.5 20.25a2.25 2.25 0 0 1-3.182 0L3.75 13.5V3.75h9.75l6.75 6.75a2.25 2.25 0 0 1 0 3z" />
                <circle cx="7.5" cy="7.5" r="1" />
            </svg>
            <span>{{ __('site.nav.pricing_short') }}</span>
        </a>
        @auth
            <a href="{{ route('dashboard') }}" @click="active = 'account'"
                :class="active === 'account' ? 'text-emerald-400' : 'text-gray-400'"
                class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
                <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                    :class="active === 'account' ? 'opacity-100' : 'opacity-0'"></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3.25" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 20.25a6.75 6.75 0 0 1 13.5 0" />
                </svg>
                <span>{{ __('site.nav.dashboard_short') }}</span>
            </a>
        @else
            <a href="{{ route('login') }}" @click="active = 'account'"
                :class="active === 'account' ? 'text-emerald-400' : 'text-gray-400'"
                class="relative flex flex-col items-center justify-center gap-1 py-2 text-[10px] leading-none font-bn transition-colors">
                <span class="absolute top-0 h-0.5 w-8 rounded-full bg-emerald-400 transition-opacity"
                    :class="active === 'account' ? 'opacity-100' : 'opacity-0'"></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="3.25" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 20.25a6.75 6.75 0 0 1 13.5 0" />
                </svg>
                <span>{{ __('site.nav.login') }}</span>
            </a>
        @endauth
    </div>
</nav>
