<x-layouts.print title="Department Roster">
    <style>
        {{ file_get_contents(resource_path('css/print/reports.css')) }}
    </style>

    <div class="report">
        <div class="report-header">
            <h1>Department Roster</h1>
            <p class="text-sm">{{ $departments->count() }} departments, {{ $totals['employees'] ?? 0 }} total employees</p>
            <p class="text-sm">Generated {{ $generated_at->format('d F Y H:i') }}</p>
        </div>

        @foreach($departments as $dept)
            <div class="report-section">
                <h2>
                    {{ $dept->name }}
                    @if($dept->head)
                        <span style="font-weight: normal; font-size: 0.9em;">— {{ $dept->head->full_name }}</span>
                    @endif
                </h2>

                @if($dept->employees->count() > 0)
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dept->employees as $emp)
                                <tr>
                                    <td>{{ $emp->serial_number }}</td>
                                    <td>{{ $emp->full_name }}</td>
                                    <td>{{ $emp->position?->title }}</td>
                                    <td style="font-size: 9pt; word-break: break-all;">{{ $emp->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p style="font-size: 9pt; text-align: right; margin-top: 0.3cm;">
                        {{ $dept->employees->count() }} employee{{ $dept->employees->count() !== 1 ? 's' : '' }}
                    </p>
                @else
                    <p style="font-style: italic; color: #666;">No employees in this department</p>
                @endif
            </div>
        @endforeach

        <div class="report-footer">
            Generated {{ $generated_at->format('d F Y H:i') }}
        </div>
    </div>
</x-layouts.print>
