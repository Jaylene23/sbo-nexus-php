<?php
// SBO Nexus — Google OAuth configuration
// This file must never contain the Google Client Secret.

require_once __DIR__ . '/../vendor/autoload.php';

// Tolerate small clock drift between this server and Google's servers.
// Without this, the JWT library rejects Google's identity token the
// instant this machine's clock is even a few seconds behind real time
// (e.g. "Cannot handle token with iat prior to ..."), which is common
// on Windows/XAMPP setups where the system clock drifts.
\Firebase\JWT\JWT::$leeway = 60;

function google_client($createState = false)
{
    static $client = null;

    if ($client instanceof Google\Client) {
        return $client;
    }

    $clientId = getenv('GOOGLE_CLIENT_ID') ?: '';
    $clientSecret = getenv('GOOGLE_CLIENT_SECRET') ?: '';
    $redirectUri = getenv('GOOGLE_REDIRECT_URI')
        ?: 'http://localhost/sbo-nexus-php/auth/google_callback.php';

    if ($clientId === '' || $clientSecret === '') {
        throw new RuntimeException(
            'Google OAuth is not configured. Check your .env file.'
        );
    }

    $client = new Google\Client();

    $client->setClientId($clientId);
    $client->setClientSecret($clientSecret);
    $client->setRedirectUri($redirectUri);

    // We only need the user's identity for SBO Nexus login.
    $client->addScope('openid');
    $client->addScope('email');
    $client->addScope('profile');

    // Generate OAuth state only when starting the authorization request.
    // The callback must not replace the state saved before redirecting to Google.
    if ($createState) {
        $state = bin2hex(random_bytes(32));
        $_SESSION['google_oauth_state'] = $state;
        $client->setState($state);
    }

    $client->setAccessType('online');
    $client->setPrompt('select_account');

    return $client;
}
