<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\GuestPaymentConfirmationService;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class PaystackWebhookController extends Controller
{
    public function __construct(
        protected SubscriptionBillingService $subscriptionBilling,
        protected GuestPaymentConfirmationService $guestPayments,
    ) {}

    public function handle(Request $request)
    {
        $secret = config('services.paystack.secret_key');
        $signature = $request->header('X-Paystack-Signature');
        $computed = hash_hmac('sha512', $request->getContent(), (string) $secret);

        if (blank($secret) || ! hash_equals($computed, (string) $signature)) {
            Log::warning('Paystack webhook signature mismatch — ignored.');
            return Response::make('', 401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $reference = $payload['data']['reference'] ?? null;
        $providerRef = (string) ($payload['data']['id'] ?? '');

        if ($event === 'charge.success' && $reference) {
            $this->route($reference, $providerRef);
        }

        return Response::make('', 200);
    }

    protected function route(string $reference, string $providerRef): void
    {
        if (Str::startsWith($reference, 'AFS-SUB-')) {
            $this->subscriptionBilling->confirmPayment($reference, $providerRef);
        } elseif (Str::startsWith($reference, 'AFS-PAY-')) {
            $this->guestPayments->confirm($reference, $providerRef);
        } else {
            Log::warning('Paystack webhook with unrecognised reference prefix.', ['reference' => $reference]);
        }
    }
}
