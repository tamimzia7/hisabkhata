<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Mess;
use App\Models\MonthlySettlement;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Report types supported by the module.
     *
     * @var list<string>
     */
    public const TYPES = [
        'daily-meal',
        'monthly-meal',
        'member-meal',
        'bazar',
        'deposit',
        'balance',
        'settlement',
        'income-expense',
    ];

    public function __construct(private readonly SettlementService $settlements) {}

    /**
     * Show the report catalogue.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('viewReports', $mess);

        return view('student.reports.index', [
            'mess' => $mess,
            'month' => $this->month($request),
        ]);
    }

    /**
     * Render or export one report.
     */
    public function show(Request $request, Mess $mess, string $type): View|StreamedResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);
        Gate::authorize('viewReports', $mess);

        $month = $this->month($request);
        $report = $this->build($request, $mess, $type, $month);

        if ($request->query('format') === 'csv') {
            return $this->csv($type, $report['columns'], $report['rows']);
        }

        return view('student.reports.show', [
            'mess' => $mess,
            'type' => $type,
            'month' => $month,
            'report' => $report,
        ]);
    }

    /**
     * Build a report definition with columns and rows.
     *
     * @return array{title: string, columns: list<string>, rows: Collection<int, array<int, string|float|null>>, summary: array<string, float>}
     */
    private function build(Request $request, Mess $mess, string $type, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        return match ($type) {
            'daily-meal' => $this->dailyMealReport($mess, $start, $end),
            'monthly-meal' => $this->monthlyMealReport($mess, $start, $end),
            'member-meal' => $this->memberMealReport($request, $mess, $start, $end),
            'bazar' => $this->bazarReport($mess, $start, $end),
            'deposit' => $this->depositReport($mess, $start, $end),
            'balance' => $this->balanceReport($mess, $month),
            'settlement' => $this->settlementReport($mess, $month),
            'income-expense' => $this->incomeExpenseReport($mess, $start, $end),
        };
    }

    private function dailyMealReport(Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $mess->mealEntries()
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('entry_date, SUM(breakfast) as breakfast, SUM(lunch) as lunch, SUM(dinner) as dinner, SUM(total_meals) as total')
            ->groupBy('entry_date')
            ->orderBy('entry_date')
            ->get()
            ->map(fn ($row): array => [
                $row->entry_date,
                round((float) $row->breakfast, 2),
                round((float) $row->lunch, 2),
                round((float) $row->dinner, 2),
                round((float) $row->total, 2),
            ]);

        return [
            'title' => __('student.reports.types.daily_meal'),
            'columns' => [__('student.reports.columns.date'), __('student.meals.breakfast'), __('student.meals.lunch'), __('student.meals.dinner'), __('student.reports.columns.total')],
            'rows' => $rows,
            'summary' => ['total' => round((float) $rows->sum(fn (array $row): float => $row[4]), 2)],
        ];
    }

    private function monthlyMealReport(Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $mess->mealEntries()
            ->with('member')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy('mess_member_id')
            ->map(function (Collection $group): array {
                return [
                    $group->first()->member?->name ?? '—',
                    round((float) $group->sum('breakfast'), 2),
                    round((float) $group->sum('lunch'), 2),
                    round((float) $group->sum('dinner'), 2),
                    round((float) $group->sum('total_meals'), 2),
                ];
            })
            ->values();

        return [
            'title' => __('student.reports.types.monthly_meal'),
            'columns' => [__('student.members.name'), __('student.meals.breakfast'), __('student.meals.lunch'), __('student.meals.dinner'), __('student.reports.columns.total')],
            'rows' => $rows,
            'summary' => ['total' => round((float) $rows->sum(fn (array $row): float => $row[4]), 2)],
        ];
    }

    private function memberMealReport(Request $request, Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $memberId = $request->integer('member');

        $query = $mess->mealEntries()
            ->with('member')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->when($memberId > 0, fn ($query) => $query->where('mess_member_id', $memberId))
            ->orderBy('entry_date');

        $rows = $query->get()->map(fn ($entry): array => [
            $entry->entry_date?->toDateString(),
            $entry->member?->name ?? '—',
            round((float) $entry->breakfast, 2),
            round((float) $entry->lunch, 2),
            round((float) $entry->dinner, 2),
            round((float) $entry->total_meals, 2),
        ]);

        return [
            'title' => __('student.reports.types.member_meal'),
            'columns' => [__('student.reports.columns.date'), __('student.members.name'), __('student.meals.breakfast'), __('student.meals.lunch'), __('student.meals.dinner'), __('student.reports.columns.total')],
            'rows' => $rows,
            'summary' => ['total' => round((float) $rows->sum(fn (array $row): float => (float) $row[5]), 2)],
        ];
    }

    private function bazarReport(Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = $mess->bazarRecords()
            ->with(['purchaser', 'items'])
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('purchase_date')
            ->get()
            ->map(fn ($record): array => [
                $record->purchase_date?->toDateString(),
                $record->category,
                $record->purchaser?->name ?? '—',
                $record->items->pluck('name')->implode(', '),
                round((float) $record->total_amount, 2),
            ]);

        return [
            'title' => __('student.reports.types.bazar'),
            'columns' => [__('student.reports.columns.date'), __('student.bazar.category'), __('student.bazar.purchased_by'), __('student.bazar.items'), __('student.reports.columns.total')],
            'rows' => $rows,
            'summary' => ['total' => round((float) $rows->sum(fn (array $row): float => (float) $row[4]), 2)],
        ];
    }

    private function depositReport(Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $deposits = $mess->deposits()->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])->get();

        $rows = $mess->members()
            ->orderBy('name')
            ->get()
            ->map(function ($member) use ($deposits): array {
                $rows = $deposits->where('mess_member_id', $member->getKey());

                return [
                    $member->name,
                    round((float) $rows->where('type', 'deposit')->sum('amount'), 2),
                    round((float) $rows->where('type', 'refund')->sum('amount'), 2),
                    round((float) $rows->where('type', 'adjustment')->sum('amount'), 2),
                    round((float) $rows->sum(fn ($deposit): float => $deposit->signedAmount()), 2),
                ];
            });

        return [
            'title' => __('student.reports.types.deposit'),
            'columns' => [__('student.members.name'), __('student.deposits.deposit'), __('student.deposits.refund'), __('student.deposits.adjustment'), __('student.reports.columns.net')],
            'rows' => $rows,
            'summary' => ['total' => round((float) $rows->sum(fn (array $row): float => (float) $row[4]), 2)],
        ];
    }

    private function balanceReport(Mess $mess, string $month): array
    {
        $preview = $this->settlements->preview($mess, $month);

        $rows = $preview['members']->map(fn (array $row): array => [
            $row['member']->name,
            $row['meals'],
            $row['meal_cost'],
            $row['deposits'],
            $row['other_allocated'],
            $row['balance'],
        ]);

        return [
            'title' => __('student.reports.types.balance'),
            'columns' => [__('student.members.name'), __('student.reports.columns.meals'), __('student.reports.columns.meal_cost'), __('student.reports.columns.deposits'), __('student.reports.columns.other'), __('student.reports.columns.balance')],
            'rows' => $rows,
            'summary' => ['balance' => round((float) $rows->sum(fn (array $row): float => (float) $row[5]), 2)],
        ];
    }

    private function settlementReport(Mess $mess, string $month): array
    {
        $settlement = $mess->settlements()
            ->where('month', $month)
            ->where('status', MonthlySettlement::STATUS_FINALIZED)
            ->latest('version')
            ->first();

        if ($settlement === null) {
            return [
                'title' => __('student.reports.types.settlement'),
                'columns' => [__('student.reports.columns.item'), __('student.reports.columns.value')],
                'rows' => collect(),
                'summary' => [],
                'empty' => __('student.reports.no_settlement'),
            ];
        }

        $rows = $settlement->details()
            ->with('member')
            ->get()
            ->map(fn ($detail): array => [
                $detail->member?->name ?? '—',
                $detail->meals,
                $detail->meal_cost,
                $detail->deposits,
                $detail->other_allocated,
                $detail->balance,
            ]);

        return [
            'title' => __('student.reports.types.settlement').' — '.$settlement->month.' v'.$settlement->version,
            'columns' => [__('student.members.name'), __('student.reports.columns.meals'), __('student.reports.columns.meal_cost'), __('student.reports.columns.deposits'), __('student.reports.columns.other'), __('student.reports.columns.balance')],
            'rows' => $rows,
            'summary' => [
                'meal_rate' => (float) $settlement->meal_rate,
                'surplus' => (float) $settlement->total_surplus,
            ],
        ];
    }

    private function incomeExpenseReport(Mess $mess, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = collect();

        for ($cursor = $start->startOfMonth(); $cursor <= $end; $cursor = $cursor->addMonth()) {
            $from = $cursor->startOfMonth()->toDateString();
            $to = $cursor->endOfMonth()->toDateString();

            $income = (float) $mess->deposits()
                ->whereBetween('deposit_date', [$from, $to])
                ->get()
                ->sum(fn ($deposit): float => $deposit->signedAmount());
            $bazar = (float) $mess->bazarRecords()->whereBetween('purchase_date', [$from, $to])->sum('total_amount');
            $expenses = (float) $mess->expenses()->whereBetween('expense_date', [$from, $to])->sum('amount');

            $rows->push([
                $cursor->format('M Y'),
                round($income, 2),
                round($bazar, 2),
                round($expenses, 2),
                round($income - $bazar - $expenses, 2),
            ]);
        }

        return [
            'title' => __('student.reports.types.income_expense'),
            'columns' => [__('student.reports.columns.month'), __('student.reports.columns.income'), __('student.reports.columns.bazar'), __('student.reports.columns.expenses'), __('student.reports.columns.surplus')],
            'rows' => $rows,
            'summary' => [],
        ];
    }

    /**
     * Stream a report as Excel-friendly CSV.
     *
     * @param  list<string>  $columns
     */
    private function csv(string $type, array $columns, Collection $rows): StreamedResponse
    {
        $filename = 'hisabkhata-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($columns, $rows): void {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $columns);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
