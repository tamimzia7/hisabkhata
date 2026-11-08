<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MessRequest;
use App\Models\Mess;
use App\Services\AuditLogger;
use App\Services\MessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessController extends Controller
{
    public function __construct(
        private readonly MessService $messes,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * List every workspace the user belongs to.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $memberships = $user->messMemberships()
            ->with('mess')
            ->orderByDesc('id')
            ->get();

        $plans = $user->activeSubscription()?->plan;

        return view('student.messes.index', [
            'memberships' => $memberships,
            'ownedCount' => $user->ownedMesses()->count(),
            'maxMesses' => (int) data_get($plans?->features, 'max_messes', 1),
        ]);
    }

    /**
     * Show the workspace creation form.
     */
    public function create(Request $request): View
    {
        Gate::authorize('viewAny', Mess::class);

        $subscription = $request->user()->activeSubscription();
        $maxMesses = (int) data_get($subscription?->plan?->features, 'max_messes', 1);
        $ownedCount = $request->user()->ownedMesses()->count();

        abort_if($maxMesses > 0 && $ownedCount >= $maxMesses, 403, __('student.mess.limit_reached'));

        return view('student.messes.form', ['mess' => new Mess]);
    }

    /**
     * Create a workspace with its owner member and default accounts.
     */
    public function store(MessRequest $request): RedirectResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription();
        $maxMesses = (int) data_get($subscription?->plan?->features, 'max_messes', 1);

        abort_if($maxMesses > 0 && $user->ownedMesses()->count() >= $maxMesses, 403, __('student.mess.limit_reached'));

        $mess = $this->messes->create($user, $request->validated());

        $this->audit->log($mess, $user, 'mess.created', $mess, ['name' => $mess->name], $request->ip());

        session(['student_mess_id' => $mess->getKey()]);

        return redirect()
            ->route('student.dashboard')
            ->with('toast', __('student.mess.created'));
    }

    /**
     * Show the workspace settings form.
     */
    public function edit(Mess $mess): View
    {
        Gate::authorize('update', $mess);

        return view('student.messes.form', ['mess' => $mess]);
    }

    /**
     * Update the workspace details.
     */
    public function update(MessRequest $request, Mess $mess): RedirectResponse
    {
        $mess->update($request->validated());

        $this->audit->log($mess, $request->user(), 'mess.updated', $mess, $request->validated(), $request->ip());

        return redirect()
            ->route('student.settings.edit', $mess)
            ->with('toast', __('student.mess.updated'));
    }

    /**
     * Delete a workspace and all of its records.
     */
    public function destroy(Request $request, Mess $mess): RedirectResponse
    {
        Gate::authorize('delete', $mess);

        $name = $mess->name;

        DB::transaction(fn () => $mess->delete());

        return redirect()
            ->route('student.messes.index')
            ->with('toast', __('student.mess.deleted', ['name' => $name]));
    }

    /**
     * Switch the active workspace.
     */
    public function switch(Request $request, Mess $mess): RedirectResponse
    {
        Gate::authorize('view', $mess);

        session(['student_mess_id' => $mess->getKey()]);

        return redirect()->route('student.dashboard');
    }
}
