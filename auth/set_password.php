<?php
// SBO Nexus: forced password change after the Adviser resets a password.
// The student signs in with the temporary password (or Google) and must
// choose a new password here before using anything else.

require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
if (empty($_SESSION['must_change_password'])) {
    redirect('../index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();

    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        redirect('logout.php');
    } elseif (strlen($new) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (password_verify($new, $row['password_hash'])) {
        $error = 'Choose a different password from the temporary one.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?");
        $stmt->bind_param('si', $hash, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['must_change_password'] = 0;
        log_activity($conn, $_SESSION['user_id'], $_SESSION['email'] ?? '', 'password_changed', '', 'After an Adviser reset');
        redirect('../index.php?msg=password_changed');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set a new password · SBO Nexus</title>
<link rel="icon" type="image/png" href="../assets/sbo-logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<?php include '../includes/style.php'; ?>
<?php include '../includes/auth-green.php'; ?>
<meta name="theme-color" content="#0b6b3a">
</head>
<body>

<div class="auth-wrap">

    <div class="auth-heading">
        <p class="auth-eyebrow">Northern Bukidnon State College</p>
        <h1 class="auth-title">SBO Nexus</h1>
    </div>

    <div class="auth-card" style="max-width:480px;">

        <div class="auth-brand">
            <img src="../assets/sbo-logo.png" alt="SBO logo" class="auth-logo">
        </div>

        <?php if ($error): ?>
            <div class="flash flash-err"><?= h($error) ?></div>
        <?php endif; ?>

        <div class="flash" style="margin-bottom:16px;">
            <strong>Set a new password.</strong><br>
            Your Adviser reset your password. Choose a new one that only you know to continue.
        </div>

        <form method="post" action="set_password.php">
            <?= csrf_input() ?>

            <div class="field">
                <label>New password</label>
                <input type="password" name="new_password" placeholder="At least 8 characters" required minlength="8" autocomplete="new-password">
            </div>

            <div class="field">
                <label>Confirm new password</label>
                <input type="password" name="confirm_password" placeholder="Retype password" required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn btn-primary full-w">Save and continue</button>
        </form>

        <p class="auth-foot">
            <a href="logout.php" style="font-weight:600;">Log out</a>
        </p>

    </div>

</div>

</body>
</html>
