<?php

namespace App\Services\Verification;

use App\Services\QrCodeService as UnifiedQrCodeService;
use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use App\Services\QrCodeService directly instead.
 * This class is kept for backward compatibility only.
 */
class QrCodeService
{
    private UnifiedQrCodeService $unified;

    public function __construct()
    {
        $this->unified = app(UnifiedQrCodeService::class);
    }

    /** @deprecated Use UnifiedQrCodeService::verificationUrl() */
    public function verificationUrl(Model $model): string
    {
        return $this->unified->verificationUrl($model);
    }

    /** @deprecated Use UnifiedQrCodeService::svg() */
    public function svg(Model $model, int $size = 5): string
    {
        return $this->unified->svg($model, $size);
    }

    /** @deprecated Use UnifiedQrCodeService::pngDataUri() */
    public function pngDataUri(Model $model, int $size = 5): string
    {
        return $this->unified->pngDataUri($model, $size);
    }
}
