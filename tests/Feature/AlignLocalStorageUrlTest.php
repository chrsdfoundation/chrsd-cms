<?php

namespace Tests\Feature;

use App\Http\Middleware\AlignLocalStorageUrlWithHost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Local dev: APP_URL=localhost while the panel is opened on 127.0.0.1 makes
 * storage URLs cross-origin, so Filament file fields never load existing files.
 */
class AlignLocalStorageUrlTest extends TestCase
{
    private function alignFor(string $env, string $appUrl, string $requestUrl): void
    {
        $this->app['env'] = $env;
        config(['app.url' => $appUrl]);
        Storage::forgetDisk('public');

        (new AlignLocalStorageUrlWithHost)->handle(
            Request::create($requestUrl),
            fn () => response('ok'),
        );
    }

    public function test_local_loopback_mismatch_points_public_disk_at_request_origin(): void
    {
        $this->alignFor('local', 'http://localhost:8000', 'http://127.0.0.1:8000/admin');

        $this->assertSame('http://127.0.0.1:8000/storage', config('filesystems.disks.public.url'));
        $this->assertSame('http://127.0.0.1:8000/storage/417/photo.jpg', Storage::disk('public')->url('417/photo.jpg'));
    }

    public function test_port_mismatch_on_the_same_loopback_host_is_aligned(): void
    {
        $this->alignFor('local', 'http://localhost:8000', 'http://localhost:8001/admin');

        $this->assertSame('http://localhost:8001/storage', config('filesystems.disks.public.url'));
    }

    public function test_matching_origin_keeps_app_url(): void
    {
        // Compare with the disk URL as it was before the call: CI and local APP_URL differ.
        $before = config('filesystems.disks.public.url');

        $this->alignFor('local', 'http://localhost:8000', 'http://localhost:8000/admin');

        $this->assertSame($before, config('filesystems.disks.public.url'));
    }

    public function test_production_never_changes_the_storage_url(): void
    {
        $before = config('filesystems.disks.public.url');

        $this->alignFor('production', 'https://apps.chrsd.org', 'https://apps.chrsd.org/admin');

        $this->assertSame($before, config('filesystems.disks.public.url'));
    }

    public function test_non_loopback_request_host_keeps_app_url(): void
    {
        // Never trust an arbitrary Host header to decide where media URLs point.
        $before = config('filesystems.disks.public.url');

        $this->alignFor('local', 'http://localhost:8000', 'http://attacker.example/admin');

        $this->assertSame($before, config('filesystems.disks.public.url'));
    }
}
