<?php
// SBO Nexus — student registration
//
// Public registration always creates a STUDENT account.
//
// Role model:
//   admin   = Adviser/Admin
//   student = Regular Student OR SBO Officer
//
// SBO Officer status is assigned separately by the Adviser/Admin.
//
// Google registration:
//   A verified @nbsc.edu.ph Google account may pre-authorize
//   the registration form. The Google ID is saved with the
//   student account after successful registration.

require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

ob_start();

if (isset($_SESSION['user_id'])) {
    redirect('../index.php');
}


/*
 * ---------------------------------------------------------
 * Course / institute options
 * ---------------------------------------------------------
 */

// This system is only for BSIT students, so the course is fixed.
$courses = [
    'Bachelor of Science in Information Technology' => 'Institute of Computer Studies',
];
$fixedCourse = 'Bachelor of Science in Information Technology';

$yearLevels = [
    '1st Year',
    '2nd Year',
    '3rd Year',
    '4th Year'
];


/*
 * ---------------------------------------------------------
 * Google registration information
 * ---------------------------------------------------------
 *
 * google_callback.php places this information in the session
 * only after Google has successfully verified the account.
 */

$googleRegistration = $_SESSION['google_registration'] ?? null;

$googleRegistrationValid =
    is_array($googleRegistration) &&
    !empty($googleRegistration['verified']) &&
    !empty($googleRegistration['google_id']) &&
    !empty($googleRegistration['email']) &&
    is_nbsc_email($googleRegistration['email']);


/*
 * If the Google registration session is malformed,
 * remove it instead of trusting it.
 */

if (!$googleRegistrationValid) {

    if ($googleRegistration !== null) {
        unset($_SESSION['google_registration']);
    }

    $googleRegistration = null;
}


/*
 * Accounts can only be created through Google sign-in.
 * Anyone who opens this page without a verified Google registration
 * (for example by typing the address directly) is sent to the login page.
 */

if (!$googleRegistrationValid) {

    $_SESSION['login_error'] =
        'To create an account, use the "Continue with Google" button and sign in with your NBSC Google account.';

    redirect('login.php');
}


/*
 * ---------------------------------------------------------
 * Existing form values
 * ---------------------------------------------------------
 */

$error = '';

$old = [
    'full_name' => '',
    'email' => '',
    'student_id' => '',
    'year_level' => '',
    'course' => $fixedCourse
];


/*
 * If the registration was started through Google,
 * pre-fill the verified Google information.
 */

if ($googleRegistrationValid) {

    $old['full_name'] =
        trim((string)($googleRegistration['full_name'] ?? ''));

    $old['email'] =
        strtolower(trim((string)$googleRegistration['email']));
}


