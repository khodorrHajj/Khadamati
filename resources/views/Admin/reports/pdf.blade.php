<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Platform Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
        .header h1 { color: #007bff; margin: 0; font-size: 22px; }
        .header p { color: #666; margin: 5px 0 0; font-size: 11px; }
        .summary-boxes { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .summary-box { flex: 1; text-align: center; padding: 12px; margin: 0 5px; border: 1px solid #ddd; border-radius: 6px; background: #f8f9fa; }
        .summary-box h2 { margin: 0; font-size: 18px; color: #333; }
        .summary-box p { margin: 3px 0 0; font-size: 10px; color: #666; text-transform: uppercase; }
        .section { margin-bottom: 20px; }
        .section h3 { color: #007bff; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #007bff; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; }
        td { padding: 7px 10px; border-bottom: 1px solid #dee2e6; font-size: 11px; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; background: #e9ecef; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; color: #999; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>E-Services Platform Report</h1>
        <p>Generated on {{ now()->format('F d, Y \a\t H:i') }}</p>
        @if($filters['municipality'] || $filters['office'] || $filters['date_from'] || $filters['date_to'])
            <p style="margin-top: 3px;">
                <strong>Filters:</strong>
                @if($filters['municipality'])
                    Municipality: {{ $municipalities->firstWhere('id', $filters['municipality'])?->name ?? 'N/A' }}
                @endif
                @if($filters['office'])
                    | Office: {{ $offices->firstWhere('id', $filters['office'])?->name ?? 'N/A' }}
                @endif
                @if($filters['date_from'])
                    | From: {{ $filters['date_from'] }}
                @endif
                @if($filters['date_to'])
                    | To: {{ $filters['date_to'] }}
                @endif
            </p>
        @endif
    </div>

    {{-- Summary Boxes --}}
    <div class="summary-boxes">
        <div class="summary-box">
            <h2>LBP {{ number_format($revenueTotal, 0) }}</h2>
            <p>Filtered Revenue</p>
        </div>
        <div class="summary-box">
            <h2>{{ $requestsPerOffice->count() }}</h2>
            <p>Offices with Requests</p>
        </div>
        <div class="summary-box">
            <h2>{{ $topServices->first()['label'] ?? 'N/A' }}</h2>
            <p>Top Service</p>
        </div>
    </div>

    {{-- Revenue by Municipality & Office --}}
    <div class="section">
        <h3>Revenue by Municipality & Office</h3>
        <table>
            <thead>
                <tr>
                    <th>Municipality</th>
                    <th>Office</th>
                    <th class="text-right">Revenue (LBP)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($revenueRows as $row)
                    <tr>
                        <td>{{ $row['municipality'] }}</td>
                        <td>{{ $row['office'] }}</td>
                        <td class="text-right">{{ number_format($row['revenue'], 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align:center; color:#999;">No revenue data for the selected filters.</td>
                    </tr>
                @endforelse
                @if($revenueRows->isNotEmpty())
                    <tr class="total-row">
                        <td colspan="2" class="text-right">Total</td>
                        <td class="text-right">{{ number_format($revenueTotal, 0) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- Requests per Office --}}
    <div class="section">
        <h3>Requests per Government Office</h3>
        <table>
            <thead>
                <tr>
                    <th>Office</th>
                    <th class="text-right">Request Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requestsPerOffice as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center; color:#999;">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top Services --}}
    <div class="section">
        <h3>Top 5 Services</h3>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th class="text-right">Request Count</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topServices as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center; color:#999;">No data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pending Workload --}}
    <div class="section">
        <h3>Pending Workload</h3>
        <table>
            <thead>
                <tr>
                    <th>Office</th>
                    <th class="text-right">Pending Requests</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendingWorkload as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-right">{{ $row['count'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align:center; color:#999;">No pending workload.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer">
        This report was auto-generated by the E-Services Platform.
    </div>
</body>
</html>
