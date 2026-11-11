<?php

namespace App\Domains\Pos\Services;

/**
 * Cimiento para POS offline (PWA / sync).
 *
 * TODO v1 iterativo:
 * - Service Worker + Cache API para shell del POS
 * - IndexedDB / local queue de ventas pendientes
 * - Endpoint de sync con idempotencia por client_uuid
 * - Reconciliación de stock al reconectar
 */
class OfflineSyncFoundation
{
    public function manifestPath(): string
    {
        return public_path('manifest.webmanifest');
    }

    public function serviceWorkerPath(): string
    {
        return public_path('sw.js');
    }

    /**
     * @return array{enabled: bool, version: string, endpoints: array<string, string>}
     */
    public function config(): array
    {
        return [
            'enabled' => false, // foundation only
            'version' => '0.1.0-scaffold',
            'endpoints' => [
                // TODO: Route::post('/pos/sync', ...)
                'sync' => '/pos/sync',
                'manifest' => '/manifest.webmanifest',
                'service_worker' => '/sw.js',
            ],
        ];
    }
}
