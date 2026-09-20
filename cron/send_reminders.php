<?php
// SBO Nexus: daily event reminder job.
// Emails registered students about events that start tomorrow.
//
// Run it once a day (for example every day at 4:00 PM):
//   Command line:  php /path/to/sbo-nexus-php/cron/send_reminders.php
//   By URL:        https://your-domain/sbo-nexus-php/cron/send_reminders.php?key=YOUR_SBO_CRON_KEY
//
// The URL form needs SBO_CRON_KEY to be set in .env. Without it, only the command line works.

require __DIR__ . '/../config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/mailer.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    $expected = (string) getenv('SBO_CRON_KEY');
    $given = (string) ($_GET['key'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit("Forbidden\n");
    }
}

$report = send_event_reminders($conn);

if ($report['status'] === 'mail_not_configured') {
    echo "Email is not configured. Set the SBO_SMTP settings in .env.\n";
    exit(1);
}
if (!$report['events']) {
    echo "No events start tomorrow, nothing to send.\n";
    exit(0);
}
foreach ($report['events'] as $row) {
    echo '#' . $row['id'] . ' ' . $row['title'] . ': ';
    echo $row['skipped'] ? 'skipped (' . $row['skipped'] . ')' : 'sent ' . $row['sent'] . ', failed ' . $row['failed'];
    echo "\n";
}
