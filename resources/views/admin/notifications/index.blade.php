@extends('layouts.admin')

@section('title', __('notifications.title'))

@section('content')
    @include('notifications._list')
@endsection
