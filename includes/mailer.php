<?php
// SBO Nexus: email notifications (PHPMailer over SMTP).
// Sends a notice to students when the Adviser approves a new event.
// Mail problems never block the approval: every failure is logged and ignored.

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

// True only when the .env has enough SMTP settings to send mail.
function mail_is_configured() {
    return getenv('SBO_SMTP_HOST') && getenv('SBO_SMTP_USER') && getenv('SBO_SMTP_PASS');
}

// Public address of the system, used for links inside emails.
// Set SBO_BASE_URL in .env (for example https://yourdomain/sbo-nexus-php).
function sbo_base_url() {
    $configured = trim((string) getenv('SBO_BASE_URL'));
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function build_mailer() {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = getenv('SBO_SMTP_HOST');
    $mail->Port = (int) (getenv('SBO_SMTP_PORT') ?: 587);
    $mail->SMTPAuth = true;
    $mail->Username = getenv('SBO_SMTP_USER');
    $mail->Password = getenv('SBO_SMTP_PASS');
    $secure = strtolower((string) getenv('SBO_SMTP_SECURE'));
    $mail->SMTPSecure = $secure === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->SMTPKeepAlive = true;
    $mail->Timeout = 15;
    $mail->CharSet = 'UTF-8';
    $from = getenv('SBO_MAIL_FROM') ?: getenv('SBO_SMTP_USER');
    $mail->setFrom($from, getenv('SBO_MAIL_FROM_NAME') ?: 'SBO Nexus');
    return $mail;
}

// Adds the notification columns on older installations (safe to run repeatedly).
function ensure_email_notification_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    foreach ([
        ['users', 'email_notifications', "ALTER TABLE users ADD COLUMN email_notifications TINYINT(1) NOT NULL DEFAULT 1"],
        ['events', 'notified_at', "ALTER TABLE events ADD COLUMN notified_at DATETIME NULL"],
        ['events', 'reminder_sent_at', "ALTER TABLE events ADD COLUMN reminder_sent_at DATETIME NULL"],
    ] as $m) {
        $check = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[0]}' AND COLUMN_NAME = '{$m[1]}'");
        if ($check && $check->num_rows === 0) {
            @$conn->query($m[2]);
        }
    }
}

function format_event_when($event) {
    $when = date('l, F j, Y', strtotime($event['event_date']));
    if (!empty($event['event_end_date']) && $event['event_end_date'] !== $event['event_date']) {
        $when .= ' to ' . date('l, F j, Y', strtotime($event['event_end_date']));
    }
    if (!empty($event['event_time'])) {
        $when .= ', ' . date('g:i A', strtotime($event['event_time']));
        if (!empty($event['event_end_time'])) {
            $when .= ' to ' . date('g:i A', strtotime($event['event_end_time']));
        }
    }
    return $when;
}

// Builds the HTML and plain-text bodies shared by the notice and reminder emails.
function build_event_email($event, $heading, $intro, $buttonLabel, $footerNote) {
    $title = (string) $event['title'];
    $link = sbo_base_url() . '/index.php?view=eventDetail&event_id=' . (int) $event['id'];
    $when = format_event_when($event);
    $venue = (string) $event['venue'];
    $category = (string) ($event['category'] ?? '');
    $desc = trim((string) ($event['description'] ?? ''));
    if (mb_strlen($desc) > 300) {
        $desc = mb_substr($desc, 0, 300) . '...';
    }

    $e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

    $html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#222;">'
        . '<h2 style="margin:0 0 4px;color:#0b6b3a;">' . $e($heading) . '</h2>'
        . '<p style="margin:0 0 16px;color:#666;">' . $e($intro) . '</p>'
        . '<table style="border-collapse:collapse;width:100%;font-size:14px;">'
        . '<tr><td style="padding:6px 0;color:#666;width:90px;">Event</td><td style="padding:6px 0;"><strong>' . $e($title) . '</strong></td></tr>'
        . '<tr><td style="padding:6px 0;color:#666;">When</td><td style="padding:6px 0;">' . $e($when) . '</td></tr>'
        . '<tr><td style="padding:6px 0;color:#666;">Where</td><td style="padding:6px 0;">' . $e($venue) . '</td></tr>'
        . ($category !== '' ? '<tr><td style="padding:6px 0;color:#666;">Category</td><td style="padding:6px 0;">' . $e($category) . '</td></tr>' : '')
        . '</table>'
        . ($desc !== '' ? '<p style="font-size:14px;line-height:1.6;">' . nl2br($e($desc)) . '</p>' : '')
        . ($buttonLabel !== null ? '<p style="margin:22px 0;"><a href="' . $e($link) . '" style="background:#0b6b3a;color:#fff;padding:11px 20px;border-radius:8px;text-decoration:none;font-size:14px;">' . $e($buttonLabel) . '</a></p>' : '<p style="margin:18px 0;"></p>')
        . '<p style="font-size:12px;color:#888;">' . $e($footerNote) . ' You can turn these emails off under Profile, then Privacy &amp; Security.</p>'
        . '</div>';

    $text = "$heading\n$intro\n\nEvent: $title\nWhen: $when\nWhere: $venue\n"
        . ($desc !== '' ? "\n$desc\n" : '')
        . ($buttonLabel !== null ? "\n$buttonLabel: $link\n" : '')
        . "\n$footerNote You can turn these emails off under Profile > Privacy & Security.\n";

    return [$html, $text];
}

