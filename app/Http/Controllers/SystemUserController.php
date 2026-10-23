<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SystemUserController extends Controller
{
    /**
     * Create a system user with one of the roles the admin may assign.
     */
    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $assignableRoles = $actor->assignableStaffRoles();

        abort_if($assignableRoles === [], 403);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in($assignableRoles)],
        ], [
            'name.required' => __('site.auth.messages.name_required'),
            'email.required' => __('site.auth.messages.email_required'),
            'email.email' => __('site.auth.messages.email_invalid'),
            'email.unique' => __('site.auth.messages.email_taken'),
            'password.required' => __('site.auth.messages.password_required'),
            'password.min' => __('site.auth.messages.password_min'),
            'role.in' => __('site.admin.role_not_allowed'),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('dashboard', ['page' => 'system-users'])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $staff = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'mode' => 'admin',
            'role' => $validated['role'] ?? User::ROLE_CUSTOMER_CARE,
        ]);

        $this->log($request, 'staff_created', $staff->name);

        return redirect()
            ->route('dashboard', ['page' => 'system-users'])
            ->with('toast', __('site.admin.staff_created'));
    }

    /**
     * Remove a system user. Developer accounts stay protected.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->canManageSystemUsers(), 403);
        abort_if($user->is($request->user()), 403);
        abort_unless(
            $user->isAdmin() && in_array($user->role, [User::ROLE_SUB_ADMIN, User::ROLE_CUSTOMER_CARE], true),
            403
        );

        $name = $user->name;

        $user->delete();

        $this->log($request, 'staff_removed', $name);

        return redirect()
            ->route('dashboard', ['page' => 'system-users'])
            ->with('toast', __('site.admin.staff_removed'));
    }

    /**
     * Record the action in the admin activity log.
     */
    private function log(Request $request, string $action, string $subject): void
    {
        AdminActivity::create([
            'user_id' => $request->user()->getKey(),
            'action' => $action,
            'subject' => $subject,
            'ip_address' => $request->ip(),
        ]);
    }
}
