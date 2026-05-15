<?php

namespace App\Services;

use App\Models\CryptoPayment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoPaymentService
{
    public function initiate(Service $service, int $userId): string
    {
        $cryptoPayment = CryptoPayment::create([
            'user_id'      => $userId,
            'service_id'   => $service->id,
            'price_amount' => $service->price,
            'status'       => 'waiting',
        ]);

        $response = Http::withHeaders([
            'x-api-key' => config('services.nowpayments.api_key'),
        ])->post($this->baseUrl() . '/v1/invoice', [
            'price_amount'      => (float) $service->price,
            'price_currency'    => 'usd',
            'order_id'          => (string) $cryptoPayment->id,
            'order_description' => 'Payment for: ' . $service->name,
            'ipn_callback_url'  => route('webhook.nowpayments'),
            'success_url'       => route('citizen.payment.success'),
            'cancel_url'        => route('citizen.payment.cancelled'),
        ]);

        if (!$response->successful()) {
            $cryptoPayment->update(['status' => 'failed']);
            throw new \RuntimeException('NOWPayments invoice creation failed: ' . $response->body());
        }

        $data = $response->json();

        $cryptoPayment->update([
            'nowpayments_invoice_id' => $data['id'] ?? null,
            'payment_url'            => $data['invoice_url'] ?? null,
        ]);

        return $data['invoice_url'];
    }

    public function handleWebhook(Request $request, CitizenServiceRequestService $serviceRequestService): void
    {
        if (!$this->verifySignature($request)) {
            abort(401, 'Invalid webhook signature.');
        }

        $data = $request->all();

        $cryptoPayment = CryptoPayment::where(
            'nowpayments_payment_id', $data['payment_id'] ?? null
        )->first();

        if (!$cryptoPayment) {
            $cryptoPayment = CryptoPayment::find($data['order_id'] ?? null);
        }

        if (!$cryptoPayment) {
            Log::warning('CryptoPayment webhook: record not found.', ['payload' => $data]);
            return;
        }

        $cryptoPayment->update([
            'status'                 => $data['payment_status'] ?? $cryptoPayment->status,
            'actually_paid'          => $data['actually_paid'] ?? null,
            'nowpayments_payment_id' => $data['payment_id'] ?? $cryptoPayment->nowpayments_payment_id,
            'payin_address'          => $data['pay_address'] ?? null,
        ]);

        if (
            ($data['payment_status'] ?? null) === 'finished'
            && !$cryptoPayment->service_request_id
        ) {
            $serviceRequest = $serviceRequestService->create(
                $cryptoPayment->service,
                $cryptoPayment->user_id,
                'Paid via cryptocurrency'
            );
            $cryptoPayment->update(['service_request_id' => $serviceRequest->id]);
        }
    }

    private function baseUrl(): string
    {
        return config('services.nowpayments.sandbox')
            ? 'https://api-sandbox.nowpayments.io'
            : 'https://api.nowpayments.io';
    }

    private function verifySignature(Request $request): bool
    {
        $secret    = config('services.nowpayments.ipn_secret');
        $signature = $request->header('x-nowpayments-sig');

        if (!$secret || !$signature) {
            return false;
        }

        $payload = $request->all();
        ksort($payload);

        $computed = hash_hmac('sha512', json_encode($payload), $secret);

        return hash_equals($computed, strtolower($signature));
    }
}