// Sends one message to many people in BCC batches so nobody sees other addresses.
// Returns ['sent' => int, 'failed' => int].
function send_bcc_batches(array $recipients, $subject, $html, $text) {
    $out = ['sent' => 0, 'failed' => 0];
    @set_time_limit(120);
    ignore_user_abort(true);

    try {
        $mail = build_mailer();
    } catch (Throwable $err) {
        error_log('SBO Nexus mail setup failed: ' . $err->getMessage());
        $out['failed'] = count($recipients);
        return $out;
    }

    foreach (array_chunk($recipients, 40) as $batch) {
        try {
            $mail->clearAddresses();
            $mail->clearBCCs();
            $mail->addAddress($mail->From, $mail->FromName);
            foreach ($batch as $addr) {
                $mail->addBCC($addr);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = $text;
            $mail->send();
            $out['sent'] += count($batch);
        } catch (Throwable $err) {
            $out['failed'] += count($batch);
            error_log('SBO Nexus email batch failed: ' . $err->getMessage());
        }
    }
    $mail->smtpClose();
    return $out;
}

// Emails every opted-in student about an approved event, once per event.
// Returns ['sent' => int, 'failed' => int, 'skipped' => string|null].
function notify_students_of_event($conn, $event) {
    $result = ['sent' => 0, 'failed' => 0, 'skipped' => null];

    if (!mail_is_configured()) {
        $result['skipped'] = 'mail_not_configured';
        return $result;
    }

    ensure_email_notification_schema($conn);

    // Claim the event first so a double click cannot send the notice twice.
    $claim = $conn->prepare("UPDATE events SET notified_at = NOW() WHERE id = ? AND notified_at IS NULL AND status = 'approved'");
    $claim->bind_param('i', $event['id']);
    $claim->execute();
    $claimed = $claim->affected_rows === 1;
    $claim->close();
    if (!$claimed) {
        $result['skipped'] = 'already_notified';
        return $result;
    }

    $recipients = [];
    $res = $conn->query("SELECT email FROM users WHERE role = 'student' AND email_notifications = 1 AND email <> ''");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $row['email'];
            }
        }
    }
    if (!$recipients) {
        $result['skipped'] = 'no_recipients';
        return $result;
    }

    list($html, $text) = build_event_email(
        $event,
        'New event: ' . $event['title'],
        'ICS Supreme Body Organization',
        'View event and register',
        'You received this because you have an SBO Nexus student account.'
    );
    $sent = send_bcc_batches($recipients, 'New SBO event: ' . $event['title'], $html, $text);
    $result['sent'] = $sent['sent'];
    $result['failed'] = $sent['failed'];

    error_log('SBO Nexus event #' . (int) $event['id'] . ' notice: sent ' . $result['sent'] . ', failed ' . $result['failed']);
    return $result;
}

