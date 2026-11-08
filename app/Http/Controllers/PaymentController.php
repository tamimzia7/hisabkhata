<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**
     * Handle the browser return after a successful gateway checkout.
     */
    public function success(Request $request): RedirectResponse
    {
        $payment = $this->locatePayment($request);

        if ($payment === null) {
            abort(404);
        }

        $payment = $this->payments->handleSuccess($payment, $request->string('val_id')->toString());

        return redirect()
            ->route('subscriptions.show', $payment->product)
            ->with('toast', $payment->isSuccessful()
                ? __('subscription.payment.success')
                : __('subscription.payment.failed'));
    }

    /**
     * Handle a failed checkout return.
     */
    public function fail(Request $request): RedirectResponse
    {
        $payment = $this->locatePayment($request);

        if ($payment === null) {
            abort(404);
        }

        $this->payments->markFailed(
            $payment,
            (string) $request->input('status', __('subscription.payment.failed')),
            $request->all(),
        );

        return redirect()
            ->route('subscriptions.show', $payment->product)
            ->with('toast', __('subscription.payment.failed'));
    }

    /**
     * Handle a cancelled checkout return.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $payment = $this->locatePayment($request);

        if ($payment === null) {
            abort(404);
        }

        $this->payments->markCancelled(
            $payment,
            (string) $request->input('status', __('subscription.payment.cancelled')),
            $request->all(),
        );

        return redirect()
            ->route('subscriptions.show', $payment->product)
            ->with('toast', __('subscription.payment.cancelled'));
    }

    /**
     * Handle SSLCommerz's server-to-server IPN callback.
     */
    public function ipn(Request $request): Response|JsonResponse
    {
        $payment = $this->locatePayment($request);

        if ($payment === null) {
            return response('Invalid transaction', 404);
        }

        if ($payment->isSuccessful()) {
            return response('OK');
        }

        $payment = $this->payments->handleSuccess($payment, $request->string('val_id')->toString());

        return $payment->isSuccessful()
            ? response('OK')
            : response('Invalid payment', 400);
    }

    /**
     * Resolve the payment referenced by a callback.
     */
    private function locatePayment(Request $request): ?Payment
    {
        $transactionId = $request->string('tran_id')->toString();

        if ($transactionId === '') {
            return null;
        }

        return $this->payments->findByTransactionId($transactionId);
    }
}
