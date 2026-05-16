<?php

namespace App\Services;

use App\Models\Service;
use App\Models\StripePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    public function initiate(Service $service, int $userId): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $stripePayment = StripePayment::create([
            'user_id'      => $userId,
            'service_id'   => $service->id,
            'price_amount' => $service->price,
            'status'       => 'pending',
        ]);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'mode'                 => 'payment',
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'usd',
                    'unit_amount'  => (int) round((float) $service->price * 100),
                    'product_data' => [
                        'name' => $service->name,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata'    => [
                'stripe_payment_id' => (string) $stripePayment->id,
            ],
            'success_url' => route('citizen.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('citizen.payment.cancelled'),
        ]);

        $stripePayment->update([
            'stripe_session_id' => $session->id,
            'payment_url'       => $session->url,
        ]);

        return $session->url;
    }

    public function handleWebhook(Request $request, CitizenServiceRequestService $serviceRequestService): void
    {
        $event = $this->verifySignature($request);

        if (!$event) {
            abort(401, 'Invalid webhook signature.');
        }

        if ($event->type !== 'checkout.session.completed') {
            return;
        }

        $session = $event->data->object;

        $stripePayment = StripePayment::where('stripe_session_id', $session->id)->first();

        if (!$stripePayment) {
            $stripePayment = StripePayment::find($session->metadata->stripe_payment_id ?? null);
        }

        if (!$stripePayment) {
            Log::warning('StripePayment webhook: record not found.', ['session_id' => $session->id]);
            return;
        }

        $stripePayment->update([
            'status'                   => 'completed',
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
        ]);

        if (!$stripePayment->service_request_id) {
            $serviceRequest = $serviceRequestService->create(
                $stripePayment->service,
                $stripePayment->user_id,
                'Paid via card (Stripe)'
            );
            $stripePayment->update(['service_request_id' => $serviceRequest->id]);
        }
    }

    private function verifySignature(Request $request): \Stripe\Event|false
    {
        $secret    = config('services.stripe.webhook_secret');
        $signature = $request->header('Stripe-Signature');
        $payload   = $request->getContent();

        if (!$secret || !$signature) {
            return false;
        }

        try {
            return Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Exception) {
            return false;
        }
    }
}
