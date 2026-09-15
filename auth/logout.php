<?php
// SBO Nexus: Event Management module, logout
require '../config.php';
require '../includes/functions.php';
require '../includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    log_activity($conn, (int) $_SESSION['user_id'], $_SESSION['email'] ?? '', 'logout');
}

$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
