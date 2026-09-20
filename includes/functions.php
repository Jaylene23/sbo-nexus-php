<?php
// SBO Nexus: Event Management module — data access helpers

function get_events($conn, $status = null, $created_by_user_id = null, $search = null) {
    $sql = "SELECT * FROM events WHERE 1=1";
    $types = '';
    $params = [];

    if ($status) {
        $sql .= " AND status = ?";
        $types .= 's';
        $params[] = $status;
    }
    if ($created_by_user_id) {
        $sql .= " AND created_by = ?";
        $types .= 'i';
        $params[] = $created_by_user_id;
    }
    if ($search) {
        $sql .= " AND (title LIKE ? OR venue LIKE ? OR category LIKE ?)";
        $like = '%' . $search . '%';
        $types .= 'sss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
    $sql .= " ORDER BY event_date ASC";

    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_event($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

function get_registrations($conn, $event_id) {
    $stmt = $conn->prepare("SELECT r.*, u.full_name, u.student_id, u.year_level, u.course FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ? ORDER BY r.registered_at ASC");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_registrant_avatars($conn, $event_id, $limit = 4) {
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.photo FROM registrations r JOIN users u ON u.id = r.user_id WHERE r.event_id = ? ORDER BY r.registered_at ASC LIMIT ?");
    $stmt->bind_param('ii', $event_id, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// Every approved event a student is registered for, soonest first —
// used by both My Schedule's calendar and its agenda list.
function get_my_schedule_events($conn, $user_id) {
    $stmt = $conn->prepare("SELECT e.* FROM registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND e.status = 'approved' ORDER BY e.event_date ASC, e.event_time ASC");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function count_registrations($conn, $event_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id = ?");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['c'];
}

function count_attended($conn, $event_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM registrations WHERE event_id = ? AND attended = 1");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['c'];
}

function get_event_report($conn, $event_id) {
    ensure_event_management_schema($conn);
    $stmt = $conn->prepare("SELECT * FROM event_reports WHERE event_id = ?");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: ['event_id' => $event_id, 'narrative' => '', 'recommendations' => '', 'updated_at' => null];
}

function total_registrations($conn) {
    $result = $conn->query("SELECT COUNT(*) AS c FROM registrations");
    return (int) $result->fetch_assoc()['c'];
}

function is_past($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

function event_end_date($event) {
    return !empty($event['event_end_date']) ? $event['event_end_date'] : ($event['event_date'] ?? null);
}
function event_is_past($event) {
    $endDate = event_end_date($event);
    if (!$endDate) return false;
    $end = $endDate . (!empty($event['event_end_time']) ? ' ' . $event['event_end_time'] : ' 23:59:59');
    return strtotime($end) < time();
}

/** Whether "now" falls inside the event's date/time range, with a small
 *  grace window on each side so attendance check-in isn't blocked by a
 *  student arriving a few minutes early/late or minor schedule slack. */
function event_is_ongoing($event, $graceMinutes = 30) {
    $start = $event['event_date'] . ' ' . ($event['event_time'] ?: '00:00:00');
    $endDate = event_end_date($event);
    $end = $endDate . ' ' . ($event['event_end_time'] ?: '23:59:59');

    $startTs = strtotime($start) - ($graceMinutes * 60);
    $endTs = strtotime($end) + ($graceMinutes * 60);
    $now = time();

    return $now >= $startTs && $now <= $endTs;
}

/** Smallest and largest check-in range an officer may set, in meters. */
if (!defined('ATTENDANCE_MIN_RADIUS_METERS')) define('ATTENDANCE_MIN_RADIUS_METERS', 20);
if (!defined('ATTENDANCE_MAX_RADIUS_METERS')) define('ATTENDANCE_MAX_RADIUS_METERS', 500);
/** Check-ins are refused when the phone reports a worse GPS accuracy than this. */
if (!defined('ATTENDANCE_MAX_ACCURACY_METERS')) define('ATTENDANCE_MAX_ACCURACY_METERS', 100);

/** Clamp a requested check-in range into the allowed limits. */
function clamp_geofence_radius($value) {
    $r = (int) $value;
    if ($r <= 0) {
        $r = defined('ATTENDANCE_GEOFENCE_RADIUS_METERS') ? (int) ATTENDANCE_GEOFENCE_RADIUS_METERS : 100;
    }
    return max(ATTENDANCE_MIN_RADIUS_METERS, min(ATTENDANCE_MAX_RADIUS_METERS, $r));
}

/** The check-in range, in meters, that applies to one event. */
function event_geofence_radius($event) {
    return clamp_geofence_radius($event['geofence_radius'] ?? 0);
}

/**
 * Whether students may self check-in right now, and why not if they may not.
 * 'auto'   follows the event schedule (30 minutes of grace before and after).
 * 'open'   the officer started attendance, so it can begin early, but it still
 *          ends automatically 30 minutes after the event finishes.
 * 'paused' the officer paused it.
 * 'closed' the officer stopped it.
 * Returns ['open' => bool, 'reason' => 'open'|'auto'|'paused'|'closed'|'not_ongoing'|'ended'].
 */
function attendance_checkin_state($event) {
    $status = $event['attendance_status'] ?? 'auto';
    if ($status === 'closed') {
        return ['open' => false, 'reason' => 'closed'];
    }
    if ($status === 'paused') {
        return ['open' => false, 'reason' => 'paused'];
    }
    if ($status === 'open') {
        $endTs = strtotime(event_end_date($event) . ' ' . ($event['event_end_time'] ?: '23:59:59')) + (30 * 60);
        return time() <= $endTs ? ['open' => true, 'reason' => 'open'] : ['open' => false, 'reason' => 'ended'];
    }
    return event_is_ongoing($event) ? ['open' => true, 'reason' => 'auto'] : ['open' => false, 'reason' => 'not_ongoing'];
}

/** Short text a student sees when check-in is not available. */
function attendance_state_text($reason) {
    switch ($reason) {
        case 'closed': return 'Attendance is closed by the organizers';
        case 'paused': return 'Check-in is paused by the organizers';
        case 'ended': return 'Attendance for this event has ended';
        default: return 'Check-in opens once the event starts';
    }
}

/** Great-circle distance between two lat/lng points, in meters. */
function haversine_distance_meters($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
function event_duration_days($event) {
    $start = $event['event_date'] ?? null; $end = event_end_date($event);
    if (!$start || !$end) return 1;
    return max(1, (int)(new DateTime($start))->diff(new DateTime($end))->days + 1);
}
function fmt_event_date_range($event) {
    $start=$event['event_date']??''; $end=event_end_date($event);
    if (!$start) return ''; if (!$end || $start===$end) return fmt_date($start);
    return date('Y',strtotime($start))===date('Y',strtotime($end)) ? date('M j',strtotime($start)).'–'.date('M j, Y',strtotime($end)) : fmt_date($start).'–'.fmt_date($end);
}
function fmt_event_time_range($event) {
    $from=$event['event_time']??null; $to=$event['event_end_time']??null;
    if ($from && $to) return fmt_time($from).' – '.fmt_time($to);
    if ($from) return fmt_time($from); if ($to) return 'Until '.fmt_time($to); return '—';
}

function fmt_date($date) {
    return date('M j, Y', strtotime($date));
}

function fmt_time($time) {
    if (!$time) return '';
    return date('g:i A', strtotime($time));
}

function fmt_datetime_full($datetime) {
    if (!$datetime) return '';
    return date('M j, Y g:i A', strtotime($datetime));
}

/** Groups registration rows by year level (1st-4th Year, plus a catch-all
 *  for unset), alphabetical by name within each group. Used by both the
 *  full registrants roster and the attendance-only list. */
function group_registrations_by_year($regs) {
    $groups = [];
    foreach ($regs as $r) {
        $yl = $r['year_level'] ?: 'Year level not set';
        $groups[$yl][] = $r;
    }
    foreach ($groups as &$group) {
        usort($group, fn($a, $b) => strcasecmp($a['full_name'], $b['full_name']));
    }
    unset($group);
    return $groups;
}

function get_feedback($conn, $event_id) {
    $stmt = $conn->prepare("SELECT f.*, u.full_name, u.role FROM event_feedback f JOIN users u ON u.id = f.user_id WHERE f.event_id = ? ORDER BY f.created_at ASC");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function fmt_datetime($ts) {
    return date('M j, g:i A', strtotime($ts));
}

function fmt_month($date) {
    return strtoupper(date('M', strtotime($date)));
}

function fmt_day($date) {
    return date('j', strtotime($date));
}

function fmt_year($date) {
    return date('Y', strtotime($date));
}

function get_officers($conn) {
    $result = $conn->query("SELECT * FROM users WHERE role = 'officer' ORDER BY full_name ASC");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_user($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}



/** Ensure event media/organizer tables and cover-photo support exist for existing installations. */
function ensure_event_management_schema($conn) {
    static $done = false;
    if ($done) return;
    $done = true;

    // Ensure the media and organizer tables exist on older installations.
    @$conn->query("CREATE TABLE IF NOT EXISTS event_photos (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id INT UNSIGNED NOT NULL,
      file_path VARCHAR(255) NOT NULL,
      is_cover TINYINT(1) NOT NULL DEFAULT 0,
      uploaded_by INT UNSIGNED NOT NULL,
      uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
      FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    foreach ([
        ['events','event_end_date',"ALTER TABLE events ADD COLUMN event_end_date DATE NULL AFTER event_date"],
        ['events','event_end_time',"ALTER TABLE events ADD COLUMN event_end_time TIME NULL AFTER event_time"],
        ['poll_options','image_path',"ALTER TABLE poll_options ADD COLUMN image_path VARCHAR(255) NULL AFTER option_text"]
    ] as $migration) {
        $check = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$migration[0]}' AND COLUMN_NAME = '{$migration[1]}'");
        if ($check && $check->num_rows === 0) @ $conn->query($migration[2]);
    }
    @$conn->query("UPDATE events SET event_end_date = event_date WHERE event_end_date IS NULL");

    @$conn->query("CREATE TABLE IF NOT EXISTS event_organizers (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      added_by INT UNSIGNED NOT NULL,
      committee VARCHAR(100) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE,
      UNIQUE KEY unique_event_organizer (event_id, user_id)
    ) ENGINE=InnoDB");

    // Older installations already have event_organizers but not the
    // committee column — add it so committee assignment works there too.
    $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_organizers' AND COLUMN_NAME = 'committee'");
    if ($col && $col->num_rows === 0) {
        @$conn->query("ALTER TABLE event_organizers ADD COLUMN committee VARCHAR(100) NULL AFTER added_by");
    }

    // Older installations already have registrations but not the columns
    // that record HOW attendance was marked (self geofenced check-in vs.
    // an officer manually ticking the box) and WHEN.
    $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'checkin_method'");
    if ($col && $col->num_rows === 0) {
        @$conn->query("ALTER TABLE registrations ADD COLUMN checkin_method ENUM('manual','geofence') NULL AFTER attended");
    }
    $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'registrations' AND COLUMN_NAME = 'checked_in_at'");
    if ($col && $col->num_rows === 0) {
        @$conn->query("ALTER TABLE registrations ADD COLUMN checked_in_at TIMESTAMP NULL AFTER checkin_method");
    }

    // Older student records used the short course name. Use the full BSIT name everywhere.
    @$conn->query("UPDATE users SET course = 'Bachelor of Science in Information Technology' WHERE course = 'BS Information Technology'");

    // Attendance security: each event gets its own check-in range and an
    // officer-controlled pause switch, and every self check-in keeps the
    // location evidence so officers can review it.
    foreach ([
        ['events', 'geofence_radius', "ALTER TABLE events ADD COLUMN geofence_radius SMALLINT UNSIGNED NOT NULL DEFAULT 100"],
        ['users', 'must_change_password', "ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0"],
        ['registrations', 'checkin_lat', "ALTER TABLE registrations ADD COLUMN checkin_lat DECIMAL(10,7) NULL"],
        ['registrations', 'checkin_lng', "ALTER TABLE registrations ADD COLUMN checkin_lng DECIMAL(10,7) NULL"],
        ['registrations', 'checkin_distance_m', "ALTER TABLE registrations ADD COLUMN checkin_distance_m INT NULL"],
        ['registrations', 'checkin_accuracy_m', "ALTER TABLE registrations ADD COLUMN checkin_accuracy_m INT NULL"],
    ] as $m) {
        $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$m[0]}' AND COLUMN_NAME = '{$m[1]}'");
        if ($col && $col->num_rows === 0) {
            @$conn->query($m[2]);
        }
    }

    // The officer's attendance control: auto (follows the event schedule),
    // open (started by the officer), paused, or closed (stopped).
    $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'attendance_status'");
    if ($col && $col->num_rows === 0) {
        $added = @$conn->query("ALTER TABLE events ADD COLUMN attendance_status ENUM('auto','open','paused','closed') NOT NULL DEFAULT 'auto'");
        if ($added) {
            // Carry over events paused with the earlier on/off switch, if it exists.
            $old = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'events' AND COLUMN_NAME = 'checkin_paused'");
            if ($old && $old->num_rows > 0) {
                @$conn->query("UPDATE events SET attendance_status = 'paused' WHERE checkin_paused = 1");
            }
        }
    }

    // Per-event accomplishment report: an officer-editable narrative that
    // gets embedded into that event's downloadable report alongside the
    // auto-computed registration/attendance stats.
    @$conn->query("CREATE TABLE IF NOT EXISTS event_reports (
      event_id INT UNSIGNED NOT NULL PRIMARY KEY,
      narrative TEXT NULL,
      recommendations TEXT NULL,
      updated_by INT UNSIGNED NULL,
      updated_at TIMESTAMP NULL,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
      FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    // The editable accomplishment report: one saved document per officer and event
    // (event_id is NULL for the officer's all-events summary).
    @$conn->query("CREATE TABLE IF NOT EXISTS report_documents (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id INT UNSIGNED NOT NULL,
      event_id INT UNSIGNED NULL,
      template VARCHAR(30) NOT NULL DEFAULT 'standard',
      content MEDIUMTEXT NOT NULL,
      updated_at TIMESTAMP NULL,
      KEY idx_report_doc_owner (user_id, event_id),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $col = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'event_photos' AND COLUMN_NAME = 'is_cover'");
    if ($col && $col->num_rows === 0) {
        @$conn->query("ALTER TABLE event_photos ADD COLUMN is_cover TINYINT(1) NOT NULL DEFAULT 0 AFTER file_path");
    }

    // Older SBO Nexus databases may not have these officer profile fields yet.
    foreach ([
        ['users','position_title',"ALTER TABLE users ADD COLUMN position_title VARCHAR(100) NULL AFTER photo"],
        ['users','bio',"ALTER TABLE users ADD COLUMN bio VARCHAR(500) NULL AFTER position_title"]
    ] as $migration) {
        $check = @$conn->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$migration[0]}' AND COLUMN_NAME = '{$migration[1]}'");
        if ($check && $check->num_rows === 0) @ $conn->query($migration[2]);
    }
}

function get_event_organizers($conn, $event_id) {
    ensure_event_management_schema($conn);
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.photo, u.position_title, u.bio, eo.id AS organizer_id, eo.committee
                           FROM event_organizers eo
                           JOIN users u ON u.id = eo.user_id
                           WHERE eo.event_id = ?
                           ORDER BY eo.created_at ASC, u.full_name ASC");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_event_organizer_candidates($conn, $event_id) {
    ensure_event_management_schema($conn);
    $stmt = $conn->prepare("SELECT u.id, u.full_name, u.email, u.position_title
                           FROM users u
                           WHERE u.role IN ('officer','admin')
                             AND u.id NOT IN (SELECT user_id FROM event_organizers WHERE event_id = ?)
                           ORDER BY u.full_name ASC");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}
function get_event_hero_photo($conn, $event_id) {
    $stmt = $conn->prepare("SELECT file_path FROM event_photos WHERE event_id = ? ORDER BY is_cover DESC, uploaded_at DESC LIMIT 1");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? $row['file_path'] : null;
}

function get_event_photos($conn, $event_id) {
    $stmt = $conn->prepare("SELECT * FROM event_photos WHERE event_id = ? ORDER BY uploaded_at DESC");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_rating_summary($conn, $event_id) {
    $stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(rating) AS rating_count FROM event_feedback WHERE event_id = ? AND rating IS NOT NULL");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ['avg' => $row['avg_rating'] ? round($row['avg_rating'], 1) : null, 'count' => (int) $row['rating_count']];
}

function initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $sub = function_exists('mb_substr') ? 'mb_substr' : 'substr';
    $first = $sub($parts[0] ?? '', 0, 1);
    $last = count($parts) > 1 ? $sub($parts[count($parts) - 1], 0, 1) : '';
    return function_exists('mb_strtoupper') ? mb_strtoupper($first . $last) : strtoupper($first . $last);
}

function avatar_html($user, $size = 40) {
    if (!empty($user['photo'])) {
        return '<img src="' . h($user['photo']) . '" class="avatar" style="width:' . $size . 'px;height:' . $size . 'px;" alt="">';
    }
    return '<span class="avatar avatar-fallback" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size * 0.4) . 'px;">' . h(initials($user['full_name'] ?? '?')) . '</span>';
}

function star_html($rating, $size = 14) {
    if (!$rating) return '';
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= round($rating) ? '★' : '☆';
    }
    return '<span style="color:var(--gold-dark);font-size:' . $size . 'px;letter-spacing:1px;">' . $out . '</span>';
}

// Category icons echo the four elements on the actual ICS shield: code
// brackets, cloud/network, a lightbulb (innovation), and a camera/reel
// (multimedia) — so the same visual language carries from the logo into
// the app itself rather than using generic unrelated icons.
function category_color($category) {
    $colors = [
        'Academic' => '#5B3E96',
        'Sports' => '#C6473B',
        'Cultural' => '#8A5A18',
        'Outreach' => '#0F49A7',
        'Organizational' => '#83630F',
    ];
    return $colors[$category] ?? '#0B5013';
}

function category_icon($category) {
    $icons = [
        'Academic' => '<path d="M8 9l-4 3 4 3M16 9l4 3-4 3M13 6l-2 12"/>',
        'Sports' => '<circle cx="12" cy="12" r="8"/><path d="M12 4v16M4 12h16M6.3 6.3l11.4 11.4M17.7 6.3L6.3 17.7"/>',
        'Cultural' => '<rect x="3" y="6" width="14" height="12" rx="2"/><circle cx="10" cy="12" r="3.2"/><path d="M17 10l4-2v8l-4-2"/>',
        'Outreach' => '<path d="M17.5 17a4 4 0 000-8 5.5 5.5 0 00-10.6 1.6A4 4 0 008 18h9.5z"/>',
        'Organizational' => '<path d="M9 18h6M10 21h4M12 3a5.5 5.5 0 00-3 10.1c.6.4 1 1.1 1 1.9h4c0-.8.4-1.5 1-1.9A5.5 5.5 0 0012 3z"/>',
    ];
    $path = $icons[$category] ?? $icons['Organizational'];
    return '<svg class="cat-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

function icon($name, $class = 'meta-icon') {
    $icons = [
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'pin' => '<path d="M12 21s7-6.5 7-11.5a7 7 0 10-14 0C5 14.5 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.3"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17.5" cy="9" r="2.3"/><path d="M15.5 14.2a5 5 0 014.5 5.8"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.3-4.3"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'hourglass' => '<path d="M6 3h12M6 21h12M7 3c0 5 5 6.5 5 9s-5 4-5 9M17 3c0 5-5 6.5-5 9s5 4 5 9"/>',
        'clipboard' => '<rect x="5" y="4" width="14" height="17" rx="2"/><rect x="9" y="2.5" width="6" height="3.5" rx="1"/><path d="M9 12h6M9 16h6"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.9 3.1-7 7-7s7 3.1 7 7"/>',
        'lock' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6l7-3z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8v.01"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 015 0c0 1.5-2.5 2-2.5 3.5M12 16.5v.01"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-down' => '<path d="M6 9l6 6 6-6"/>',
        'home' => '<path d="M3 10.5L12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5M9.5 21v-6h5v6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'camera' => '<path d="M4 8h3l1.5-2h7L17 8h3v10H4z"/><circle cx="12" cy="13" r="3"/>',
        'upload' => '<path d="M12 16V4M7 9l5-5 5 5M5 20h14"/>',
        'graduation' => '<path d="M3 9l9-5 9 5-9 5-9-5z"/><path d="M7 12v5c3 2 5 2 10 0v-5"/><path d="M21 10v5"/>',
        'book' => '<path d="M4 5a3 3 0 013-3h13v17H7a3 3 0 00-3 3z"/><path d="M7 2v17"/>',
        'building' => '<path d="M4 21h16M6 21V9h12v12M9 9V5h6v4M8 13h2M14 13h2M8 17h2M14 17h2"/>',
        'star' => '<path d="M12 3l2.6 5.8 6.2.6-4.6 4.2 1.3 6.2L12 16.9 6.5 19.8l1.3-6.2L3.2 9.4l6.2-.6L12 3z"/>',
    ];
    $path = $icons[$name] ?? '';
    return '<svg class="' . h($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

/** The green archer artwork (assets/archer.png) used in place of the old floating diamond decorations. */
function archer_icon($class = 'archer-icon') {
    return '<img class="' . h($class) . '" src="assets/archer.png" alt="" width="512" height="512" aria-hidden="true" draggable="false">';
}

/** The SBO officer positions the Adviser can choose from. Edit this list to match your organization. */
function sbo_officer_positions() {
    return [
        'Governor',
        'Vice Governor',
        'Secretary',
        'Assistant Secretary',
        'Treasurer',
        'Auditor',
        'Public Information Officer',
        'Business Manager',
        'Peace Officer',
        '1st Year Representative',
        '2nd Year Representative',
        '3rd Year Representative',
        '4th Year Representative',
    ];
}

/**
 * Turns the position form fields into a clean position name, or null if invalid.
 * $selected is one of the listed positions, or '__other' together with a typed $custom name.
 */
function resolve_officer_position($selected, $custom) {
    $selected = trim((string) $selected);
    if ($selected === '__other') {
        $name = trim(preg_replace('/\s+/', ' ', strip_tags((string) $custom)));
        if ($name === '') {
            return null;
        }
        return function_exists('mb_substr') ? mb_substr($name, 0, 100) : substr($name, 0, 100);
    }
    return in_array($selected, sbo_officer_positions(), true) ? $selected : null;
}

function days_until_label($date) {
    $days = (int) ceil((strtotime($date) - strtotime(date('Y-m-d'))) / 86400);
    if ($days < 0) return null;
    if ($days === 0) return 'Today';
    if ($days === 1) return 'Tomorrow';
    if ($days <= 14) return "In $days days";
    return null;
}

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ===== Polls & Surveys module =====

function get_polls($conn, $status = null) {
    $sql = "SELECT p.*, u.full_name AS creator_name FROM polls p JOIN users u ON u.id = p.created_by WHERE 1=1";
    $types = '';
    $params = [];
    if ($status) {
        $sql .= " AND p.status = ?";
        $types .= 's';
        $params[] = $status;
    }
    $sql .= " ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function get_poll($conn, $id) {
    $stmt = $conn->prepare("SELECT p.*, u.full_name AS creator_name FROM polls p JOIN users u ON u.id = p.created_by WHERE p.id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row;
}

// Returns options with vote counts for a poll.
function get_poll_options($conn, $poll_id) {
    $stmt = $conn->prepare("SELECT o.*, (SELECT COUNT(*) FROM poll_votes v WHERE v.option_id = o.id) AS votes FROM poll_options o WHERE o.poll_id = ? ORDER BY o.id ASC");
    $stmt->bind_param('i', $poll_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function total_poll_votes($conn, $poll_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM poll_votes WHERE poll_id = ?");
    $stmt->bind_param('i', $poll_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['c'];
}

function get_my_vote($conn, $poll_id, $user_id) {
    $stmt = $conn->prepare("SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?");
    $stmt->bind_param('ii', $poll_id, $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ? (int) $row['option_id'] : null;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// Validates and saves an uploaded image. Returns the web-relative path
// (e.g. "uploads/events/abc123.jpg") on success, or null on failure.
function save_uploaded_image($fileField, $destDir, $webPrefix, $fileIndex = null) {
    if (!isset($_FILES[$fileField])) return null;
    $file = $_FILES[$fileField];
    if ($fileIndex !== null && is_array($file['error'])) {
        if (!isset($file['error'][$fileIndex]) || $file['error'][$fileIndex] !== UPLOAD_ERR_OK) return null;
        $file = [
            'error' => $file['error'][$fileIndex],
            'tmp_name' => $file['tmp_name'][$fileIndex],
            'size' => $file['size'][$fileIndex]
        ];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $tmpPath = $file['tmp_name'];

    // Verify it's actually an image (not just a renamed .php file) by
    // reading its real content, not trusting the client-supplied name/type.
    $info = @getimagesize($tmpPath);
    if ($info === false) return null;

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];
    if (!isset($allowed[$info[2]])) return null;

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxBytes) return null;

    $ext = $allowed[$info[2]];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!is_dir($destDir)) {
        @mkdir($destDir, 0755, true);
    }
    $destPath = rtrim($destDir, '/') . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destPath)) return null;

    return rtrim($webPrefix, '/') . '/' . $filename;
}

// Activity log (sign-ins, sign-outs, password changes)
require_once __DIR__ . '/activity_log.php';
