{{--
  CHRSD ID — FRONT (Simplified for mPDF compatibility)

  Pure mPDF render with table-based layout. No SVG, no absolute positioning complexity.
  All images as base64 data URIs.

  Vars: $idCard, $employee, $photoUrl, $logoUrl, $roundLogoUrl
--}}
@php
    $displayName = $idCard->displayName();
    $designation = $idCard->designation
        ?: $idCard->program_name
        ?: (optional(optional($employee)->position)->title ?? '');
    $bloodGroup = $idCard->blood_group ?: 'N/A';
    $nationality = $idCard->nationality ?: optional($employee)->nationality ?: '—';
    $idTypeLabel = $idCard->id_type_label
        ?: (optional($idCard->idCardType)->name ?: 'Identity Card');
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { size: 85.6mm 54mm; margin: 0; }
    body { margin: 0; padding: 2mm; font-family: 'DejaVu Sans', Arial, sans-serif; color: #163E22; font-size: 7pt; }
    table { width: 100%; height: 50mm; border-collapse: collapse; }

    /* Left sidebar */
    .sidebar { background: #123420; width: 8mm; color: #C9A14A; font-weight: 700; text-align: center; padding: 2mm 0; }

    /* Main content */
    .content { padding: 1.5mm 2mm; position: relative; }
    .header { display: flex; gap: 2mm; margin-bottom: 1mm; }
    .logo { width: 10mm; height: 10mm; flex-shrink: 0; }
    .logo img { width: 100%; height: 100%; }

    .org-info { flex: 1; }
    .org-name { font-size: 10pt; font-weight: 700; margin: 0; }
    .org-sub { font-size: 4pt; color: #186D3B; margin: 0.2mm 0 0 0; line-height: 1.2; }
    .id-type { font-size: 5.5pt; font-weight: 700; color: #C09020; margin-top: 0.5mm; text-transform: uppercase; }

    .divider { border-top: 0.3mm solid #C09020; margin: 0.5mm 0; }

    .photo-section { float: right; width: 20mm; height: 24mm; border: 0.6mm solid #C09020; margin-left: 2mm; text-align: center; }
    .photo-section img { width: 100%; height: 100%; }
    .photo-section .placeholder { padding-top: 8mm; color: #999; font-size: 6pt; }

    .info { margin-top: 0.5mm; }
    .name { font-size: 11pt; font-weight: 700; margin: 0.3mm 0; }
    .desig { font-size: 7.5pt; font-style: italic; color: #186D3B; margin: 0; }

    .details { width: 100%; font-size: 6.5pt; margin-top: 0.5mm; border-collapse: collapse; }
    .details td { padding: 0.2mm 0; }
    .details .label { color: #186D3B; width: 16mm; font-weight: bold; }
    .details .value { color: #163E22; font-weight: 700; }

    .footer { font-size: 5pt; color: #186D3B; margin-top: 0.5mm; border-top: 0.2mm solid #C09020; padding-top: 0.3mm; }
</style>
</head>
<body>

<div class="header">
    @if($logoUrl)
        <div class="logo"><img src="{{ $logoUrl }}" alt=""></div>
    @endif
    <div class="org-info">
        <div class="org-name">CHRSD</div>
        <div class="org-sub">Centre for Humanitarian Research &amp;<br>Social Development Foundation</div>
        <div class="id-type">{{ $idTypeLabel }}</div>
    </div>
</div>

<div class="divider"></div>

@if($photoUrl)
    <div class="photo-section"><img src="{{ $photoUrl }}" alt=""></div>
@else
    <div class="photo-section"><div class="placeholder">PHOTO</div></div>
@endif

<div class="info">
    <div class="name">{{ $displayName }}</div>
    <div class="desig">{{ $designation }}</div>
</div>

<table class="details">
    <tr>
        <td class="label">ID No</td>
        <td class="value">{{ $idCard->serial_number }}</td>
    </tr>
    <tr>
        <td class="label">Blood Group</td>
        <td class="value">{{ $bloodGroup }}</td>
    </tr>
    <tr>
        <td class="label">Nationality</td>
        <td class="value">{{ $nationality }}</td>
    </tr>
    <tr>
        <td class="label">Valid From</td>
        <td class="value">{{ optional($idCard->valid_from)->format('d M Y') }}</td>
    </tr>
    <tr>
        <td class="label">Expires</td>
        <td class="value">{{ optional($idCard->valid_until)->format('d M Y') }}</td>
    </tr>
</table>

<div class="footer">
    This card certifies that the bearer is an authorized representative of CHRSD.
</div>

</body>
</html>
