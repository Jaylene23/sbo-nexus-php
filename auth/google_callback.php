<?php
// SBO Nexus — Google OAuth callback
// Only verified @nbsc.edu.ph accounts are allowed.
//
// Role model:
//   admin   = Adviser/Admin
//   student = regular student OR SBO Officer
//
// New public registrations are ALWAYS students.
// SBO Officer status must be assigned by the Adviser/Admin.

require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

function google_login_error($message)
{
    $_SESSION['login_error'] = $message;
    redirect('login.php');
}

try {

    // Loading includes/google.php pulls in vendor/autoload.php, which can
    // throw (e.g. PHP version too old for the Google client library).
    // Keeping this require inside the try block means that failure shows
    // the friendly login-page error below instead of a raw fatal error.
    require '../includes/google.php';

    /*
     * ---------------------------------------------------------
     * 1. Check whether Google returned an OAuth error
     * ---------------------------------------------------------
     */

    if (isset($_GET['error'])) {
        google_login_error(
            'Google sign-in was cancelled or could not be completed.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 2. Verify OAuth state
     * ---------------------------------------------------------
     */

    $state = $_GET['state'] ?? '';
    $savedState = $_SESSION['google_oauth_state'] ?? '';

    unset($_SESSION['google_oauth_state']);

    if (
        !is_string($state) ||
        $state === '' ||
        $savedState === '' ||
        !hash_equals($savedState, $state)
    ) {
        http_response_code(403);
        exit(
            'Security check failed. Please start Google sign-in again.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 3. Get authorization code
     * ---------------------------------------------------------
     */

    $code = $_GET['code'] ?? '';

    if (!is_string($code) || $code === '') {
        google_login_error(
            'Google did not return a valid authorization code.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 4. Exchange authorization code for token
     * ---------------------------------------------------------
     */

    $client = google_client(false);

    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (isset($token['error'])) {

        error_log(
            'Google OAuth token error: ' .
            json_encode($token, JSON_UNESCAPED_SLASHES)
        );

        $detail = $token['error_description']
            ?? $token['error']
            ?? 'unknown_error';

        // Keep the normal user-facing message, but write Google's
        // actual reason to the PHP error log for XAMPP debugging.
        error_log('Google OAuth token error detail: ' . $detail);

        google_login_error(
            'Google sign-in could not be completed. Please try again.'
        );
    }

    $client->setAccessToken($token);


    /*
     * ---------------------------------------------------------
     * 5. Get Google identity token
     * ---------------------------------------------------------
     */

    $idToken = $token['id_token'] ?? '';

    if ($idToken === '') {
        google_login_error(
            'Google did not return a valid identity token.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 6. Verify Google ID token
     * ---------------------------------------------------------
     */

    $payload = $client->verifyIdToken($idToken);

    if (!$payload || empty($payload['email'])) {
        google_login_error(
            'Google could not verify your account.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 7. Get verified Google account information
     * ---------------------------------------------------------
     */

    $email = strtolower(trim($payload['email']));

    $googleId = trim(
        (string)($payload['sub'] ?? '')
    );

    $googleName = trim(
        (string)($payload['name'] ?? '')
    );


    /*
     * ---------------------------------------------------------
     * 8. Google email must be verified
     * ---------------------------------------------------------
     */

    if (empty($payload['email_verified'])) {

        google_login_error(
            'Your Google email must be verified before you can use SBO Nexus.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 9. Only official NBSC accounts are allowed
     * ---------------------------------------------------------
     */

    if (!is_nbsc_email($email)) {

        google_login_error(
            'Only official @nbsc.edu.ph Google accounts can log in to SBO Nexus.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 10. Google account must have a subject ID
     * ---------------------------------------------------------
     */

    if ($googleId === '') {

        google_login_error(
            'Google did not provide a valid account identifier.'
        );
    }


    /*
     * ---------------------------------------------------------
     * 11. Look for an existing SBO Nexus account
     * ---------------------------------------------------------
     *
     * We first search by email because the student's
     * official NBSC email is the primary identity.
     */

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ? LIMIT 1"
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare database query.'
        );
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    /*
     * ---------------------------------------------------------
     * 12. Existing user
     * ---------------------------------------------------------
     */

    if ($user) {

        /*
         * Extra database-side NBSC protection.
         */
        if (!is_nbsc_email($user['email'])) {

            google_login_error(
                'This account is not authorized to access SBO Nexus.'
            );
        }


        /*
         * -----------------------------------------------------
         * Check whether another account already owns this
         * Google ID.
         * -----------------------------------------------------
         */

        $stmt = $conn->prepare(
            "SELECT id FROM users
             WHERE google_id = ?
             AND id <> ?
             LIMIT 1"
        );

        if (!$stmt) {
            throw new RuntimeException(
                'Unable to prepare Google account check.'
            );
        }

        $userId = (int)$user['id'];

        $stmt->bind_param(
            'si',
            $googleId,
            $userId
        );

        $stmt->execute();

        $googleOwner = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if ($googleOwner) {

            google_login_error(
                'This Google account is already linked to another SBO Nexus account.'
            );
        }


        /*
         * -----------------------------------------------------
         * Link Google ID to existing account.
         * -----------------------------------------------------
         *
         * This is safe because:
         *
         * Google verified the @nbsc.edu.ph email
         * AND
         * the email matches the existing SBO Nexus account.
         */

        if (
            empty($user['google_id']) ||
            $user['google_id'] !== $googleId
        ) {

            $stmt = $conn->prepare(
                "UPDATE users
                 SET google_id = ?
                 WHERE id = ?
                 LIMIT 1"
            );

            if (!$stmt) {
                throw new RuntimeException(
                    'Unable to link Google account.'
                );
            }

            $stmt->bind_param(
                'si',
                $googleId,
                $userId
            );

            if (!$stmt->execute()) {

                $stmt->close();

                throw new RuntimeException(
                    'Unable to save Google account.'
                );
            }

            $stmt->close();

            /*
             * Refresh the user record after linking.
             */

            $stmt = $conn->prepare(
                "SELECT * FROM users WHERE id = ? LIMIT 1"
            );

            if (!$stmt) {
                throw new RuntimeException(
                    'Unable to reload user account.'
                );
            }

            $stmt->bind_param('i', $userId);
            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            $stmt->close();
        }


        /*
         * -----------------------------------------------------
         * Existing role is preserved.
         * -----------------------------------------------------
         *
         * admin   = Adviser/Admin
         * student = Student
         *
         * We do NOT allow Google login to change the role.
         */

        log_in_user($user);
        log_activity($conn, $user['id'], $user['email'] ?? '', 'login', 'google');

        unset($_SESSION['login_error']);

        redirect('../index.php');
    }


    /*
     * ---------------------------------------------------------
     * 13. New Google account
     * ---------------------------------------------------------
     *
     * The Google account is valid but does not yet have
     * an SBO Nexus account.
     *
     * Store only the verified registration information in
     * the session temporarily.
     *
     * The actual student record is NOT created here.
     */

    $_SESSION['google_registration'] = [
        'google_id' => $googleId,
        'email'     => $email,
        'full_name' => $googleName,
        'verified'  => true,
    ];

    unset($_SESSION['login_error']);

    /*
     * Send the student to the normal registration form.
     */
    redirect('register.php');


} catch (Throwable $e) {

    error_log(
        'SBO Nexus Google OAuth exception: ' .
        $e->getMessage() .
        ' | file=' . $e->getFile() .
        ' | line=' . $e->getLine()
    );

    google_login_error(
        'Google sign-in could not be completed. Please try again.'
    );
}