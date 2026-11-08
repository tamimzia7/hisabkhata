<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MealEntryRequest;
use App\Models\MealEntry;
use App\Models\Mess;
use App\Services\AuditLogger;
use App\Services\SettlementService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MealEntryController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SettlementService $settlements,
    ) {}

    /**
     * List meal entries and the monthly member summary.
     */
    public function index(Request $request, Mess $mess): View
    {
        Gate::authorize('view', $mess);

        $month = $this->month($request);
        $start = CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $end = $start->endOfMonth();

        $query = $mess->mealEntries()
            ->with('member')
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->when($request->filled('member'), fn ($query) => $query->where('mess_member_id', $request->integer('member')))
            ->latest('entry_date')
            ->latest('id');

        $summary = $mess->mealEntries()
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('mess_member_id, SUM(breakfast) as breakfast, SUM(lunch) as lunch, SUM(dinner) as dinner, SUM(total_meals) as total')
            ->groupBy('mess_member_id')
            ->get()
            ->keyBy('mess_member_id');

        return view('student.meals.index', [
            'mess' => $mess,
            'month' => $month,
            'entries' => $query->paginate(30)->withQueryString(),
            'summary' => $summary,
            'members' => $mess->activeMembers()->orderBy('name')->get(),
            'locked' => $this->settlements->isLocked($mess, $month),
            'totals' => [
                'breakfast' => round((float) $summary->sum('breakfast'), 2),
                'lunch' => round((float) $summary->sum('lunch'), 2),
                'dinner' => round((float) $summary->sum('dinner'), 2),
                'total' => round((float) $summary->sum('total'), 2),
            ],
        ]);
    }

    /**
     * Record a meal entry.
     */
    public function store(MealEntryRequest $request, Mess $mess): RedirectResponse
    {
        $this->authorizeEntry($request, $mess, (int) $request->validated('mess_member_id'));
        $this->settlements->ensureMonthOpen($mess, $request->validated('entry_date'), 'entry_date');

        $entry = $mess->mealEntries()->create([
            ...$request->validated(),
            'created_by' => $request->user()->getKey(),
        ]);

        $this->audit->log($mess, $request->user(), 'meal.created', $entry, $request->validated(), $request->ip());

        return back()->with('toast', __('student.meals.created'));
    }

    /**
     * Update a meal entry.
     */
    public function update(MealEntryRequest $request, Mess $mess, MealEntry $entry): RedirectResponse
    {
        $this->ensureBelongs($mess, $entry);
        $this->authorizeEntry($request, $mess, $entry->mess_member_id);
        $this->settlements->ensureMonthOpen($mess, $entry->entry_date->toDateString(), 'entry_date');
        $this->settlements->ensureMonthOpen($mess, $request->validated('entry_date'), 'entry_date');

        $entry->update($request->validated());

        $this->audit->log($mess, $request->user(), 'meal.updated', $entry, $request->validated(), $request->ip());

        return back()->with('toast', __('student.meals.updated'));
    }

    /**
     * Delete a meal entry.
     */
    public function destroy(Request $request, Mess $mess, MealEntry $entry): RedirectResponse
    {
        $this->ensureBelongs($mess, $entry);
        $this->authorizeEntry($request, $mess, $entry->mess_member_id);
        $this->settlements->ensureMonthOpen($mess, $entry->entry_date->toDateString(), 'entry_date');

        $this->audit->log($mess, $request->user(), 'meal.deleted', $entry, ['member_id' => $entry->mess_member_id], $request->ip());
        $entry->delete();

        return back()->with('toast', __('student.meals.deleted'));
    }

    /**
     * Managers may edit any entry; members may only edit their own.
     */
    private function authorizeEntry(Request $request, Mess $mess, int $memberId): void
    {
        if ($request->user()->can('manageMeals', $mess)) {
            return;
        }

        $own = $mess->memberFor($request->user());

        abort_unless($own !== null && $own->getKey() === $memberId, 403);
    }

    private function ensureBelongs(Mess $mess, MealEntry $entry): void
    {
        abort_unless($entry->mess_id === $mess->getKey(), 404);
    }

    private function month(Request $request): string
    {
        $month = (string) $request->query('month', now()->format('Y-m'));

        return preg_match('/^\d{4}-\d{2}$/', $month) === 1 ? $month : now()->format('Y-m');
    }
}
