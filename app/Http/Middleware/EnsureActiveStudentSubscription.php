<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveStudentSubscription
{
    /**
     * Restrict Student/Mess routes to users with an active Student subscription.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasActiveStudentSubscription()) {
            if ($request->expectsJson()) {
                abort(403, __('student.subscription.required'));
            }

            return redirect()
                ->route('student.subscription.show')
                ->with('toast', __('student.subscription.required'));
        }

        return $next($request);
    }
}
