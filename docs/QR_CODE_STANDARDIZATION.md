# QR Code Standardization Guide

## Overview

As of August 2026, all QR code generation across the CHRSD CMS system has been standardized to use a single unified approach. This ensures consistency in appearance, functionality, and maintainability across all document types.

## Standardized Approach

### Single Service
- **Service**: `App\Services\QrCodeService`
- **Location**: `app/Services/QrCodeService.php`
- **Namespace**: `App\Services` (not `App\Services\Verification`)

The old `App\Services\Verification\QrCodeService` still exists as a backward-compatibility facade but is deprecated.

### Standardized Parameters

**Module Size**: `5` (DNS2D library units)
- Creates approximately 150-200px QR code at 96dpi
- Suitable for scanning from 30cm+ distance
- Consistent across all printed and digital documents
- Default parameter, no need to specify in most cases

**Encoding Format**:
- **Primary**: SVG (scalable, self-contained, ideal for PDFs)
- **Fallback**: PNG data URI (for DomPDF compatibility if needed)

**Verification URL**:
- **Production**: `https://chrsd.org/verify/{identifier}`
- **Local Development**: `http://127.0.0.1:8001/verify/{identifier}`
- Configured via `VERIFY_BASE_URL` environment variable
- Falls back to `APP_URL` if not configured

**Identifier**:
- **Primary**: `verification_hash` (database column)
- **Fallback**: `serial_number` (if hash unavailable)
- Service automatically selects the appropriate identifier

## Documents Using Standardized QR Codes

All documents now use the unified service with consistent sizing:

### 1. **ID Cards** (Employee Identity)
- **Front Side**: Bottom-right corner (10.5mm × 10.5mm)
- **Back Side**: Bottom-center (11mm × 11mm)
- **Controller**: `PrintIdCardController`
- **Generator**: `IdCardGeneratorService`
- **Views**: `print/id-card.blade.php`, `documents/id_cards/print.blade.php`

### 2. **Certificates**
- **Location**: Top-left corner
- **Controller**: `PrintCertificateController`
- **Generator**: `CertificateGeneratorService`
- **Views**: `certificates/print.blade.php`, `certificates/template.blade.php`

### 3. **Official Letters**
- **Location**: Bottom-left area
- **Controller**: `PrintLetterController`
- **Generator**: `LetterGeneratorService`
- **Views**: `documents/letters/standard.blade.php`

### 4. **Money Receipts**
- **Location**: Bottom area
- **Controller**: `MoneyReceiptController`
- **Views**: `documents/money-receipts/default.blade.php`

## Code Examples

### Usage in Controllers

```php
use App\Services\QrCodeService;

class PrintCertificateController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCode,
    ) {}

    public function show(Certificate $certificate)
    {
        // Get verification URL
        $verifyUrl = $this->qrCode->verificationUrl($certificate);
        
        // Generate SVG QR code (uses default size of 5)
        $qrSvg = $this->qrCode->svg($certificate);
        
        // Or generate PNG data URI (if needed)
        $qrPng = $this->qrCode->pngDataUri($certificate);

        return view('certificates.print', [
            'qrSvg' => $qrSvg,
            'verifyUrl' => $verifyUrl,
        ]);
    }
}
```

### Usage in Views

```blade
<!-- Displaying SVG QR code -->
<div class="qr-code">
    {!! $qrSvg !!}
</div>

<!-- Displaying verification URL -->
<p>Scan to verify: {{ $verifyUrl }}</p>
```

### Usage in Services/Generators

```php
use App\Services\QrCodeService;

class CertificateGeneratorService
{
    public function __construct(
        protected QrCodeService $qr,
    ) {}

    public function buildContext(Certificate $certificate): array
    {
        // Get SVG QR code with default size
        $qrSvg = $this->qr->svg($certificate);
        
        // Get verification URL
        $verifyUrl = $this->qr->verificationUrl($certificate);

        return [
            'qr_svg' => $qrSvg,
            'verify_url' => $verifyUrl,
            // ... other context
        ];
    }
}
```

