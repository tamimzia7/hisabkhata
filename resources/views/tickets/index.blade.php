@php($viewer = auth()->user())
@extends($viewer->isSystemUser() ? 'layouts.admin' : 'layouts.app')

@section('title', __('tickets.title'))

@section('content')
    @if($viewer->isSystemUser())
        @include('tickets.partials._index-content')
    @else
        <x-tickets.client-shell max="max-w-6xl">
            @include('tickets.partials._index-content')
        </x-tickets.client-shell>
    @endif
@endsection
