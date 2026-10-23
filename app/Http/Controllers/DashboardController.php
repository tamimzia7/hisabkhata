<?php

namespace App\Http\Controllers;

use App\Models\User;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard for the authenticated user's mode.
     */
    public function __invoke(Request $request): View
    {
        if ($request->user()->mode === 'admin') {
            return view('admin.dashboard', [
                'users' => User::latest()->take(10)->get(),
                'totalUsers' => User::count(),
                'admin' => $request->user(),
            ]);
        }

        return view('dashboard');
    }

    /**
     * Show the admin profile page.
     */
    public function profile(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.dashboard', [
            'users' => User::latest()->take(10)->get(),
            'totalUsers' => User::count(),
            'admin' => $request->user(),
            'adminPage' => 'admin-profile',
        ]);
    }

    /**
     * Update the authenticated admin's profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'current_password' => [
                'nullable', 'string',
                function (string $attribute, mixed $value, Closure $fail) use ($request, $user): void {
                    if ($request->filled('password') && ! Hash::check((string) $value, $user->password)) {
                        $fail(__('site.admin.current_password_incorrect'));
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => __('site.auth.messages.name_required'),
            'email.required' => __('site.auth.messages.email_required'),
            'email.email' => __('site.auth.messages.email_invalid'),
            'email.unique' => __('site.auth.messages.email_taken'),
            'password.min' => __('site.auth.messages.password_min'),
            'password.confirmed' => __('site.admin.password_mismatch'),
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.profile')
            ->with('toast', __('site.admin.profile_updated'));
    }

    /**
     * Abort unless the authenticated user is an admin.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->mode === 'admin', 403);
    }
}
