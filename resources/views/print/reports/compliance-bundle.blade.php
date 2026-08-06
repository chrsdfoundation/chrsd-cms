<x-layouts.print title="Compliance Export">
    <style>
        {{ file_get_contents(resource_path('css/print/reports.css')) }}
    </style>

    <div class="report">
        <div class="report-header">
            <h1>Compliance Export Bundle</h1>
            <p>{{ $from->format('d F Y') }} to {{ $to->format('d F Y') }}</p>
            <p class="text-sm">Generated {{ $generated_at->format('d F Y H:i') }}</p>
        </div>

        {{-- Executive Summary --}}
        <div class="report-section">
            <h2>Summary</h2>
            <table class="report-table">
                <tr>
                    <td><strong>Certificates Issued</strong></td>
                    <td>{{ $totals['certificates'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Letters Released</strong></td>
                    <td>{{ $totals['letters'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Documents Revoked</strong></td>
                    <td>{{ $totals['revocations'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Activity Records</strong></td>
                    <td>{{ $totals['activity'] ?? 0 }}</td>
                </tr>
            </table>
        </div>

        {{-- Revocations Register --}}
        @if(isset($revocations) && $revocations['items']->count() > 0)
            <div class="report-section">
                <h2>Revocations ({{ $revocations['items']->count() }})</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Type</th>
                            <th>Revoked On</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revocations['items'] as $rev)
                            <tr>
                                <td>{{ $rev['serial_number'] }}</td>
                                <td>{{ $rev['document_type'] }}</td>
                                <td>{{ $rev['revoked_at'] }}</td>
                                <td style="font-size: 9pt;">{{ Str::limit($rev['reason'] ?? '', 50) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Issued Documents Register --}}
        @if(isset($documents) && $documents->count() > 0)
            <div class="report-section">
                <h2>Issued Documents ({{ $documents->count() }})</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Type</th>
                            <th>Recipient</th>
                            <th>Date</th>
                            <th>Hash (first 16 chars)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($documents as $doc)
                            <tr>
                                <td style="font-size: 9pt;">{{ $doc['serial_number'] }}</td>
                                <td>{{ $doc['type'] }}</td>
                                <td>{{ Str::limit($doc['recipient'], 30) }}</td>
                                <td>{{ $doc['date'] }}</td>
                                <td style="font-family: monospace; font-size: 8pt;">{{ substr($doc['hash'], 0, 16) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Cryptographic Manifest --}}
        @if(isset($manifest))
            <div class="report-section">
                <h2>Cryptographic Manifest</h2>
                <p style="font-size: 9pt; color: #666;">
                    HMAC-SHA256 digest of the sorted payload (keyed to APP_KEY). Recompute this hash from the printed contents to verify the bundle was not modified after generation.
                </p>
                <p style="font-family: monospace; font-size: 8pt; word-break: break-all; background: #f5f5f5; padding: 0.5cm;">
                    {{ $manifest }}
                </p>
            </div>
        @endif

        <div class="report-footer">
            <strong>Audit Trail Complete</strong><br>
            Generated {{ $generated_at->format('d F Y H:i') }}
        </div>
    </div>
</x-layouts.print>
