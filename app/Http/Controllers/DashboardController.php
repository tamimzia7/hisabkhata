<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            ]);
        }

        return view('dashboard');
    }
}
