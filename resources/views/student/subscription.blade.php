@extends('layouts.student')

@section('title', $productName.' — '.__('subscription.title'))
@section('heading', $productName)
@section('subheading', __('subscription.subtitle'))

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-gray-400 font-bn">{{ __('subscription.products.student') }}</p>
        <a href="{{ route('subscriptions.index') }}" class="glass rounded-lg px-4 py-2 text-xs text-gray-300 transition hover:bg-white/10 font-bn">{{ __('subscription.view_all') }}</a>
    </div>

    @include('subscriptions._details')
</div>
@endsection