// Emails students who registered for events that start tomorrow, once per event.
// Meant to run once a day (see cron/send_reminders.php).
// Returns a list of per-event results plus an overall status string.
function send_event_reminders($conn) {
    $report = ['status' => 'ok', 'events' => []];

    if (!mail_is_configured()) {
        $report['status'] = 'mail_not_configured';
        return $report;
    }

    ensure_email_notification_schema($conn);

    // PHP's timezone is Asia/Manila (set in config.php), so "tomorrow" matches the campus clock.
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'approved' AND event_date = ? AND reminder_sent_at IS NULL");
    $stmt->bind_param('s', $tomorrow);
    $stmt->execute();
    $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($events as $event) {
        $entry = ['id' => (int) $event['id'], 'title' => $event['title'], 'sent' => 0, 'failed' => 0, 'skipped' => null];

        $claim = $conn->prepare("UPDATE events SET reminder_sent_at = NOW() WHERE id = ? AND reminder_sent_at IS NULL");
        $claim->bind_param('i', $event['id']);
        $claim->execute();
        $claimed = $claim->affected_rows === 1;
        $claim->close();
        if (!$claimed) {
            $entry['skipped'] = 'already_reminded';
            $report['events'][] = $entry;
            continue;
        }

        $recipients = [];
        $rs = $conn->prepare("SELECT u.email FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ? AND u.role = 'student' AND u.email_notifications = 1 AND u.email <> ''");
        $rs->bind_param('i', $event['id']);
        $rs->execute();
        $rows = $rs->get_result();
        while ($row = $rows->fetch_assoc()) {
            if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $row['email'];
            }
        }
        $rs->close();

        if (!$recipients) {
            $entry['skipped'] = 'no_recipients';
            $report['events'][] = $entry;
            continue;
        }

        list($html, $text) = build_event_email(
            $event,
            'Reminder: ' . $event['title'] . ' is tomorrow',
            'You are registered for this event.',
            'View event details',
            'You received this because you registered for this event.'
        );
        $sent = send_bcc_batches($recipients, 'Reminder: ' . $event['title'] . ' is tomorrow', $html, $text);
        $entry['sent'] = $sent['sent'];
        $entry['failed'] = $sent['failed'];
        $report['events'][] = $entry;

        error_log('SBO Nexus event #' . (int) $event['id'] . ' reminder: sent ' . $entry['sent'] . ', failed ' . $entry['failed']);
    }

    return $report;
}

// Emails of opted-in students currently registered for an event.
// Call this BEFORE deleting the event, because deleting removes its registrations.
function get_registered_student_emails($conn, $eventId) {
    $emails = [];
    $stmt = $conn->prepare("SELECT u.email FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ? AND u.role = 'student' AND u.email_notifications = 1 AND u.email <> ''");
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if (filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = $row['email'];
        }
    }
    $stmt->close();
    return $emails;
}

// True when a cancellation notice is worth sending: the event was visible to
// students (approved) and has not finished yet.
function event_needs_cancel_notice($event) {
    if (!$event || $event['status'] !== 'approved') {
        return false;
    }
    $lastDay = !empty($event['event_end_date']) ? $event['event_end_date'] : $event['event_date'];
    return $lastDay >= date('Y-m-d');
}

// Tells registered students that an upcoming event was cancelled.
// Returns ['sent' => int, 'failed' => int, 'skipped' => string|null].
function notify_event_cancelled(array $event, array $recipients) {
    $result = ['sent' => 0, 'failed' => 0, 'skipped' => null];

    if (!mail_is_configured()) {
        $result['skipped'] = 'mail_not_configured';
        return $result;
    }
    if (!$recipients) {
        $result['skipped'] = 'no_recipients';
        return $result;
    }

    list($html, $text) = build_event_email(
        $event,
        'Event cancelled: ' . $event['title'],
        'The SBO has cancelled this event, so your registration was removed.',
        null,
        'You received this because you were registered for this event.'
    );
    $sent = send_bcc_batches($recipients, 'Cancelled: ' . $event['title'], $html, $text);
    $result['sent'] = $sent['sent'];
    $result['failed'] = $sent['failed'];

    error_log('SBO Nexus event #' . (int) $event['id'] . ' cancellation: sent ' . $result['sent'] . ', failed ' . $result['failed']);
    return $result;
}
