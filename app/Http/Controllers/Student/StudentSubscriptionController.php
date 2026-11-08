<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPageData;
use App\Services\SubscriptionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionPageData $pageData,
        private SubscriptionService $subscriptions,
    ) {}

    /**
     * Show the Meal Management subscription page.
     */
    public function show(Request $request): View
    {
        return view('student.subscription', $this->pageData->forProduct(
            $request->user(),
            SubscriptionPlan::PRODUCT_STUDENT,
        ));
    }

    /**
     * Activate the one-time Meal Management free trial.
     */
    public function activate(Request $request): RedirectResponse
    {
        try {
            $this->subscriptions->startTrial($request->user(), SubscriptionPlan::PRODUCT_STUDENT);
        } catch (DomainException $exception) {
            return redirect()
                ->route('student.subscription.show')
                ->with('toast', $exception->getMessage());
        }

        return redirect()
            ->route('student.dashboard')
            ->with('toast', __('student.subscription.activated'));
    }
}
