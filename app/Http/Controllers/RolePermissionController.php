<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    /**
     * Update the permission matrix for every admin role.
     */
    public function update(Request $request): RedirectResponse
    {
        $actor = $request->user();

        abort_unless($actor->role === User::ROLE_DEVELOPER, 403);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string', Rule::in(User::PERMISSIONS)],
        ]);

        $permissions = $validated['permissions'] ?? [];

        foreach (User::ROLES as $role) {
            $granted = (array) ($permissions[$role] ?? []);

            foreach (User::PERMISSIONS as $permission) {
                Setting::putBoolean(
                    User::permissionKey($role, $permission),
                    in_array($permission, $granted, true)
                );
            }
        }

        AdminActivity::create([
            'user_id' => $actor->getKey(),
            'action' => 'permission_updated',
            'subject' => __('site.admin.role_permissions'),
            'ip_address' => $request->ip(),
        ]);

        return redirect()
            ->route('dashboard', ['page' => 'roles-permission'])
            ->with('toast', __('site.admin.permission_saved'));
    }
}
