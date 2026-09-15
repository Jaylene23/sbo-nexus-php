<?php
// SBO Nexus: Student / Officer / Admin login

require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

ob_start();

if (isset($_SESSION['user_id'])) {
    redirect('../index.php');
}

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Enter your NBSC email and password.';
    } elseif (!is_nbsc_email($email)) {
        log_activity($conn, null, $email, 'login_failed', 'password', 'Not an NBSC email');
        $error = 'Only @nbsc.edu.ph accounts can log in to SBO Nexus.';
    } else {
        $stmt = $conn->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();

        $user = $stmt->get_result()->fetch_assoc();

        $stmt->close();

        if (
            $user &&
            password_verify($password, $user['password_hash'])
        ) {
            log_in_user($user);
            log_activity($conn, $user['id'], $email, 'login', 'password');
            redirect('../index.php');
        } else {
            log_activity($conn, $user['id'] ?? null, $email, 'login_failed', 'password', $user ? 'Wrong password' : 'No account with this email');
            $error = 'That email and password don\'t match our records.';
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

<title>Log in · SBO Nexus</title>

<link
    rel="icon"
    type="image/png"
    href="../assets/sbo-logo.png"
>

<link rel="preconnect" href="https://fonts.googleapis.com">

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


<!-- =====================================================
     LOGIN PAGE
     ===================================================== -->

<div class="auth-wrap">


    <!-- HEADER -->

    <div class="auth-heading">

        <p class="auth-eyebrow">
            Northern Bukidnon State College
        </p>

        <h1 class="auth-title">
            SBO Nexus
        </h1>

    </div>


    <!-- LOGIN CARD -->

    <div class="auth-card">


        <!-- SBO LOGO -->

        <div class="auth-brand">

            <img
                src="../assets/sbo-logo.png"
                alt="SBO logo"
                class="auth-logo"
            >

        </div>


        <!-- ERROR -->

        <?php if ($error): ?>

            <div class="flash flash-err">

                <?= h($error) ?>

            </div>

        <?php endif; ?>


        <!-- =================================================
             PASSWORD LOGIN
             ================================================= -->

        <form
            method="post"
            action="login.php"
        >


            <!-- EMAIL -->

            <div class="field">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="you@nbsc.edu.ph"
                    required
                    value="<?= h($_POST['email'] ?? '') ?>"
                >

            </div>


            <!-- PASSWORD -->

            <div class="field">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <!-- LOGIN -->

            <button
                type="submit"
                class="btn btn-primary full-w"
            >
                Log in
            </button>


        </form>


        <!-- =================================================
             DIVIDER
             ================================================= -->

        <div
            style="
                display:flex;
                align-items:center;
                gap:12px;
                margin:14px 0;
                color:var(--muted,#777);
                font-size:13px;
            "
        >

            <span
                style="
                    height:1px;
                    background:rgba(0,0,0,.12);
                    flex:1;
                "
            ></span>

            <span>
                or
            </span>

            <span
                style="
                    height:1px;
                    background:rgba(0,0,0,.12);
                    flex:1;
                "
            ></span>

        </div>


        <!-- =================================================
             GOOGLE LOGIN
             ================================================= -->

        <a
            href="google_login.php"
            class="btn btn-google full-w"
            style="
                display:flex;
                align-items:center;
                justify-content:center;
                gap:10px;
                text-decoration:none;
            "
        >

            <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0;">
                <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"/>
                <path fill="#FBBC05" d="M3.964 10.707A5.41 5.41 0 013.682 9c0-.593.102-1.17.282-1.707V4.961H.957A8.996 8.996 0 000 9c0 1.452.348 2.827.957 4.039l3.007-2.332z"/>
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.166 6.656 3.58 9 3.580z"/>
            </svg>

            Continue with Google

        </a>




        <!-- =================================================
             NBSC GOOGLE INFORMATION
             ================================================= -->

        <p
            style="
                text-align:center;
                font-size:12px;
                color:var(--muted,#777);
                margin-top:14px;
            "
        >

            Google sign-in is limited to official

            <strong>
                @nbsc.edu.ph
            </strong>

            accounts.

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


<?php echo inject_csrf_fields(ob_get_clean()); ?>