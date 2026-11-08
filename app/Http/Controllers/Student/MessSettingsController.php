<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\MessRequest;
use App\Models\Mess;
use App\Models\MessMember;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MessSettingsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Show the workspace settings form.
     */
    public function edit(Mess $mess): View
    {
        Gate::authorize('update', $mess);

        return view('student.settings.edit', ['mess' => $mess]);
    }

    /**
     * Update the workspace settings.
     */
    public function update(MessRequest $request, Mess $mess): RedirectResponse
    {
        $mess->update($request->validated());

        $this->audit->log($mess, $request->user(), 'mess.settings_updated', $mess, $request->validated(), $request->ip());

        return back()->with('toast', __('student.mess.updated'));
    }

    /**
     * Show member permission overrides.
     */
    public function permissions(Mess $mess): View
    {
        Gate::authorize('managePermissions', $mess);

        return view('student.settings.permissions', [
            'mess' => $mess,
            'members' => $mess->members()->orderBy('name')->get(),
            'abilities' => [
                'manage_members' => __('student.permissions.abilities.manage_members'),
                'manage_meals' => __('student.permissions.abilities.manage_meals'),
                'manage_bazar' => __('student.permissions.abilities.manage_bazar'),
                'manage_finance' => __('student.permissions.abilities.manage_finance'),
                'manage_settlements' => __('student.permissions.abilities.manage_settlements'),
                'view_reports' => __('student.permissions.abilities.view_reports'),
            ],
        ]);
    }

    /**
     * Persist member permission overrides.
     */
    public function updatePermissions(Request $request, Mess $mess): RedirectResponse
    {
        Gate::authorize('managePermissions', $mess);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'in:manager,accountant,member'],
        ]);

        $members = $mess->members()->get();

        foreach ($members as $member) {
            if ($member->role === MessMember::ROLE_OWNER) {
                continue;
            }

            $role = $validated['roles'][$member->getKey()] ?? $member->role;

            if (in_array($role, [MessMember::ROLE_MANAGER, MessMember::ROLE_ACCOUNTANT, MessMember::ROLE_MEMBER], true)) {
                $member->role = $role;
            }

            $abilities = $validated['permissions'][$member->getKey()] ?? [];
            $member->permissions = collect($abilities)
                ->mapWithKeys(fn ($value, $key): array => [(string) $key => (bool) $value])
                ->all();

            $member->save();
        }

        $this->audit->log($mess, $request->user(), 'permissions.updated', null, [], $request->ip());

        return back()->with('toast', __('student.permissions.updated'));
    }
}
