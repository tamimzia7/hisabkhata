<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Mess;
use App\Services\DashboardService;
use App\Services\MessService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(private readonly MessService $messes) {}

    /**
     * Show the Student/Mess dashboard for the active workspace.
     */
    public function __invoke(Request $request, DashboardService $dashboard): View|RedirectResponse
    {
        $mess = $this->messes->activeMessFor($request->user(), session('student_mess_id'));

        if ($mess === null) {
            return redirect()->route('student.messes.index');
        }

        Gate::authorize('view', $mess);

        [$from, $to, $range] = $this->resolveRange($request);

        return view('student.dashboard', [
            'mess' => $mess,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'summary' => $dashboard->summary($mess, $from, $to),
        ]);
    }

    /**
     * Convert the requested quick range into concrete dates.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: string}
     */
    private function resolveRange(Request $request): array
    {
        $range = (string) $request->query('range', 'month');
        $now = CarbonImmutable::now();

        [$from, $to] = match ($range) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'previous_month' => [$now->subMonthNoOverflow()->startOfMonth(), $now->subMonthNoOverflow()->endOfMonth()],
            'custom' => $this->customRange($request, $now),
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        return [$from, $to, $range === 'custom' ? 'custom' : $range];
    }

    /**
     * Resolve a validated custom date range with sensible fallbacks.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function customRange(Request $request, CarbonImmutable $now): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = filled($validated['from'] ?? null)
            ? CarbonImmutable::parse($validated['from'])->startOfDay()
            : $now->startOfMonth();

        $to = filled($validated['to'] ?? null)
            ? CarbonImmutable::parse($validated['to'])->endOfDay()
            : $now->endOfMonth();

        return [$from, $to];
    }
}
