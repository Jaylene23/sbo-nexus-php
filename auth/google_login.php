<?php
// SBO Nexus — start Google OAuth login

require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

try {
    // Loading includes/google.php pulls in vendor/autoload.php, which can
    // throw (e.g. PHP version too old for the Google client library).
    // Keeping this require inside the try block means that failure shows
    // the friendly login-page error below instead of a raw fatal error.
    require '../includes/google.php';

    $client = google_client(true);

    $authUrl = $client->createAuthUrl();

    header(
        'Location: ' .
        filter_var($authUrl, FILTER_SANITIZE_URL)
    );

    exit;

} catch (Throwable $e) {
    error_log(
        'SBO Nexus Google OAuth start error: ' .
        $e->getMessage()
    );

    $_SESSION['login_error'] =
        'Google sign-in is not configured correctly.';

    redirect('login.php');
}