@extends('layouts.app')

@section('title', $productName.' — '.__('subscription.title'))

@section('content')
<div class="min-h-screen flex relative z-10" x-data="{}">
    @include('partials.client-sidebar')

    <main class="flex-1 overflow-x-hidden p-3 md:p-4 lg:p-5">
        <div class="mx-auto max-w-6xl">
            @include('subscriptions._details')
        </div>
    </main>
</div>
@endsection
