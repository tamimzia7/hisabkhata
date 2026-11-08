<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SslCommerzGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptions,
        private SslCommerzGateway $gateway,
    ) {}

    /**
     * Suspend an active subscription and record an audit trail entry.
     */
    public function suspend(Request $request, UserSubscription $subscription): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->subscriptions->suspend(
            $subscription,
            $admin,
            $validated['reason'] ?? null,
            $request->ip(),
        );

        return $this->backToSubscriptions(__('subscription.admin.subscription_suspended'));
    }

    /**
     * Reactivate a suspended subscription and record an audit trail entry.
     */
    public function reactivate(Request $request, UserSubscription $subscription): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $this->subscriptions->reactivate($subscription, $admin, $request->ip());

        return $this->backToSubscriptions(__('subscription.admin.subscription_reactivated'));
    }

    /**
     * Update the price and availability of a subscription plan.
     */
    public function updatePlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $plan->update([
            'name' => $validated['name'] ?? $plan->name,
            'price' => $validated['price'],
            'duration_days' => $validated['duration_days'] ?? $plan->duration_days,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $plan->is_active,
        ]);

        $this->subscriptions->logAdminActivity(
            $admin,
            'subscription_plan_updated',
            sprintf('%s (৳%s, %d days)', $plan->slug, number_format((float) $plan->price, 2), $plan->duration_days),
            $request->ip(),
        );

        return redirect()
            ->route('dashboard', ['page' => 'admin-pricing'])
            ->with('toast', __('subscription.admin.plan_updated'));
    }

    /**
     * Securely store the SSLCommerz merchant credentials.
     */
    public function updateGateway(Request $request): RedirectResponse
    {
        $admin = $this->authorizeSuperAdmin($request);

        $validated = $request->validate([
            'store_id' => ['required', 'string', 'max:100'],
            'store_password' => ['nullable', 'string', 'max:255'],
            'sandbox' => ['boolean'],
        ]);

        Setting::put(SslCommerzGateway::STORE_ID_KEY, $validated['store_id']);

        if (filled($validated['store_password'] ?? null)) {
            Setting::putSecret(SslCommerzGateway::STORE_PASSWORD_KEY, $validated['store_password']);
        }

        if ($request->has('sandbox')) {
            Setting::putBoolean(SslCommerzGateway::SANDBOX_KEY, $request->boolean('sandbox'));
        }

        $this->subscriptions->logAdminActivity(
            $admin,
            'payment_gateway_updated',
            'SSLCommerz store: '.$validated['store_id'],
            $request->ip(),
        );

        return redirect()
            ->route('dashboard', ['page' => 'admin-pricing'])
            ->with('toast', __('subscription.admin.gateway_updated'));
    }

    /**
     * Only super admins may change subscriptions, pricing, or gateway setup.
     */
    private function authorizeSuperAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user !== null && $user->role === User::ROLE_SUPER_ADMIN, 403);

        return $user;
    }

    private function backToSubscriptions(string $message): RedirectResponse
    {
        return redirect()
            ->route('dashboard', ['page' => 'admin-subscriptions'])
            ->with('toast', $message);
    }
}
