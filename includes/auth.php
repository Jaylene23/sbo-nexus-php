<?php
// SBO Nexus: Event Management module — session and access-control helpers
// Include this AFTER config.php on every protected page.

if (session_status() === PHP_SESSION_NONE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function is_nbsc_email($email) {
    $email = strtolower(trim((string) $email));
    return (bool) preg_match('/^[^@\\s]+@nbsc\\.edu\\.ph$/', $email);
}


function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf_token() {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Security check failed. Please go back, refresh the page, and try again.');
    }
}

function inject_csrf_fields($html) {
    $field = csrf_input();
    return preg_replace_callback(
        '/<form\b([^>]*\bmethod\s*=\s*["\']post["\'][^>]*)>/i',
        function ($match) use ($field) {
            if (stripos($match[0], 'name="csrf_token"') !== false || stripos($match[0], "name='csrf_token'") !== false) {
                return $match[0];
            }
            return $match[0] . $field;
        },
        $html
    );
}

function current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name'],
        'email' => $_SESSION['email'] ?? null,
        'student_id' => $_SESSION['student_id'] ?? null,
        'year_level' => $_SESSION['year_level'] ?? null,
        'course' => $_SESSION['course'] ?? null,
        'photo' => $_SESSION['photo'] ?? null,
    ];
}

// Call at the top of any page that requires login.
// Pass an array of allowed roles to also restrict by role.
function require_login($allowedRoles = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: auth/login.php');
        exit;
    }
    // After the Adviser resets a password, the student must choose a new one
    // before using anything else in the system.
    if (!empty($_SESSION['must_change_password'])) {
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== 'set_password.php') {
            $inAuthFolder = basename(dirname($_SERVER['SCRIPT_NAME'] ?? '')) === 'auth';
            header('Location: ' . ($inAuthFolder ? '' : 'auth/') . 'set_password.php');
            exit;
        }
    }
    if ($allowedRoles && !in_array($_SESSION['role'], $allowedRoles, true)) {
        header('Location: index.php?error=not_allowed');
        exit;
    }
}

function require_role($roles) {
    $roles = is_array($roles) ? $roles : [$roles];
    require_login($roles);
}

function log_in_user($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['student_id'] = $user['student_id'];
    $_SESSION['year_level'] = $user['year_level'];
    $_SESSION['course'] = $user['course'];
    $_SESSION['photo'] = $user['photo'] ?? null;
    $_SESSION['must_change_password'] = !empty($user['must_change_password']) ? 1 : 0;
}

// Call after any action that changes the logged-in user's own profile
// fields, so the session (and every page using current_user()) reflects
// the change immediately without requiring a fresh login.
function refresh_session_field($field, $value) {
    $_SESSION[$field] = $value;
}
