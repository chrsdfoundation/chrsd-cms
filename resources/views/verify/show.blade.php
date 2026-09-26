<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Document Verification — {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-12 px-4">

<div class="max-w-2xl mx-auto">

    {{-- Header --}}
    <div class="text-center mb-8">
        <p class="text-sm font-semibold text-gray-500 uppercase tracking-widest">{{ config('app.name') }}</p>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">Document Verification Portal</h1>
    </div>

    @if (! $found)

        {{-- Not Found --}}
        <div class="bg-red-50 border border-red-400 text-red-800 p-4 rounded-lg flex items-center mb-6">
            <span class="text-2xl mr-3">✗</span>
            <div>
                <h3 class="font-bold">Document Not Found</h3>
                <p class="text-sm">No document matches this verification link. It may have been tampered with or the hash is invalid.</p>
            </div>
        </div>

    @else

        @php
            $isValid   = $snapshot['is_valid'];
            $isRevoked = $snapshot['status'] === 'revoked';
            $isExpired = $snapshot['status'] === 'expired';
        @endphp

        {{-- Status Banner --}}
        @if ($isValid)
            <div class="bg-green-50 border border-green-400 text-green-800 p-4 rounded-lg flex items-center mb-6">
                <span class="text-2xl mr-3">✓</span>
                <div>
                    <h3 class="font-bold">Authentic Document Verified</h3>
                    <p class="text-sm">This document was officially issued by {{ config('app.name') }}.</p>
                </div>
            </div>
        @elseif ($isRevoked)
            <div class="bg-red-50 border border-red-400 text-red-800 p-4 rounded-lg flex items-center mb-6">
                <span class="text-2xl mr-3">✗</span>
                <div>
                    <h3 class="font-bold">Document Revoked</h3>
                    <p class="text-sm">This document has been revoked and is no longer valid.@if($snapshot['revocation_reason']) Reason: {{ $snapshot['revocation_reason'] }}.@endif</p>
                </div>
            </div>
        @elseif ($isExpired)
            <div class="bg-yellow-50 border border-yellow-400 text-yellow-800 p-4 rounded-lg flex items-center mb-6">
                <span class="text-2xl mr-3">⚠</span>
                <div>
                    <h3 class="font-bold">Document Expired</h3>
                    <p class="text-sm">This document is authentic but has passed its validity date.</p>
                </div>
            </div>
        @else
            <div class="bg-gray-50 border border-gray-300 text-gray-700 p-4 rounded-lg flex items-center mb-6">
                <span class="text-2xl mr-3">ℹ</span>
                <div>
                    <h3 class="font-bold">Document Found — Status: {{ ucfirst($snapshot['status']) }}</h3>
                    <p class="text-sm">This document record exists in the system.</p>
                </div>
            </div>
        @endif

        {{-- Document Specifications --}}
        <div class="bg-white shadow rounded-lg p-6 space-y-4">
            <h2 class="text-lg font-semibold border-b pb-2 text-gray-700">Document Specifications</h2>

            <table class="w-full text-left border-collapse text-sm">
                <tbody>
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium w-1/3">Serial Number</th>
                        <td class="py-2 font-mono font-bold text-gray-900">{{ $snapshot['serial'] }}</td>
                    </tr>
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Document Type</th>
                        <td class="py-2 text-gray-900">
                            {{ match($snapshot['kind']) {
                                'OfficialLetter' => 'Official Letter',
                                'Certificate'    => 'Certificate',
                                'Employee'       => 'Employee Record',
                                'IdCard'         => 'ID Card',
                                default          => $snapshot['kind'],
                            } }}
                        </td>
                    </tr>
                    @if ($snapshot['recipient'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Issued To</th>
                        <td class="py-2 font-semibold text-gray-900">{{ $snapshot['recipient'] }}</td>
                    </tr>
                    @endif
                    @if ($snapshot['purpose'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Purpose</th>
                        <td class="py-2 text-gray-900">{{ $snapshot['purpose'] }}</td>
                    </tr>
                    @endif
                    @if ($snapshot['signatory'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Authorized Signatory</th>
                        <td class="py-2 text-gray-900">{{ $snapshot['signatory'] }}</td>
                    </tr>
                    @endif
                    @if ($snapshot['kind'] === 'IdCard' && $snapshot['valid_from'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Valid From</th>
                        <td class="py-2 text-gray-900">{{ \Carbon\Carbon::parse($snapshot['valid_from'])->format('F j, Y') }}</td>
                    </tr>
                    @elseif ($snapshot['kind'] !== 'IdCard')
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Date of Issuance</th>
                        <td class="py-2 text-gray-900">
                            {{ $snapshot['issued_on'] ? \Carbon\Carbon::parse($snapshot['issued_on'])->format('F j, Y') : '—' }}
                        </td>
                    </tr>
                    @endif
                    @if ($snapshot['valid_until'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Valid Until</th>
                        <td class="py-2 text-gray-900">{{ \Carbon\Carbon::parse($snapshot['valid_until'])->format('F j, Y') }}</td>
                    </tr>
                    @endif
                    @if ($snapshot['revoked_at'])
                    <tr class="border-b">
                        <th class="py-2 pr-4 text-gray-500 font-medium">Revoked On</th>
                        <td class="py-2 text-red-700">{{ \Carbon\Carbon::parse($snapshot['revoked_at'])->format('F j, Y') }}</td>
                    </tr>
                    @endif
                    <tr>
                        <th class="py-2 pr-4 text-gray-500 font-medium align-top">Cryptographic Hash</th>
                        <td class="py-2 font-mono text-xs text-gray-500 break-all">{{ $snapshot['hash'] }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- Action Buttons --}}
            <div class="pt-4 flex flex-col sm:flex-row gap-3">
                <a href="mailto:info@chrsd.org?subject={{ urlencode('Verification Query ' . $snapshot['serial']) }}"
                   class="border border-gray-300 text-gray-700 text-center px-4 py-2 rounded hover:bg-gray-50 transition text-sm">
                    Report Discrepancy
                </a>
            </div>
        </div>

    @endif

    <p class="text-center text-xs text-gray-400 mt-8">
        Verification powered by {{ config('app.name') }} &bull; This page contains no personal data beyond what was encoded in the QR code.
    </p>

</div>

</body>
</html>
