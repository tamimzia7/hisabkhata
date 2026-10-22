<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new user and authenticate them.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'mode' => ['required', Rule::in(['student', 'personal', 'business'])],
        ], [
            'name.required' => __('site.auth.messages.name_required'),
            'email.required' => __('site.auth.messages.email_required'),
            'email.email' => __('site.auth.messages.email_invalid'),
            'email.unique' => __('site.auth.messages.email_taken'),
            'password.required' => __('site.auth.messages.password_required'),
            'password.min' => __('site.auth.messages.password_min'),
            'mode.required' => __('site.auth.messages.mode_required'),
            'mode.in' => __('site.auth.messages.mode_required'),
        ]);

        $user = User::create($validated);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('toast', __('site.auth.messages.account_created'));
    }
}
