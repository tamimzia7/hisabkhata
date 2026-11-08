<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProductSubscription
{
    /**
     * Restrict product routes to users with an active entitlement for that product.
     */
    public function handle(Request $request, Closure $next, string $product = 'student'): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasActiveSubscription($product)) {
            if ($request->expectsJson()) {
                abort(403, __('subscription.required'));
            }

            return redirect()
                ->route('subscriptions.show', $product)
                ->with('toast', __('subscription.required'));
        }

        return $next($request);
    }
}
