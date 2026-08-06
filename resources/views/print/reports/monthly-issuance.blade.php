<x-layouts.print title="Monthly Issuance Report">
    <style>
        {{ file_get_contents(resource_path('css/print/reports.css')) }}
    </style>

    <div class="report">
        <div class="report-header">
            <h1>Monthly Issuance Summary</h1>
            <p>{{ $month->format('F Y') }}</p>
            <p class="text-sm">Generated {{ $generated_at->format('d F Y H:i') }}</p>
        </div>

        @if($certificates->count() > 0)
            <div class="report-section">
                <h2>Certificates Issued ({{ $certificates->count() }})</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Employee</th>
                            <th>Certificate Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($certificates as $cert)
                            <tr>
                                <td>{{ $cert->serial_number }}</td>
                                <td>{{ $cert->employee?->full_name ?? $cert->recipient_name }}</td>
                                <td>{{ $cert->type->name }}</td>
                                <td>{{ $cert->issued_on?->format('d M Y') }}</td>
                                <td>{{ $cert->issuance_status->label ?? $cert->issuance_status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($letters->count() > 0)
            <div class="report-section">
                <h2>Letters Released ({{ $letters->count() }})</h2>
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($letters as $letter)
                            <tr>
                                <td>{{ $letter->serial_number }}</td>
                                <td>{{ $letter->recipient_name }}</td>
                                <td>{{ Str::limit($letter->subject, 40) }}</td>
                                <td>{{ $letter->released_on?->format('d M Y') }}</td>
                                <td>{{ $letter->letter_status->label ?? $letter->letter_status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="report-footer">
            <strong>Summary:</strong> {{ $totals['certificates'] ?? 0 }} certificates, {{ $totals['letters'] ?? 0 }} letters<br>
            Generated {{ $generated_at->format('d F Y H:i') }}
        </div>
    </div>
</x-layouts.print>
