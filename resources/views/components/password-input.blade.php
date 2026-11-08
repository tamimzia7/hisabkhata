@props([
    'id',
    'name',
    'label' => null,
    'labelClass' => 'text-sm text-gray-300 font-bn',
    'inputClass' => 'w-full mt-1 glass rounded-lg px-3 sm:px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400/50',
    'wrapperClass' => '',
    'autocomplete' => 'current-password',
    'placeholder' => '••••••••',
])

@php
    $inputAttributes = $attributes->merge([
        'class' => trim($inputClass.' pr-10'),
    ]);
    $showLabel = __('site.auth.show_password');
    $hideLabel = __('site.auth.hide_password');
@endphp

<div x-data="{ passwordVisible: false }" class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $id }}" class="{{ $labelClass }}">{{ $label }}</label>
    @endif

    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            x-bind:type="passwordVisible ? 'text' : 'password'"
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            {{ $inputAttributes }}
        >

        <button
            type="button"
            @click="passwordVisible = !passwordVisible"
            :aria-label="passwordVisible ? @js($hideLabel) : @js($showLabel)"
            :title="passwordVisible ? @js($hideLabel) : @js($showLabel)"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 hover:text-emerald-400 transition-colors"
        >
            <svg x-show="!passwordVisible" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <circle cx="12" cy="12" r="3" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            <svg x-show="passwordVisible" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
            </svg>
        </button>
    </div>

    {{ $slot }}
</div>
