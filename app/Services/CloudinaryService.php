<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Uploads happen client-side via Cloudinary's unsigned upload widget (see
 * onboarding/wizard.blade.php and hotel/rooms/*.blade.php) — the browser
 * talks to Cloudinary directly and we just store the returned URL.
 * This service only handles the one thing that must happen server-side:
 * deleting an asset by its public_id when a room photo/video is removed.
 */
class CloudinaryService
{
    public function destroy(string $publicId, string $resourceType = 'image'): bool
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if (blank($cloudName) || blank($apiKey) || blank($apiSecret)) {
            Log::warning('Cloudinary not configured — skipping remote delete.', ['public_id' => $publicId]);
            return false;
        }

        $timestamp = time();
        $signature = sha1("public_id={$publicId}&timestamp={$timestamp}{$apiSecret}");

        try {
            $response = Http::asForm()->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/{$resourceType}/destroy",
                [
                    'public_id' => $publicId,
                    'timestamp' => $timestamp,
                    'api_key' => $apiKey,
                    'signature' => $signature,
                ]
            );

            return $response->successful() && in_array($response->json('result'), ['ok', 'not found']);
        } catch (\Throwable $e) {
            Log::error('Cloudinary destroy failed: '.$e->getMessage());
            return false;
        }
    }
}
