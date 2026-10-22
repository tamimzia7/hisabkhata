<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate an existing user into the application.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => __('site.auth.messages.email_required'),
            'email.email' => __('site.auth.messages.email_invalid'),
            'password.required' => __('site.auth.messages.password_required'),
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('site.auth.messages.credentials_invalid'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))->with('toast', __('site.auth.messages.login_success'));
    }

    /**
     * Log the authenticated user out of the application.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('toast', __('site.auth.messages.logout_success'));
    }
}
