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
            'name.required' => 'পুরো নাম লিখুন।',
            'email.required' => 'ইমেইল লিখুন।',
            'email.email' => 'সঠিক ইমেইল ঠিকানা লিখুন।',
            'email.unique' => 'এই ইমেইল দিয়ে আগে থেকেই অ্যাকাউন্ট আছে।',
            'password.required' => 'পাসওয়ার্ড লিখুন।',
            'password.min' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।',
            'mode.required' => 'আপনার ধরন নির্বাচন করুন।',
            'mode.in' => 'আপনার ধরন নির্বাচন করুন।',
        ]);

        $user = User::create($validated);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('toast', 'অ্যাকাউন্ট তৈরি হয়েছে! আপনার ড্যাশবোর্ডে স্বাগতম।');
    }
}
