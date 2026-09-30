<?php
// SBO Nexus: download the activity log as a CSV file (Adviser only).
// Uses the same filters as the Activity Log page.

require 'config.php';
require 'includes/functions.php';
require 'includes/auth.php';

require_login();
require_role('admin');

$f = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'role' => (string) ($_GET['role'] ?? ''),
    'ev' => (string) ($_GET['ev'] ?? ''),
    'from' => (string) ($_GET['from'] ?? ''),
    'to' => (string) ($_GET['to'] ?? ''),
];

$rows = activity_log_rows($conn, $f, 5000, 0);
$labels = activity_event_labels();
$roleNames = ['student' => 'Student', 'officer' => 'SBO Officer', 'admin' => 'Adviser'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sbo-activity-log-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // lets Excel read accented letters correctly

// Stops a cell that starts with = + - or @ from being run as a formula in Excel.
$safe = function ($v) {
    $v = (string) $v;
    return ($v !== '' && strpbrk($v[0], "=+-@\t\r") !== false) ? "'" . $v : $v;
};

fputcsv($out, ['Date and time', 'Name', 'Email', 'Role', 'Activity', 'How', 'Details', 'IP address', 'Device']);
foreach ($rows as $r) {
    $dev = describe_user_agent($r['user_agent']);
    fputcsv($out, [
        $r['created_at'],
        $safe($r['full_name'] ?: 'Unknown account'),
        $safe($r['email']),
        $roleNames[$r['user_role'] ?? ''] ?? '',
        $labels[$r['event']] ?? $r['event'],
        $r['method'] === 'google' ? 'Google' : ($r['method'] === 'password' ? 'Password' : ''),
        $safe($r['detail']),
        $r['ip'],
        $dev['label'] . ($dev['type'] !== '' ? ' (' . $dev['type'] . ')' : ''),
    ]);
}
fclose($out);
