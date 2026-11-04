@php($viewer = auth()->user())
@extends($viewer->isSystemUser() ? 'layouts.admin' : 'layouts.app')

@section('title', $ticket->ticket_number.' — '.__('tickets.title'))

@section('content')
    @if($viewer->isSystemUser())
        @include('tickets.partials._conversation')
    @else
        <x-tickets.client-shell :back="route('tickets.index')" max="max-w-6xl">
            @include('tickets.partials._conversation')
        </x-tickets.client-shell>
    @endif
@endsection
