@extends('layouts.student')

@section('title', $report['title'])
@section('heading', $report['title'])
@section('subheading', $mess->name.' · '.$month)

@section('content')
<div class="space-y-4">
    <div class="glass flex flex-wrap items-center justify-between gap-2 rounded-xl p-3">
        <a href="{{ route('student.mess.reports.index', [$mess, 'month' => $month]) }}" class="btn-ghost rounded-lg px-3 py-2 text-[11px] text-gray-200 font-bn">← {{ __('student.common.back') }}</a>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('student.mess.reports.show', [$mess, $type, 'month' => $month, 'format' => 'csv']) }}" class="btn-ghost rounded-lg px-3 py-2 text-[11px] text-emerald-300 font-bn">⤓ {{ __('student.reports.export_csv') }}</a>
            <button onclick="window.print()" class="btn-emerald rounded-lg px-3 py-2 text-[11px] font-bold font-bn">🖨 {{ __('student.reports.print') }}</button>
        </div>
    </div>

    <div class="glass-strong rounded-2xl p-5" id="report-print">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-2 border-b border-white/10 pb-3">
            <div>
                <h2 class="text-base font-bold font-display font-bn text-white">{{ $mess->name }}</h2>
                <p class="text-[11px] text-gray-400 font-bn">{{ $report['title'] }} · {{ $month }}</p>
            </div>
            <p class="text-[10px] text-gray-500 font-bn">{{ __('student.reports.generated') }}: {{ now()->format('d M Y H:i') }}</p>
        </div>

        @if(! empty($report['summary']))
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach($report['summary'] as $key => $value)
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-[10px] text-emerald-300 font-bn">{{ __('student.reports.columns.'.$key) }}: <b>{{ is_numeric($value) ? (floor((float) $value) == (float) $value ? localize_number((float) $value) : localize_money($value)) : $value }}</b></span>
                @endforeach
            </div>
        @endif

        @if(! empty($report['empty']))
            <p class="py-8 text-center text-sm text-gray-500 font-bn">{{ $report['empty'] }}</p>
        @else
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach($report['columns'] as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['rows'] as $row)
                            <tr>
                                @foreach($row as $cell)
                                    <td>{{ is_numeric($cell) ? (floor((float) $cell) == (float) $cell ? localize_number((float) $cell) : localize_money($cell)) : $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($report['columns']) }}" class="py-6 text-center text-gray-500 font-bn">{{ __('student.common.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<style>@media print { body * { visibility: hidden; } #report-print, #report-print * { visibility: visible; } #report-print { position: absolute; inset: 0; } }</style>
@endsection
