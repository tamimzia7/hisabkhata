@php($currentLocale = app()->getLocale())
@php($compact = $compact ?? false)
<div class="flex items-center glass rounded-full {{ $compact ? 'gap-0.5 p-0.5' : 'gap-1 px-2 py-1' }}">
    <a href="{{ route('lang.switch', 'bn') }}"
        class="{{ $compact ? 'px-1.5 py-1 text-[10px]' : 'px-2.5 py-1 text-xs' }} font-bn rounded-full transition {{ $currentLocale === 'bn' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}"
        title="বাংলা">বাংলা</a>
    <a href="{{ route('lang.switch', 'en') }}"
        class="{{ $compact ? 'px-1.5 py-1 text-[10px]' : 'px-2.5 py-1 text-xs' }} font-bn rounded-full transition {{ $currentLocale === 'en' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}"
        title="English">EN</a>
</div>