### Custom Size (if needed)

```php
// Use custom module size (not recommended, use default for consistency)
$qrSvg = $this->qrCode->svg($certificate, 6);  // Larger QR code
$qrSvg = $this->qrCode->svg($certificate, 4);  // Smaller QR code
```

## Service Methods

### `verificationUrl(Model|string $data): string`
Returns the full verification URL for a model or string identifier.

```php
$url = $qrCode->verificationUrl($certificate);  // Uses verification_hash
$url = $qrCode->verificationUrl('ref-123');     // Uses provided string
```

### `svg(Model|string $data, int $moduleSize = 5): string`
Generates QR code as SVG string (recommended for PDFs).

```php
$svg = $qrCode->svg($certificate);           // Default size (5)
$svg = $qrCode->svg($certificate, 6);        // Custom size
```

### `pngDataUri(Model|string $data, int $moduleSize = 5): string`
Generates QR code as PNG base64 data URI (fallback for compatibility).

```php
$dataUri = $qrCode->pngDataUri($certificate);
```

### Deprecated Methods (Legacy)

```php
// Not recommended - external API dependency
$qrCode->generateUrl($data, 256);    // External API URL
$qrCode->generateDataUri($data, 256); // Fetch from external API
```

## Environment Configuration

### Required Environment Variables

```env
# Verification base URL (where QR codes point to)
VERIFY_BASE_URL=https://chrsd.org     # Production
VERIFY_BASE_URL=http://127.0.0.1:8001 # Local development
```

### Configuration File

See `config/qr-code.php` for system-wide QR code settings.

## Migration Notes

### From Old System

**Before** (Inconsistent):
```php
// Different sizes, different services
$qrUrl = $oldService->generateUrl($verifyUrl, 256);  // External API, 256px
$qrSvg = $verificationService->svg($cert, 6);        // SVG, module size 6
$qrSvg = $verificationService->svg($letter, 4);      // SVG, module size 4
```

**After** (Standardized):
```php
// Single service, consistent approach
$qrSvg = $qrCode->svg($certificate);  // SVG, module size 5
$qrSvg = $qrCode->svg($letter);        // SVG, module size 5
$qrSvg = $qrCode->svg($receipt);       // SVG, module size 5
```

### Files Updated

**Controllers**:
- `PrintIdCardController` - Now uses unified service
- `PrintCertificateController` - Imports unified service
- `PrintLetterController` - Imports unified service
- `MoneyReceiptController` - Now injects QR service

**Services**:
- `CertificateGeneratorService` - Uses unified service with default size
- `IdCardGeneratorService` - Uses unified service with default size
- `LetterGeneratorService` - Uses unified service with default size
- `QrCodeService` - New unified service (main)
- `Verification/QrCodeService` - Deprecated facade (backward compatibility)

**Views**:
- All document views updated to use SVG QR codes
- Removed direct DNS2D library calls from Money Receipt view

## Troubleshooting

### QR Code Not Scanning

1. **Check module size** - Ensure size is appropriate (default 5)
2. **Verify URL** - Confirm `VERIFY_BASE_URL` environment variable is set
3. **Check SVG rendering** - Some PDFs don't render SVG; try PNG fallback

### URL Changes

When `VERIFY_BASE_URL` environment variable changes:
- Old printed documents will encode old URL in QR code
- New prints will encode new URL
- Both QRs should work if both endpoints are active

### Backward Compatibility

Old `Verification\QrCodeService` is available as facade but deprecated:
- Use new `App\Services\QrCodeService` for new code
- Don't import deprecated service in new files

## Future Enhancements

Potential improvements for future consideration:
- QR code color customization (logo, colors)
- Dynamic URL encoding options
- Batch QR generation for bulk prints
- QR code analytics tracking
- Interactive QR code test page in admin
