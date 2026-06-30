<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\GuestPaymentConfirmationService;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use App\Services\WithdrawalService;

class FlutterwaveWebhookController extends Controller
{
    public function __construct(
        protected SubscriptionBillingService $subscriptionBilling,
        protected GuestPaymentConfirmationService $guestPayments,
        protected WithdrawalService $withdrawals,
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
        $event = $payload['event'] ?? null;

        if ($event === 'transfer.completed') {
            $this->handleTransfer($payload);
            return Response::make('', 200);
        }

        // existing charge/payment handling
        $status      = $payload['data']['status'] ?? $payload['status'] ?? null;
        $reference   = $payload['data']['tx_ref'] ?? $payload['txRef']  ?? null;
        $providerRef = (string) ($payload['data']['id'] ?? $payload['id'] ?? '');

        if ($status === 'successful' && $reference) {
            $this->route($reference, $providerRef);
        }

        return Response::make('', 200);
    }

    protected function handleTransfer(array $payload): void
    {
        $reference   = $payload['data']['reference'] ?? null;
        $status      = strtoupper($payload['data']['status'] ?? '');
        $providerRef = (string) ($payload['data']['id'] ?? '');

        if (! $reference || ! Str::startsWith($reference, 'AFS-WD-')) {
            Log::warning('Flutterwave transfer webhook with unrecognised reference.', ['reference' => $reference]);
            return;
        }

        if ($status === 'SUCCESSFUL') {
            $this->withdrawals->confirmCompleted($reference, $providerRef);
        } elseif ($status === 'FAILED') {
            $this->withdrawals->confirmFailed($reference, $payload['data']['complete_message'] ?? 'Transfer failed at provider.');
        }
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
