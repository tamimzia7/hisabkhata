<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MessMember;
use App\Services\DashboardService;
use App\Services\MessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
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

        $member = $mess->memberFor($request->user());
        abort_if($member === null, 403);

        $month = $this->month($request);
        $search = trim((string) $request->query('q', ''));
        $summary = $dashboard->summary($mess, $month, $member);

        $rows = $summary['members'];

        if ($member->role === MessMember::ROLE_MEMBER) {
            $rows = $rows->filter(fn (array $row): bool => (int) $row['member']?->getKey() === (int) $member->getKey());
        }

        if ($search !== '') {
            $rows = $rows->filter(function (array $row) use ($search): bool {
                $needle = mb_strtolower($search);

                return str_contains(mb_strtolower((string) $row['member']?->name), $needle)
                    || str_contains(mb_strtolower((string) $row['member']?->email), $needle);
            });
        }

        return view('student.dashboard', [
            'mess' => $mess,
            'member' => $member,
            'month' => $month,
            'search' => $search,
            'summary' => $summary,
            'overview' => $this->paginate($rows->values(), $request, $month, $search),
            'memberships' => $request->user()->messMemberships()->with('mess')->get(),
            'subscription' => $request->user()->activeSubscription(),
        ]);
    }

    /**
     * Build the member overview paginator.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function paginate(Collection $rows, Request $request, string $month, string $search): LengthAwarePaginator
    {
        $perPage = 8;
        $page = max(1, (int) $request->integer('page', 1));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => route('student.dashboard'),
                'query' => array_filter([
                    'month' => $month,
                    'q' => $search !== '' ? $search : null,
                ], fn ($value): bool => $value !== null && $value !== ''),
            ],
        );
    }

    /**
     * Validate the selected billing month.
     */
    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
