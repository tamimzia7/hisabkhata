<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentSubscriptionController extends Controller
{
    /**
     * Show the Student/Mess subscription plans.
     */
    public function show(Request $request): View
    {
        $subscription = $request->user()->activeSubscription(SubscriptionPlan::PRODUCT_STUDENT);
        $plans = SubscriptionPlan::query()
            ->where('product', SubscriptionPlan::PRODUCT_STUDENT)
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('student.subscription', [
            'subscription' => $subscription,
            'plans' => $plans,
        ]);
    }

    /**
     * Activate a Student subscription (free trial or manual access plan).
     */
    public function activate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasActiveStudentSubscription()) {
            return redirect()->route('student.dashboard');
        }

        $validated = $request->validate([
            'plan' => ['nullable', 'string', 'max:80'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('product', SubscriptionPlan::PRODUCT_STUDENT)
            ->where('is_active', true)
            ->when(filled($validated['plan'] ?? null), fn ($query) => $query->where('slug', $validated['plan']))
            ->orderBy('price')
            ->firstOrFail();

        UserSubscription::create([
            'user_id' => $user->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'status' => UserSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays($plan->duration_days),
        ]);

        return redirect()
            ->route('student.dashboard')
            ->with('toast', __('student.subscription.activated'));
    }
}
