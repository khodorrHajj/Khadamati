<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2933; font-size: 12px; line-height: 1.5; }
        h1, h2, h3 { margin: 0 0 8px; }
        .header { margin-bottom: 20px; }
        .meta { margin-bottom: 20px; border-collapse: collapse; width: 100%; }
        .meta td { padding: 6px 8px; border: 1px solid #d9e2ec; }
        .meta th { width: 180px; text-align: left; padding: 6px 8px; border: 1px solid #d9e2ec; background: #f7fafc; }
        .section { margin-top: 20px; }
        .box { border: 1px solid #d9e2ec; padding: 12px; background: #fbfdff; }
        .documents { width: 100%; border-collapse: collapse; }
        .documents th, .documents td { border: 1px solid #d9e2ec; padding: 6px 8px; text-align: left; }
        .documents th { background: #f7fafc; }
        .muted { color: #52606d; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Service Request Receipt</h1>
        <div class="muted">Generated on {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <table class="meta" cellspacing="0" cellpadding="0">
        <tbody>
            <tr><th>Tracking Code</th><td>{{ $serviceRequest->tracking_code }}</td></tr>
            <tr><th>Request ID</th><td>#{{ $serviceRequest->id }}</td></tr>
            <tr><th>Citizen</th><td>{{ $serviceRequest->user?->name ?? '-' }}</td></tr>
            <tr><th>Citizen Email</th><td>{{ $serviceRequest->user?->email ?? '-' }}</td></tr>
            <tr><th>Service</th><td>{{ $serviceRequest->service?->name ?? '-' }}</td></tr>
            <tr><th>Category</th><td>{{ $serviceRequest->service?->serviceCategory?->name ?? '-' }}</td></tr>
            <tr><th>Office</th><td>{{ $serviceRequest->service?->governmentOffice?->name ?? '-' }}</td></tr>
            <tr><th>Municipality</th><td>{{ $serviceRequest->service?->governmentOffice?->municipality?->name ?? '-' }}</td></tr>
            <tr><th>Status</th><td>{{ $serviceRequest->status }}</td></tr>
            <tr><th>Submitted At</th><td>{{ optional($serviceRequest->created_at)->format('Y-m-d H:i') ?: '-' }}</td></tr>
        </tbody>
    </table>

    <div class="section">
        <h3>Submitted Notes</h3>
        <div class="box">
            {!! nl2br(e($serviceRequest->notes ?: 'No notes were provided with this request.')) !!}
        </div>
    </div>

    <div class="section">
        <h3>Uploaded Documents</h3>
        @if ($serviceRequest->requestDocuments->isNotEmpty())
            <table class="documents" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>Document</th>
                        <th>Type</th>
                        <th>Uploaded At</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($serviceRequest->requestDocuments as $document)
                        <tr>
                            <td>{{ $document->original_name ?: basename((string) $document->document_path) }}</td>
                            <td>{{ $document->document_type ?: 'Submitted document' }}</td>
                            <td>{{ optional($document->created_at)->format('Y-m-d H:i') ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="box">No supporting documents were uploaded at the time this receipt was generated.</div>
        @endif
    </div>
</body>
</html>
