@extends('layouts.student')

@section('title', __('student.settings.title'))
@section('heading', __('student.settings.title'))
@section('subheading', $mess->name)

@section('content')
<div class="mx-auto max-w-3xl space-y-4">
    <form method="POST" action="{{ route('student.mess.settings.update', $mess) }}" class="glass rounded-2xl p-4">
        @csrf
        @method('PUT')
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
                <input name="contact_name" value="{{ old('contact_name', $mess->contact_name) }}" class="field font-bn">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.contact_phone') }}</label>
                <input name="contact_phone" value="{{ old('contact_phone', $mess->contact_phone) }}" class="field">
            </div>
            <div>
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.contact_email') }}</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $mess->contact_email) }}" class="field">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.address') }}</label>
                <input name="address" value="{{ old('address', $mess->address) }}" class="field font-bn">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-[11px] text-gray-400 font-bn">{{ __('student.mess.description') }}</label>
                <textarea name="description" rows="3" class="field font-bn">{{ old('description', $mess->description) }}</textarea>
            </div>
        </div>
        <button type="submit" class="btn-emerald mt-3 rounded-lg px-5 py-2 text-xs font-bold font-bn">{{ __('student.common.save') }}</button>
    </form>

    @if($mess->owner_id === auth()->id())
        <div class="glass rounded-2xl border border-red-500/20 p-4">
            <h3 class="text-sm font-bold text-red-300 font-bn">{{ __('student.settings.danger_zone') }}</h3>
            <p class="mt-1 text-[11px] text-gray-500 font-bn">{{ __('student.settings.delete_warning') }}</p>
            <form method="POST" action="{{ route('student.messes.destroy', $mess) }}" onsubmit="return confirm(@js(__('student.settings.confirm_delete')))" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg bg-red-500/15 px-4 py-2 text-xs font-bold text-red-300 font-bn">{{ __('student.settings.delete_mess') }}</button>
            </form>
        </div>
    @endif
</div>
@endsection
