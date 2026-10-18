<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Throwable;

class GoogleTokenVerifier
{
    public function verify(string $idToken): ?array
    {
        try {
            $client = new GoogleClient([
                'client_id' => config('services.google.client_id'),
            ]);

            $payload = $client->verifyIdToken($idToken);

            return is_array($payload) ? $payload : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
