@php($currentLocale = app()->getLocale())
<div class="flex items-center gap-1 glass rounded-full px-2 py-1">
    <a href="{{ route('lang.switch', 'bn') }}"
        class="px-2.5 py-1 text-xs font-bn rounded-full transition {{ $currentLocale === 'bn' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}"
        title="বাংলা">বাংলা</a>
    <a href="{{ route('lang.switch', 'en') }}"
        class="px-2.5 py-1 text-xs font-bn rounded-full transition {{ $currentLocale === 'en' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}"
        title="English">EN</a>
</div>
