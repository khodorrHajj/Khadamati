import QRCode from 'qrcode';

const initializeRequestTrackingQr = () => {
    const widgets = document.querySelectorAll('[data-request-tracking-qr]');

    widgets.forEach(async (widget) => {
        const target = widget.querySelector('[data-qr-render]');
        const trackingUrl = widget.dataset.qrUrl;
        const trackingCode = widget.dataset.qrCode;

        if (!target || !trackingUrl || target.dataset.qrReady === 'true') {
            return;
        }

        try {
            const canvas = document.createElement('canvas');

            await QRCode.toCanvas(canvas, trackingUrl, {
                width: 180,
                margin: 1,
                color: {
                    dark: '#111827',
                    light: '#ffffff',
                },
            });

            canvas.setAttribute('aria-label', `QR code for ${trackingCode}`);
            canvas.className = 'img-fluid';

            target.innerHTML = '';
            target.appendChild(canvas);
            target.dataset.qrReady = 'true';
        } catch (error) {
            target.innerHTML = '<div class="text-muted small">QR code could not be generated.</div>';
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeRequestTrackingQr);
} else {
    initializeRequestTrackingQr();
}
