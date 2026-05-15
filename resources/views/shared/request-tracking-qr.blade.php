@php
    $trackingUrl = route('tracking.show', $serviceRequest->tracking_code);
@endphp

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title ?? 'Tracking QR Code' }}</h3>
    </div>
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-5 text-center mb-3 mb-md-0">
                <div
                    class="border rounded p-3 d-inline-flex flex-column align-items-center justify-content-center bg-white"
                    data-request-tracking-qr
                    data-qr-url="{{ $trackingUrl }}"
                    data-qr-code="{{ $serviceRequest->tracking_code }}"
                >
                    <div data-qr-render class="mb-2"></div>
                    <small class="text-muted">Scan to open tracking</small>
                </div>
            </div>
            <div class="col-md-7">
                <p class="mb-2">
                    <strong>Tracking code:</strong> {{ $serviceRequest->tracking_code }}
                </p>
                <p class="mb-3">
                    <strong>Tracking URL:</strong>
                    <a href="{{ $trackingUrl }}" target="_blank" rel="noopener">{{ $trackingUrl }}</a>
                </p>
                <p class="text-muted mb-0">
                    Citizens can scan this QR code to jump directly to the public tracking page for this request.
                </p>
            </div>
        </div>
    </div>
</div>
