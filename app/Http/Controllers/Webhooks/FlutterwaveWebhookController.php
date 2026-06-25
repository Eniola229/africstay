<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\GuestPaymentConfirmationService;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class FlutterwaveWebhookController extends Controller
{
    public function __construct(
        protected SubscriptionBillingService $subscriptionBilling,
        protected GuestPaymentConfirmationService $guestPayments,
    ) {}

    public function handle(Request $request)
    {
        $signature = $request->header('verif-hash');
        $expected = config('services.flutterwave.webhook_secret_hash');

        if (blank($expected) || $signature !== $expected) {
            Log::warning('Flutterwave webhook signature mismatch — ignored.');
            return Response::make('', 401);
        }

        $payload = $request->all();
        $status = $payload['data']['status'] ?? null;
        $reference = $payload['data']['tx_ref'] ?? null;
        $providerRef = (string) ($payload['data']['id'] ?? '');

        if ($status === 'successful' && $reference) {
            $this->route($reference, $providerRef);
        }

        return Response::make('', 200);
    }

    /**
     * Our own reference prefixes tell us which flow this belongs to:
     *   AFS-SUB-...  -> subscription/billing payment
     *   AFS-PAY-...  -> guest booking payment (virtual account)
     * Idempotency is handled inside each service via payment_reference.
     */
    protected function route(string $reference, string $providerRef): void
    {
        if (Str::startsWith($reference, 'AFS-SUB-')) {
            $this->subscriptionBilling->confirmPayment($reference, $providerRef);
        } elseif (Str::startsWith($reference, 'AFS-PAY-')) {
            $this->guestPayments->confirm($reference, $providerRef);
        } else {
            Log::warning('Flutterwave webhook with unrecognised reference prefix.', ['reference' => $reference]);
        }
    }
}
