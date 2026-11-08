<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SslCommerzGateway
{
    public const GATEWAY = 'sslcommerz';

    public const STORE_ID_KEY = 'payment.sslcommerz.store_id';

    public const STORE_PASSWORD_KEY = 'payment.sslcommerz.store_password';

    public const SANDBOX_KEY = 'payment.sslcommerz.sandbox';

    /**
     * The effective store identifier, preferring admin-configured values.
     */
    public function storeId(): ?string
    {
        return Setting::value(self::STORE_ID_KEY) ?: config('services.sslcommerz.store_id');
    }

    /**
     * The effective store password, decrypted from the settings table.
     */
    public function storePassword(): ?string
    {
        return Setting::secret(self::STORE_PASSWORD_KEY) ?: config('services.sslcommerz.store_password');
    }

    /**
     * Determine whether the gateway runs against the sandbox environment.
     */
    public function isSandbox(): bool
    {
        $setting = Setting::value(self::SANDBOX_KEY);

        if ($setting !== null) {
            return $setting === '1';
        }

        return (bool) config('services.sslcommerz.sandbox', true);
    }

    /**
     * Determine whether merchant credentials are available.
     */
    public function isConfigured(): bool
    {
        return filled($this->storeId()) && filled($this->storePassword());
    }

    /**
     * The gateway base URL for the active environment.
     */
    public function baseUrl(): string
    {
        return $this->isSandbox()
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Initialize a hosted checkout session and return the redirect URL.
     *
     * @return array{url: string, response: array<string, mixed>}
     */
    public function initiate(Payment $payment, User $user): array
    {
        $this->ensureConfigured();

        $payload = [
            'store_id' => (string) $this->storeId(),
            'store_passwd' => (string) $this->storePassword(),
            'total_amount' => number_format((float) $payment->amount, 2, '.', ''),
            'currency' => $payment->currency ?: Payment::CURRENCY_BDT,
            'tran_id' => $payment->transaction_id,
            'success_url' => route('payments.sslcommerz.success'),
            'fail_url' => route('payments.sslcommerz.fail'),
            'cancel_url' => route('payments.sslcommerz.cancel'),
            'ipn_url' => route('payments.sslcommerz.ipn'),
            'cus_name' => $user->name,
            'cus_email' => $user->email,
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_state' => 'Dhaka',
            'cus_postcode' => '1000',
            'cus_country' => 'Bangladesh',
            'cus_phone' => filled($user->phone) ? $user->phone : '01700000000',
            'ship_name' => $user->name,
            'ship_add1' => 'Dhaka',
            'ship_city' => 'Dhaka',
            'ship_state' => 'Dhaka',
            'ship_postcode' => '1000',
            'ship_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'product_name' => $payment->plan?->name ?? 'Subscription',
            'product_category' => $payment->product,
            'product_profile' => 'general',
            'value_a' => (string) $payment->getKey(),
            'value_b' => $payment->product,
            'value_c' => $payment->billing_cycle,
        ];

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(30)
                ->post($this->baseUrl().'/gwprocess/v4/api.php', $payload);
        } catch (ConnectionException|Throwable) {
            throw new RuntimeException(__('subscription.payment.gateway_unreachable'));
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || strtoupper((string) ($data['status'] ?? '')) !== 'SUCCESS' || empty($data['GatewayPageURL'])) {
            throw new RuntimeException((string) ($data['failedreason'] ?? __('subscription.payment.gateway_failed')));
        }

        return [
            'url' => (string) $data['GatewayPageURL'],
            'response' => $data,
        ];
    }

    /**
     * Verify a transaction through SSLCommerz's server-side validation API.
     *
     * @return array<string, mixed>|null
     */
    public function verify(string $validationId): ?array
    {
        $this->ensureConfigured();

        try {
            $response = Http::acceptJson()
                ->timeout(30)
                ->get($this->baseUrl().'/validator/api/validationserverAPI.php', [
                    'val_id' => $validationId,
                    'store_id' => (string) $this->storeId(),
                    'store_passwd' => (string) $this->storePassword(),
                    'format' => 'json',
                    'v' => '1',
                ]);
        } catch (ConnectionException|Throwable) {
            return null;
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            return null;
        }

        $status = strtoupper((string) ($data['status'] ?? ''));

        return in_array($status, ['VALID', 'VALIDATED'], true) ? $data : null;
    }

    /**
     * Settings safe to show in the admin panel (never the password).
     *
     * @return array{store_id: ?string, sandbox: bool, configured: bool, password_set: bool}
     */
    public function publicSettings(): array
    {
        return [
            'store_id' => $this->storeId(),
            'sandbox' => $this->isSandbox(),
            'configured' => $this->isConfigured(),
            'password_set' => filled($this->storePassword()),
        ];
    }

    /**
     * Throw when the merchant account has not been configured yet.
     */
    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(__('subscription.payment.gateway_not_configured'));
        }
    }
}
