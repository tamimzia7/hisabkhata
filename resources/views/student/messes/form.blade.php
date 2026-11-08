@extends('layouts.student')

@php($editing = $mess->exists)

@section('title', $editing ? __('student.mess.edit') : __('student.mess.create_title'))
@section('heading', $editing ? __('student.mess.edit') : __('student.mess.create_title'))
@section('subheading', $editing ? $mess->name : __('student.mess.create_subtitle'))

@section('content')
<form method="POST" action="{{ $editing ? route('student.messes.update', $mess) : route('student.messes.store') }}" class="mx-auto max-w-3xl space-y-4">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="glass rounded-2xl p-4">
        <h3 class="mb-3 text-sm font-bold font-bn">{{ __('student.mess.information') }}</h3>
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.name') }} *</label>
                <input name="name" value="{{ old('name', $mess->name) }}" required maxlength="120" class="field font-bn">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.start_date') }}</label>
                <input type="date" name="start_date" value="{{ old('start_date', $mess->start_date?->toDateString()) }}" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.billing_month') }}</label>
                <input type="month" name="billing_month" value="{{ old('billing_month', $mess->billing_month) }}" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.monthly_budget') }}</label>
                <input type="number" step="0.01" min="0" name="monthly_budget" value="{{ old('monthly_budget', $mess->monthly_budget) }}" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.contact_name') }}</label>
                <input name="contact_name" value="{{ old('contact_name', $mess->contact_name) }}" maxlength="120" class="field font-bn">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.contact_phone') }}</label>
                <input name="contact_phone" value="{{ old('contact_phone', $mess->contact_phone) }}" maxlength="30" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.contact_email') }}</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $mess->contact_email) }}" class="field">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.address') }}</label>
                <input name="address" value="{{ old('address', $mess->address) }}" maxlength="255" class="field font-bn">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.description') }}</label>
                <textarea name="description" rows="3" maxlength="2000" class="field font-bn">{{ old('description', $mess->description) }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2">
        <a href="{{ $editing ? route('student.settings.edit', $mess) : route('student.messes.index') }}" class="btn-ghost rounded-lg px-4 py-2 text-xs font-bold text-gray-300 font-bn">{{ __('student.common.cancel') }}</a>
        <button type="submit" class="btn-emerald rounded-lg px-5 py-2 text-sm font-bold font-bn">{{ $editing ? __('student.common.save') : __('student.mess.create') }}</button>
    </div>
</form>
@endsection
