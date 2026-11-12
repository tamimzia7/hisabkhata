@extends('layouts.student')

@section('title', $productName.' — '.__('subscription.title'))

@section('content')
<div class="mx-auto max-w-7xl">
    @include('subscriptions._details')
</div>
@endsection