/*
 * ---------------------------------------------------------
 * Handle registration form
 * ---------------------------------------------------------
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf_token();


    /*
     * Read form values.
     */

    $old['full_name'] =
        trim($_POST['full_name'] ?? '');

    $old['email'] =
        strtolower(trim($_POST['email'] ?? ''));

    $old['student_id'] =
        trim($_POST['student_id'] ?? '');

    $old['year_level'] =
        $_POST['year_level'] ?? '';

    // The course is never taken from the form.
    $old['course'] = $fixedCourse;

    $password =
        $_POST['password'] ?? '';

    $confirm =
        $_POST['confirm_password'] ?? '';


    /*
     * -----------------------------------------------------
     * If Google registration was used, do NOT allow the
     * user to change the verified Google email.
     * -----------------------------------------------------
     */

    if ($googleRegistrationValid) {

        $verifiedGoogleEmail =
            strtolower(
                trim(
                    (string)$googleRegistration['email']
                )
            );

        if ($old['email'] !== $verifiedGoogleEmail) {

            $error =
                'The email address must match your verified NBSC Google account.';

            $old['email'] = $verifiedGoogleEmail;
        }
    }


    /*
     * -----------------------------------------------------
     * Required fields
     * -----------------------------------------------------
     */

    if (
        $old['full_name'] === '' ||
        $old['email'] === '' ||
        $old['student_id'] === '' ||
        $password === ''
    ) {

        $error =
            'Fill in all required student information.';
    }


    /*
     * -----------------------------------------------------
     * Official NBSC email
     * -----------------------------------------------------
     */

    elseif (!is_nbsc_email($old['email'])) {

        $error =
            'Registration is limited to official @nbsc.edu.ph email accounts.';
    }


    /*
     * -----------------------------------------------------
     * Course and year validation
     * -----------------------------------------------------
     */

    elseif (
        !in_array(
            $old['year_level'],
            $yearLevels,
            true
        )
    ) {

        $error =
            'Select your year level.';
    }


    /*
     * -----------------------------------------------------
     * Password validation
     * -----------------------------------------------------
     */

    elseif (strlen($password) < 8) {

        $error =
            'Password must be at least 8 characters.';
    }

    elseif ($password !== $confirm) {

        $error =
            'Passwords do not match.';
    }


    /*
     * -----------------------------------------------------
     * Database checks
     * -----------------------------------------------------
     */

    else {

        /*
         * Check duplicate email or student ID.
         */

        $stmt = $conn->prepare(
            "SELECT id
             FROM users
             WHERE email = ?
                OR student_id = ?
             LIMIT 1"
        );

        if (!$stmt) {

            $error =
                'Unable to check the existing account.';
        } else {

            $stmt->bind_param(
                'ss',
                $old['email'],
                $old['student_id']
            );

            $stmt->execute();

            $exists =
                $stmt
                    ->get_result()
                    ->fetch_assoc();

            $stmt->close();


            if ($exists) {

                $error =
                    'An account with that email or ID number already exists.';
            }


            /*
             * -------------------------------------------------
             * Create student account
             * -------------------------------------------------
             */

            else {

                /*
                 * Public registration can ONLY create a student.
                 */

                $role = 'student';

                /*
                 * New accounts are not SBO Officers by default.
                 *
                 * If your users table already has an officer
                 * status column, we will use it later.
                 *
                 * For now, Google registration only sets
                 * google_id.
                 */

                $hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );


                /*
                 * Google ID, if registration was started
                 * through Google.
                 */

                $googleId = null;

                if ($googleRegistrationValid) {

                    $googleId =
                        trim(
                            (string)$googleRegistration['google_id']
                        );
                }


                /*
                 * -------------------------------------------------
                 * Insert account
                 * -------------------------------------------------
                 *
                 * google_id is NULL for normal registration.
                 *
                 * google_id contains the verified Google subject
                 * for Google registration.
                 */

                $stmt = $conn->prepare(
                    "INSERT INTO users
                    (
                        role,
                        full_name,
                        email,
                        google_id,
                        student_id,
                        year_level,
                        course,
                        password_hash
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );


                if (!$stmt) {

                    $error =
                        'Unable to prepare the account registration.';
                } else {

                    $stmt->bind_param(
                        'ssssssss',
                        $role,
                        $old['full_name'],
                        $old['email'],
                        $googleId,
                        $old['student_id'],
                        $old['year_level'],
                        $old['course'],
                        $hash
                    );


                    if ($stmt->execute()) {

                        $newId =
                            $stmt->insert_id;

                        $stmt->close();


                        /*
                         * Retrieve the newly created user.
                         */

                        $stmt = $conn->prepare(
                            "SELECT *
                             FROM users
                             WHERE id = ?
                             LIMIT 1"
                        );

                        if (!$stmt) {

                            throw new RuntimeException(
                                'Unable to retrieve the new account.'
                            );
                        }

                        $stmt->bind_param(
                            'i',
                            $newId
                        );

                        $stmt->execute();

                        $user =
                            $stmt
                                ->get_result()
                                ->fetch_assoc();

                        $stmt->close();


                        if (!$user) {

                            throw new RuntimeException(
                                'The account was created but could not be loaded.'
                            );
                        }


                        /*
                         * Remove the temporary Google registration
                         * information after successful registration.
                         */

                        unset(
                            $_SESSION['google_registration']
                        );


                        /*
                         * Log the student in.
                         */

                        log_in_user($user);
                        log_activity($conn, $user['id'], $user['email'] ?? $old['email'], 'register', 'google');
                        log_activity($conn, $user['id'], $user['email'] ?? $old['email'], 'login', 'google');

                        redirect('../index.php');

                    } else {

                        $stmt->close();

                        $error =
                            'Unable to create the account. Please try again.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Student registration · SBO Nexus</title>

<link
    rel="icon"
    type="image/png"
    href="../assets/sbo-logo.png"
>

<link
    rel="preconnect"
    href="https://fonts.googleapis.com"
>

<link
    href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap"
    rel="stylesheet"
>

<?php include '../includes/style.php'; ?>
<?php include '../includes/auth-green.php'; ?>

    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#0b6b3a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="../icon-192.png">
</head>

<body>

<div class="auth-wrap">

    <div class="auth-heading">

        <p class="auth-eyebrow">
            Northern Bukidnon State College
        </p>

        <h1 class="auth-title">
            SBO Nexus
        </h1>

    </div>


    <div
        class="auth-card"
        style="max-width:480px;"
    >

        <div class="auth-brand">

            <img
                src="../assets/sbo-logo.png"
                alt="SBO logo"
                class="auth-logo"
            >

        </div>


        <?php if ($error): ?>

            <div class="flash flash-err">
                <?= h($error) ?>
            </div>

        <?php endif; ?>


        <?php if ($googleRegistrationValid): ?>

            <div
                class="flash"
                style="margin-bottom:16px;"
            >

                <strong>Google account verified.</strong>

                <br>

                Your official NBSC Google account is verified.
                Complete your student information below.

                <br><br>

                <strong>
                    <?= h($old['email']) ?>
                </strong>

            </div>

        <?php else: ?>

            <div
                class="flash"
                style="margin-bottom:16px;"
            >

                Registration is available only to official
                <strong>@nbsc.edu.ph</strong>
                accounts.

            </div>

        <?php endif; ?>


        <form
            method="post"
            action="register.php"
            id="regForm"
        >

            <div class="field">

                <label>
                    Full name
                </label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Juan Dela Cruz"
                    required
                    value="<?= h($old['full_name']) ?>"
                >

            </div>


            <div class="field">

                <label>
                    NBSC email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="you@nbsc.edu.ph"
                    required
                    value="<?= h($old['email']) ?>"
                    <?= $googleRegistrationValid ? 'readonly' : '' ?>
                >

                <p class="hint">

                    <?php if ($googleRegistrationValid): ?>

                        This email was verified by Google.

                    <?php else: ?>

                        Use your official Northern Bukidnon State College email address.

                    <?php endif; ?>

                </p>

            </div>


            <div class="row2">

                <div class="field">

                    <label>
                        ID number
                    </label>

                    <input
                        type="text"
                        name="student_id"
                        placeholder="2023-00123"
                        required
                        value="<?= h($old['student_id']) ?>"
                    >

                </div>


                <div class="field">

                    <label>
                        Year level
                    </label>

                    <select
                        name="year_level"
                        required
                    >

                        <option value="">
                            Select year level
                        </option>

                        <?php foreach ($yearLevels as $yl): ?>

                            <option
                                value="<?= h($yl) ?>"
                                <?= $old['year_level'] === $yl ? 'selected' : '' ?>
                            >

                                <?= h($yl) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <div class="row2">

                <div class="field">

                    <label>
                        Course
                    </label>

                    <input
                        type="text"
                        value="<?= h($fixedCourse) ?>"
                        disabled
                    >

                </div>


                <div class="field">

                    <label>
                        Institute
                    </label>

                    <input
                        type="text"
                        id="instituteField"
                        value="<?= h($courses[$fixedCourse]) ?>"
                        disabled
                    >

                </div>

            </div>


            <div class="row2">

                <div class="field">

                    <label>
                        Password
                    </label>

                    <input
                        type="password"
                        name="password"
                        placeholder="At least 8 characters"
                        required
                        minlength="8"
                    >

                </div>


                <div class="field">

                    <label>
                        Confirm password
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Retype password"
                        required
                        minlength="8"
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn btn-primary full-w"
            >
                Create student account
            </button>

        </form>


        <p class="auth-foot">

            Already have an account?

            <a
                href="login.php"
                style="color:var(--gold-dark);font-weight:600;"
            >
                Log in
            </a>

        </p>

    </div>

</div>




<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('../service-worker.js').catch(function (error) {
      console.warn('SBO Nexus service worker registration failed:', error);
    });
  });
}
</script>
</body>
</html>

<?php
echo inject_csrf_fields(
    ob_get_clean()
);
?>