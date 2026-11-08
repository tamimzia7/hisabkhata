<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\PaymentService;
use App\Services\SubscriptionPageData;
use App\Services\SubscriptionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionPageData $pageData,
        private SubscriptionService $subscriptions,
        private PaymentService $payments,
    ) {}

    /**
     * Show every product's independent subscription status.
     */
    public function index(Request $request): View
    {
        return view('subscriptions.index', [
            'products' => $this->pageData->allProducts($request->user()),
            'currency' => SubscriptionPlan::CURRENCY_BDT,
        ]);
    }

    /**
     * Show the subscription page for one product.
     */
    public function show(Request $request, string $product): View
    {
        abort_unless(SubscriptionPlan::isValidProduct($product), 404);

        return view('subscriptions.show', $this->pageData->forProduct($request->user(), $product) + [
            'layout' => 'layouts.app',
        ]);
    }

    /**
     * Claim the one-time 30-day trial for a product.
     */
    public function startTrial(Request $request, string $product): RedirectResponse
    {
        abort_unless(SubscriptionPlan::isValidProduct($product), 404);

        try {
            $this->subscriptions->startTrial($request->user(), $product);
        } catch (DomainException $exception) {
            return redirect()
                ->route('subscriptions.show', $product)
                ->with('toast', $exception->getMessage());
        }

        return redirect()
            ->route('subscriptions.show', $product)
            ->with('toast', __('subscription.trial.started'));
    }

    /**
     * Initialize a paid SSLCommerz checkout.
     */
    public function checkout(Request $request, string $product): RedirectResponse
    {
        abort_unless(SubscriptionPlan::isValidProduct($product), 404);

        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:80'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('slug', $validated['plan'])
            ->where('product', $product)
            ->where('is_active', true)
            ->whereIn('billing_cycle', SubscriptionPlan::PAID_BILLING_CYCLES)
            ->firstOrFail();

        $payment = $this->payments->initiate($request->user(), $plan);

        try {
            $checkoutUrl = $this->payments->redirectToGateway($payment, $request->user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('subscriptions.show', $product)
                ->with('toast', $exception->getMessage());
        }

        return redirect()->away($checkoutUrl);
    }
}
