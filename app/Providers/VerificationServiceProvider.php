<?php

namespace App\Providers;

use App\Support\Blueprint\VerificationBlueprint;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class VerificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blueprint::macro('verificationColumns', function (): void {
            /** @var Blueprint $this */
            VerificationBlueprint::apply($this);
        });
    }
}
