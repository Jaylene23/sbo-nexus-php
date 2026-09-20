<?php
// SBO Nexus: activity log (who logged in, and when).
//
// Every sign-in, failed sign-in, sign-out, new account, and password change is
// written to the login_logs table so the Adviser can review it. Logging never
// blocks the user: if anything goes wrong while writing a log line, the error
// is recorded in the PHP error log and the page carries on.

/** How long log lines are kept, in days. Older lines are removed automatically. */
if (!defined('ACTIVITY_LOG_KEEP_DAYS')) define('ACTIVITY_LOG_KEEP_DAYS', 180);

function ensure_activity_log_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;
    @$conn->query("CREATE TABLE IF NOT EXISTS login_logs (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NULL,
      email VARCHAR(150) NOT NULL DEFAULT '',
      event VARCHAR(20) NOT NULL,
      method VARCHAR(20) NOT NULL DEFAULT '',
      detail VARCHAR(120) NOT NULL DEFAULT '',
      ip VARCHAR(45) NOT NULL DEFAULT '',
      user_agent VARCHAR(255) NOT NULL DEFAULT '',
      created_at DATETIME NOT NULL,
      KEY idx_log_created (created_at),
      KEY idx_log_user (user_id, created_at),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** The kinds of activity that are recorded, with the words shown to the Adviser. */
function activity_event_labels() {
    return [
        'login' => 'Logged in',
        'login_failed' => 'Failed login',
        'logout' => 'Logged out',
        'register' => 'Account created',
        'password_changed' => 'Changed password',
        'password_reset' => 'Password reset by Adviser',
    ];
}

/** The visitor's IP address. Set SBO_TRUST_PROXY=1 in .env when the site is behind Cloudflare or a similar proxy. */
function activity_client_ip() {
    if (getenv('SBO_TRUST_PROXY') === '1') {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $first = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($first, FILTER_VALIDATE_IP)) {
                    return $first;
                }
            }
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

/** Writes one activity line. $userId may be null (for example a failed login with an unknown email). */
function log_activity($conn, $userId, $email, $event, $method = '', $detail = '') {
    try {
        ensure_activity_log_schema($conn);

        $uid = $userId ? (int) $userId : null;
        $email = mb_substr(strtolower(trim((string) $email)), 0, 150);
        $event = substr((string) $event, 0, 20);
        $method = substr((string) $method, 0, 20);
        $detail = mb_substr((string) $detail, 0, 120);
        $ip = activity_client_ip();
        $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $when = date('Y-m-d H:i:s'); // same clock as the rest of the system (Asia/Manila)

        $stmt = $conn->prepare("INSERT INTO login_logs (user_id, email, event, method, detail, ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) return;
        $stmt->bind_param('isssssss', $uid, $email, $event, $method, $detail, $ip, $ua, $when);
        $stmt->execute();
        $stmt->close();

        // Tidy old lines now and then, so the table never grows without limit.
        if (random_int(1, 50) === 1) {
            $cut = date('Y-m-d H:i:s', strtotime('-' . (int) ACTIVITY_LOG_KEEP_DAYS . ' days'));
            $del = $conn->prepare("DELETE FROM login_logs WHERE created_at < ?");
            if ($del) {
                $del->bind_param('s', $cut);
                $del->execute();
                $del->close();
            }
        }
    } catch (Throwable $e) {
        error_log('SBO Nexus activity log failed: ' . $e->getMessage());
    }
}

/** "Chrome on Android" style summary of a browser's user agent text, plus a device type. */
function describe_user_agent($ua) {
    $ua = (string) $ua;
    if ($ua === '') {
        return ['label' => 'Unknown device', 'type' => ''];
    }

    if (stripos($ua, 'Edg/') !== false || stripos($ua, 'EdgA/') !== false) $browser = 'Edge';
    elseif (stripos($ua, 'OPR/') !== false || stripos($ua, 'Opera') !== false) $browser = 'Opera';
    elseif (stripos($ua, 'SamsungBrowser') !== false) $browser = 'Samsung Internet';
    elseif (stripos($ua, 'Firefox') !== false || stripos($ua, 'FxiOS') !== false) $browser = 'Firefox';
    elseif (stripos($ua, 'Chrome') !== false || stripos($ua, 'CriOS') !== false) $browser = 'Chrome';
    elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
    else $browser = 'Browser';

    if (stripos($ua, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($ua, 'Android') !== false) $os = 'Android';
    elseif (stripos($ua, 'iPhone') !== false || stripos($ua, 'iPad') !== false || stripos($ua, 'iPod') !== false) $os = 'iOS';
    elseif (stripos($ua, 'Mac OS X') !== false || stripos($ua, 'Macintosh') !== false) $os = 'macOS';
    elseif (stripos($ua, 'CrOS') !== false) $os = 'Chromebook';
    elseif (stripos($ua, 'Linux') !== false) $os = 'Linux';
    else $os = 'unknown system';

    if (stripos($ua, 'iPad') !== false || stripos($ua, 'Tablet') !== false || (stripos($ua, 'Android') !== false && stripos($ua, 'Mobile') === false)) $type = 'Tablet';
    elseif (stripos($ua, 'Mobi') !== false || stripos($ua, 'iPhone') !== false) $type = 'Phone';
    else $type = 'Computer';

    return ['label' => $browser . ' on ' . $os, 'type' => $type];
}

/**
 * Builds the WHERE part shared by the list, the count, and the CSV export.
 * $f keys: q, role (student|officer|admin), ev, from, to (Y-m-d).
 */
function activity_log_where(array $f, &$types, &$params) {
    $where = ['1=1'];
    $types = '';
    $params = [];

    if (($f['role'] ?? '') !== '' && in_array($f['role'], ['student', 'officer', 'admin'], true)) {
        $where[] = 'u.role = ?';
        $types .= 's';
        $params[] = $f['role'];
    }
    if (($f['ev'] ?? '') !== '' && array_key_exists($f['ev'], activity_event_labels())) {
        $where[] = 'l.event = ?';
        $types .= 's';
        $params[] = $f['ev'];
    }
    if (!empty($f['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['from'])) {
        $where[] = 'l.created_at >= ?';
        $types .= 's';
        $params[] = $f['from'] . ' 00:00:00';
    }
    if (!empty($f['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['to'])) {
        $where[] = 'l.created_at <= ?';
        $types .= 's';
        $params[] = $f['to'] . ' 23:59:59';
    }
    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = '(u.full_name LIKE ? OR l.email LIKE ? OR l.ip LIKE ?)';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }
    return implode(' AND ', $where);
}

function activity_log_count($conn, array $f) {
    ensure_activity_log_schema($conn);
    $where = activity_log_where($f, $types, $params);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_logs l LEFT JOIN users u ON u.id = l.user_id WHERE $where");
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $n = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $n;
}

function activity_log_rows($conn, array $f, $limit, $offset) {
    ensure_activity_log_schema($conn);
    $where = activity_log_where($f, $types, $params);
    $sql = "SELECT l.*, u.full_name, u.role AS user_role
            FROM login_logs l LEFT JOIN users u ON u.id = l.user_id
            WHERE $where ORDER BY l.created_at DESC, l.id DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types .= 'ii';
    $params[] = (int) $limit;
    $params[] = (int) $offset;
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/** Map of user id to the time of their most recent successful login. */
function activity_last_logins($conn) {
    ensure_activity_log_schema($conn);
    $out = [];
    $res = $conn->query("SELECT user_id, MAX(created_at) t FROM login_logs WHERE event = 'login' AND user_id IS NOT NULL GROUP BY user_id");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[(int) $r['user_id']] = $r['t'];
        }
    }
    return $out;
}

/** Friendly "5 min ago" text for recent times. Empty for anything older than a day. */
function activity_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 0) return '';
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return '';
}
