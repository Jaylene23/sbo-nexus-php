<?php
// SBO Nexus: Event Management module — form handler (PRG pattern)
require 'config.php';
require 'includes/functions.php';
require 'includes/auth.php';
require 'includes/mailer.php';
require 'includes/report_editor.php';

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
verify_csrf_token();
$me = current_user();
$role = $me['role'];
ensure_event_management_schema($conn);
$action = $_POST['action'] ?? '';

// -------- Officer: create event --------
if ($action === 'create_event' && $role === 'officer') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['event_date'] ?? '';
    $time = $_POST['event_time'] ?? null;
    $endDate = $_POST['event_end_date'] ?? $date;
    $endTime = $_POST['event_end_time'] ?? null;
    $venue = trim($_POST['venue'] ?? '');
    $category = $_POST['category'] ?? 'Academic';
    $capacity = null; // Events have unlimited registration capacity.
    $latitude = ($_POST['latitude'] ?? '') !== '' ? (float) $_POST['latitude'] : null;
    $longitude = ($_POST['longitude'] ?? '') !== '' ? (float) $_POST['longitude'] : null;
    // A pin only counts if both coordinates are present and within valid Earth ranges.
    if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        $latitude = null;
        $longitude = null;
    }

    if ($title === '' || $date === '' || $endDate === '' || $venue === '' || strtotime($endDate) < strtotime($date) || ($date === $endDate && $time && $endTime && strtotime($endTime) < strtotime($time))) {
        redirect('index.php?view=myEvents&error=invalid_event_dates');
    }

    $radius = clamp_geofence_radius($_POST['geofence_radius'] ?? 0);

    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_end_date, event_time, event_end_time, venue, latitude, longitude, geofence_radius, category, capacity, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->bind_param('sssssssddisii', $title, $desc, $date, $endDate, $time, $endTime, $venue, $latitude, $longitude, $radius, $category, $capacity, $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=myEvents&msg=submitted');
}

// -------- Officer: edit their own event (including its location pin) --------
if ($action === 'update_event' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $event = get_event($conn, $event_id);
    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $date = $_POST['event_date'] ?? '';
    $time = $_POST['event_time'] ?? null;
    $endDate = $_POST['event_end_date'] ?? $date;
    $endTime = $_POST['event_end_time'] ?? null;
    $venue = trim($_POST['venue'] ?? '');
    $category = $_POST['category'] ?? 'Academic';
    $capacity = null; // Events have unlimited registration capacity.
    $latitude = ($_POST['latitude'] ?? '') !== '' ? (float) $_POST['latitude'] : null;
    $longitude = ($_POST['longitude'] ?? '') !== '' ? (float) $_POST['longitude'] : null;
    if ($latitude === null || $longitude === null || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        $latitude = null;
        $longitude = null;
    }

    if ($title === '' || $date === '' || $endDate === '' || $venue === '' || strtotime($endDate) < strtotime($date) || ($date === $endDate && $time && $endTime && strtotime($endTime) < strtotime($time))) {
        redirect('index.php?view=editEvent&event_id=' . $event_id . '&error=invalid_event_dates');
    }

    $radius = clamp_geofence_radius($_POST['geofence_radius'] ?? 0);

    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, event_end_date=?, event_time=?, event_end_time=?, venue=?, latitude=?, longitude=?, geofence_radius=?, category=?, capacity=? WHERE id=? AND created_by=?");
    $stmt->bind_param('sssssssddisiii', $title, $desc, $date, $endDate, $time, $endTime, $venue, $latitude, $longitude, $radius, $category, $capacity, $event_id, $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=event_updated');
}

// -------- Officer: delete their own event --------
// Officers may delete any event they created, regardless of approval status.
// Related registrations, feedback, and photo records are removed by the
// database's ON DELETE CASCADE rules. Uploaded event image files are also
// cleaned up so deleting an event does not leave orphaned files.
if ($action === 'delete_event' && $role === 'officer') {
    $id = (int) ($_POST['event_id'] ?? 0);

    $stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND created_by = ?");
    $stmt->bind_param('ii', $id, $me['id']);
    $stmt->execute();
    $ownedEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ownedEvent) {
        redirect('index.php?view=myEvents');
    }

    // Before the event disappears, remember who must be told it was cancelled.
    // Only upcoming approved events that students could see need a notice.
    $cancelEvent = get_event($conn, $id);
    $cancelRecipients = [];
    if (event_needs_cancel_notice($cancelEvent) && mail_is_configured()) {
        ensure_email_notification_schema($conn);
        $cancelRecipients = get_registered_student_emails($conn, $id);
    }

    // Collect uploaded photo paths before the event row is deleted.
    $photoStmt = $conn->prepare("SELECT file_path FROM event_photos WHERE event_id = ?");
    $photoStmt->bind_param('i', $id);
    $photoStmt->execute();
    $photos = $photoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $photoStmt->close();

    $stmt = $conn->prepare("DELETE FROM events WHERE id = ? AND created_by = ?");
    $stmt->bind_param('ii', $id, $me['id']);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    if ($deleted) {
        foreach ($photos as $photo) {
            $fullPath = __DIR__ . '/' . $photo['file_path'];
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    if ($deleted && $cancelRecipients) {
        $cancelResult = notify_event_cancelled($cancelEvent, $cancelRecipients);
        if ($cancelResult['sent'] > 0) {
            redirect('index.php?view=myEvents&msg=event_deleted_notified');
        }
        if ($cancelResult['failed'] > 0) {
            redirect('index.php?view=myEvents&msg=event_deleted&error=mail_failed_cancel');
        }
    }

    redirect('index.php?view=myEvents&msg=' . ($deleted ? 'event_deleted' : 'delete_failed'));
}


// -------- Adviser: manage student/officer roles --------
// The database keeps the legacy internal value 'admin' for compatibility,
// but the UI and documentation identify this role as Adviser. Only an
// Adviser can promote/demote student accounts. Adviser accounts themselves
// cannot be changed through this action.
if ($action === 'change_user_role') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $newRole = $_POST['new_role'] ?? '';

    if ($targetId <= 0 || !in_array($newRole, ['student', 'officer'], true)) {
        redirect('index.php?view=manageStudents&error=invalid_role_change');
    }

    // Never allow an Adviser account to be modified by this student/officer role tool.
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || $target['role'] === 'admin' || $targetId === (int) $me['id']) {
        redirect('index.php?view=manageStudents&error=role_change_denied');
    }

    // A new officer must be given a position (Governor, Secretary, and so on).
    // Returning an officer to Student clears the position.
    $position = null;
    if ($newRole === 'officer') {
        $position = resolve_officer_position($_POST['position'] ?? '', $_POST['position_custom'] ?? '');
        if ($position === null) {
            redirect('index.php?view=manageStudents&error=position_required');
        }
    }

    $stmt = $conn->prepare("UPDATE users SET role = ?, position_title = ? WHERE id = ? AND role IN ('student','officer')");
    $stmt->bind_param('ssi', $newRole, $position, $targetId);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    redirect('index.php?view=manageStudents&msg=' . ($changed ? 'role_updated' : 'role_not_changed'));
}

// -------- Adviser: clear old activity log lines --------
if ($action === 'purge_activity_log') {
    require_role('admin');
    ensure_activity_log_schema($conn);
    $cut = date('Y-m-d H:i:s', strtotime('-90 days'));
    $stmt = $conn->prepare("DELETE FROM login_logs WHERE created_at < ?");
    $stmt->bind_param('s', $cut);
    $stmt->execute();
    $removed = $stmt->affected_rows;
    $stmt->close();
    error_log('SBO Nexus: Adviser ' . (int) $me['id'] . ' cleared ' . (int) $removed . ' activity log lines older than 90 days');
    redirect('index.php?view=activityLog&msg=log_cleared');
}

// -------- Adviser: reset a student's or officer's forgotten password --------
// Creates a temporary password that is shown once to the Adviser. The student
// must choose a new password the next time they sign in. Adviser accounts
// cannot be reset here.
if ($action === 'reset_user_password') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);

    $stmt = $conn->prepare("SELECT id, full_name, email, role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || !in_array($target['role'], ['student', 'officer'], true)) {
        redirect('index.php?view=manageStudents&error=reset_invalid');
    }

    // Easy to read out loud: no 0/O, 1/l/I look-alikes.
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $temp = '';
    for ($i = 0; $i < 10; $i++) {
        $temp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    $hash = password_hash($temp, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ? AND role IN ('student','officer')");
    $stmt->bind_param('si', $hash, $targetId);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$changed) {
        redirect('index.php?view=manageStudents&error=reset_invalid');
    }

    error_log('SBO Nexus: Adviser ' . (int) $me['id'] . ' reset the password of user ' . $targetId);
    log_activity($conn, $targetId, $target['email'], 'password_reset', '', 'By ' . $me['full_name']);
    $_SESSION['temp_password_notice'] = [
        'name' => $target['full_name'],
        'email' => $target['email'],
        'password' => $temp,
    ];
    redirect('index.php?view=manageStudents&msg=password_reset');
}

// -------- Adviser: change the position of an existing officer --------
if ($action === 'set_officer_position') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $position = resolve_officer_position($_POST['position'] ?? '', $_POST['position_custom'] ?? '');
    if ($targetId <= 0 || $position === null) {
        redirect('index.php?view=manageStudents&error=position_required');
    }

    $stmt = $conn->prepare("UPDATE users SET position_title = ? WHERE id = ? AND role = 'officer'");
    $stmt->bind_param('si', $position, $targetId);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    redirect('index.php?view=manageStudents&msg=' . ($changed ? 'position_updated' : 'role_not_changed'));
}

// -------- Student: turn event emails on or off --------
if ($action === 'update_email_preference' && $role === 'student') {
    ensure_email_notification_schema($conn);
    $enabled = isset($_POST['email_notifications']) && $_POST['email_notifications'] === '1' ? 1 : 0;
    $stmt = $conn->prepare("UPDATE users SET email_notifications = ? WHERE id = ?");
    $stmt->bind_param('ii', $enabled, $me['id']);
    $stmt->execute();
    $stmt->close();
    redirect('index.php?view=privacySecurity&msg=email_pref_saved');
}

// -------- Adviser: grant, transfer, or remove Adviser access --------
// The database value 'admin' is the Adviser role. These actions let the
// current Adviser hand the system over without touching the database.
// Rules enforced here:
//   * only an Adviser can run them
//   * the target must be an existing student or officer with an NBSC email
//   * at least one Adviser must always remain
//   * transferring away your own access needs your current password
if ($action === 'grant_adviser') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);

    $stmt = $conn->prepare("SELECT id, role, email FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || !in_array($target['role'], ['student', 'officer'], true) || !is_nbsc_email($target['email'])) {
        redirect('index.php?view=manageStudents&error=adviser_invalid');
    }

    $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND role IN ('student','officer')");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($changed) {
        error_log('SBO Nexus: Adviser access granted to user ' . $targetId . ' by user ' . (int) $me['id']);
    }
    redirect('index.php?view=manageStudents&' . ($changed ? 'msg=adviser_granted' : 'error=adviser_invalid'));
}

if ($action === 'transfer_adviser') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);
    $password = (string) ($_POST['current_password'] ?? '');
    $myId = (int) $me['id'];

    if ($targetId <= 0 || $targetId === $myId) {
        redirect('index.php?view=manageStudents&error=adviser_invalid');
    }

    // Confirm the current Adviser's password before giving up access.
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
    $stmt->bind_param('i', $myId);
    $stmt->execute();
    $mine = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$mine || $password === '' || !password_verify($password, $mine['password_hash'])) {
        redirect('index.php?view=manageStudents&error=adviser_wrong_password');
    }

    $stmt = $conn->prepare("SELECT id, role, email FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$target || !in_array($target['role'], ['student', 'officer'], true) || !is_nbsc_email($target['email'])) {
        redirect('index.php?view=manageStudents&error=adviser_invalid');
    }

    // Promote the new Adviser and demote the current one in a single step,
    // so the system is never left without an Adviser.
    $ok = false;
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ? AND role IN ('student','officer')");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $promoted = $stmt->affected_rows === 1;
        $stmt->close();

        $stmt = $conn->prepare("UPDATE users SET role = 'student' WHERE id = ? AND role = 'admin'");
        $stmt->bind_param('i', $myId);
        $stmt->execute();
        $demoted = $stmt->affected_rows === 1;
        $stmt->close();

        $adminCount = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];

        if ($promoted && $demoted && $adminCount >= 1) {
            $conn->commit();
            $ok = true;
        } else {
            $conn->rollback();
        }
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('SBO Nexus Adviser transfer failed: ' . $e->getMessage());
    }

    if (!$ok) {
        redirect('index.php?view=manageStudents&error=adviser_invalid');
    }

    error_log('SBO Nexus: Adviser access transferred from user ' . $myId . ' to user ' . $targetId);
    // The session still says admin, so update it. This account is now a student.
    refresh_session_field('role', 'student');
    redirect('index.php?msg=adviser_transferred');
}

if ($action === 'remove_adviser') {
    require_role('admin');
    $targetId = (int) ($_POST['user_id'] ?? 0);

    if ($targetId <= 0 || $targetId === (int) $me['id']) {
        redirect('index.php?view=manageStudents&error=adviser_invalid');
    }

    $adminCount = (int) $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")->fetch_assoc()['c'];
    if ($adminCount < 2) {
        redirect('index.php?view=manageStudents&error=adviser_last_admin');
    }

    $stmt = $conn->prepare("UPDATE users SET role = 'student' WHERE id = ? AND role = 'admin'");
    $stmt->bind_param('i', $targetId);
    $stmt->execute();
    $changed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($changed) {
        error_log('SBO Nexus: Adviser access removed from user ' . $targetId . ' by user ' . (int) $me['id']);
    }
    redirect('index.php?view=manageStudents&' . ($changed ? 'msg=adviser_removed' : 'error=adviser_invalid'));
}

// -------- Admin: approve or reject --------
if ($action === 'decide_event' && $role === 'admin') {
    $id = (int) ($_POST['event_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    if (in_array($decision, ['approved', 'rejected'], true)) {
        $stmt = $conn->prepare("UPDATE events SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $decision, $id);
        $stmt->execute();
        $stmt->close();

        // When an event is approved it becomes visible to students, so email them.
        if ($decision === 'approved' && mail_is_configured()) {
            $approvedEvent = get_event($conn, $id);
            if ($approvedEvent && $approvedEvent['status'] === 'approved') {
                $mailResult = notify_students_of_event($conn, $approvedEvent);
                if ($mailResult['sent'] > 0) {
                    redirect('index.php?view=queue&msg=decided_notified');
                }
                if ($mailResult['failed'] > 0) {
                    redirect('index.php?view=queue&msg=decided&error=mail_failed');
                }
            }
        }
    }
    redirect('index.php?view=queue&msg=decided');
}

// -------- Student: register for an event --------
if ($action === 'register' && $role === 'student') {
    $event_id = (int) ($_POST['event_id'] ?? 0);

    // Check the student's existing registration FIRST. This prevents a full
    // event from incorrectly blocking a student who is already registered,
    // and also makes re-registration after cancellation behave correctly.
    $dup = $conn->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ? LIMIT 1");
    $dup->bind_param('ii', $event_id, $me['id']);
    $dup->execute();
    $alreadyRegistered = $dup->get_result()->fetch_assoc();
    $dup->close();

    if ($alreadyRegistered) {
        redirect('index.php?view=browse&error=duplicate');
    }

    // Events have unlimited registration capacity. We only verify that the
    // event exists, is approved, and that this student is not already registered.
    $conn->begin_transaction();
    try {
        $eventStmt = $conn->prepare("SELECT status FROM events WHERE id = ? FOR UPDATE");
        $eventStmt->bind_param('i', $event_id);
        $eventStmt->execute();
        $event = $eventStmt->get_result()->fetch_assoc();
        $eventStmt->close();

        if (!$event || $event['status'] !== 'approved') {
            $conn->rollback();
            redirect('index.php?view=browse&error=not_open');
        }

        $stmt = $conn->prepare("INSERT INTO registrations (event_id, user_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $event_id, $me['id']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        // A unique-key collision means this account already registered.
        if ($e instanceof mysqli_sql_exception && (int)$e->getCode() === 1062) {
            redirect('index.php?view=browse&error=duplicate');
        }
        redirect('index.php?view=browse&error=not_open');
    }

    redirect('index.php?view=myRegs&msg=registered');
}

// -------- Student: self check-in for attendance (geofenced) --------
if ($action === 'check_in_attendance' && $role === 'student') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';

    $regStmt = $conn->prepare("SELECT id, attended FROM registrations WHERE event_id = ? AND user_id = ? LIMIT 1");
    $regStmt->bind_param('ii', $event_id, $me['id']);
    $regStmt->execute();
    $reg = $regStmt->get_result()->fetch_assoc();
    $regStmt->close();

    if (!$reg) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=checkin_not_registered');
    }
    if ((int)$reg['attended'] === 1) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&msg=already_checked_in');
    }

    $event = get_event($conn, $event_id);
    if (!$event || $event['status'] !== 'approved') {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=not_open');
    }
    if ($event['latitude'] === null || $event['longitude'] === null) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=no_geofence');
    }
    $checkinState = attendance_checkin_state($event);
    if (!$checkinState['open']) {
        $stateError = ['paused' => 'checkin_paused', 'closed' => 'checkin_closed', 'ended' => 'checkin_closed'][$checkinState['reason']] ?? 'not_ongoing';
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=' . $stateError);
    }
    if ($lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=no_location');
    }

    $lat = (float) $lat;
    $lng = (float) $lng;
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=no_location');
    }

    // The phone must report how accurate its GPS fix is. A missing or very
    // poor value is refused, because it cannot prove the student is on site.
    $accRaw = $_POST['acc'] ?? '';
    if ($accRaw === '' || !is_numeric($accRaw) || (float) $accRaw < 0 || (float) $accRaw > ATTENDANCE_MAX_ACCURACY_METERS) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=low_accuracy');
    }
    $accuracy = (int) round((float) $accRaw);

    $distance = haversine_distance_meters($lat, $lng, (float)$event['latitude'], (float)$event['longitude']);

    if ($distance > event_geofence_radius($event)) {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=too_far');
    }

    $distanceM = (int) round($distance);
    $stmt = $conn->prepare("UPDATE registrations SET attended = 1, checkin_method = 'geofence', checked_in_at = NOW(), checkin_lat = ?, checkin_lng = ?, checkin_distance_m = ?, checkin_accuracy_m = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ddiiii', $lat, $lng, $distanceM, $accuracy, $reg['id'], $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=eventDetail&event_id=' . $event_id . '&msg=checked_in');
}

// -------- Officer: start, pause, or stop attendance check-in --------
// The officer who owns the event controls when students can check in.
//   start: opens check-in now, even before the scheduled time
//   pause: blocks check-in temporarily
//   stop:  closes attendance for the event (it can be started again)
if ($action === 'set_checkin_status' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $map = ['start' => 'open', 'pause' => 'paused', 'stop' => 'closed'];
    $choice = $_POST['status'] ?? '';
    if (!isset($map[$choice])) {
        redirect('index.php?view=attendance&event_id=' . $event_id);
    }

    $stmt = $conn->prepare("SELECT id, status, latitude, longitude FROM events WHERE id = ? AND created_by = ?");
    $stmt->bind_param('ii', $event_id, $me['id']);
    $stmt->execute();
    $ownEvent = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ownEvent) {
        redirect('index.php?view=myEvents');
    }
    if ($choice === 'start' && $ownEvent['status'] !== 'approved') {
        redirect('index.php?view=attendance&event_id=' . $event_id . '&error=not_open');
    }
    if ($choice === 'start' && ($ownEvent['latitude'] === null || $ownEvent['longitude'] === null)) {
        redirect('index.php?view=attendance&event_id=' . $event_id . '&error=no_geofence');
    }

    $newStatus = $map[$choice];
    $stmt = $conn->prepare("UPDATE events SET attendance_status = ? WHERE id = ? AND created_by = ?");
    $stmt->bind_param('sii', $newStatus, $event_id, $me['id']);
    $stmt->execute();
    $stmt->close();

    error_log('SBO Nexus attendance: officer ' . (int) $me['id'] . ' set event ' . $event_id . ' to ' . $newStatus);
    redirect('index.php?view=attendance&event_id=' . $event_id . '&msg=attendance_' . $newStatus);
}

// -------- Officer: mark a student present, or remove a check-in --------
// The officer is the final authority on attendance. This covers students
// whose GPS failed and lets the officer remove a check-in that looks fake.
if ($action === 'set_attendance' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $reg_id = (int) ($_POST['registration_id'] ?? 0);
    $mark = ($_POST['mark'] ?? '') === '1';

    $stmt = $conn->prepare("SELECT r.id FROM registrations r JOIN events e ON e.id = r.event_id WHERE r.id = ? AND r.event_id = ? AND e.created_by = ?");
    $stmt->bind_param('iii', $reg_id, $event_id, $me['id']);
    $stmt->execute();
    $owned = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$owned) {
        redirect('index.php?view=myEvents');
    }

    if ($mark) {
        $stmt = $conn->prepare("UPDATE registrations SET attended = 1, checkin_method = 'manual', checked_in_at = NOW(), checkin_lat = NULL, checkin_lng = NULL, checkin_distance_m = NULL, checkin_accuracy_m = NULL WHERE id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE registrations SET attended = 0, checkin_method = NULL, checked_in_at = NULL, checkin_lat = NULL, checkin_lng = NULL, checkin_distance_m = NULL, checkin_accuracy_m = NULL WHERE id = ?");
    }
    $stmt->bind_param('i', $reg_id);
    $stmt->execute();
    $stmt->close();

    error_log('SBO Nexus attendance: officer ' . (int) $me['id'] . ($mark ? ' marked' : ' removed') . ' registration ' . $reg_id . ' for event ' . $event_id);
    redirect('index.php?view=attendance&event_id=' . $event_id . '&msg=' . ($mark ? 'attendance_marked' : 'attendance_removed'));
}

// -------- Student: cancel their own registration --------
if ($action === 'cancel_registration' && $role === 'student') {
    $reg_id = (int) ($_POST['registration_id'] ?? 0);

    // Delete the actual registration row. Because the slot count is calculated
    // directly from this table, a successful cancellation immediately releases
    // the slot for the same student (or another student) to use again.
    $stmt = $conn->prepare("DELETE FROM registrations WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $reg_id, $me['id']);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted > 0) {
        redirect('index.php?view=myRegs&msg=cancelled');
    }

    redirect('index.php?view=myRegs&error=not_registered');
}

// -------- Any logged-in role: post feedback (and optional rating) on an event --------
if ($action === 'post_feedback') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int) $_POST['rating'] : null;
    if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

    if ($message === '') {
        redirect('index.php?view=eventDetail&event_id=' . $event_id . '&error=empty_feedback');
    }
    $message = function_exists('mb_substr') ? mb_substr($message, 0, 500) : substr($message, 0, 500);

    $event = get_event($conn, $event_id);
    if (!$event) redirect('index.php');

    $stmt = $conn->prepare("INSERT INTO event_feedback (event_id, user_id, message, rating) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iisi', $event_id, $me['id'], $message, $rating);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=eventDetail&event_id=' . $event_id . '&msg=feedback_posted');
}

// -------- Author: delete their own feedback --------
if ($action === 'delete_feedback') {
    $fb_id = (int) ($_POST['feedback_id'] ?? 0);
    $event_id = (int) ($_POST['event_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM event_feedback WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $fb_id, $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=eventDetail&event_id=' . $event_id . '&msg=feedback_deleted');
}

// -------- Officer: upload one or multiple photos to their own event --------
if ($action === 'upload_event_photo' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $event = get_event($conn, $event_id);

    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    ensure_event_management_schema($conn);
    $uploaded = 0;
    if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
        $count = count($_FILES['photos']['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $_FILES['photo'] = [
                'name' => $_FILES['photos']['name'][$i],
                'type' => $_FILES['photos']['type'][$i],
                'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                'error' => $_FILES['photos']['error'][$i],
                'size' => $_FILES['photos']['size'][$i]
            ];
            $path = save_uploaded_image('photo', __DIR__ . '/uploads/events', 'uploads/events');
            if ($path === null) continue;
            $isCover = 0;
            $stmt = $conn->prepare("INSERT INTO event_photos (event_id, file_path, is_cover, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isii', $event_id, $path, $isCover, $me['id']);
            $stmt->execute();
            $stmt->close();
            $uploaded++;
        }
        unset($_FILES['photo']);
    } elseif (isset($_FILES['photo'])) {
        $path = save_uploaded_image('photo', __DIR__ . '/uploads/events', 'uploads/events');
        if ($path !== null) {
            $isCover = 0;
            $stmt = $conn->prepare("INSERT INTO event_photos (event_id, file_path, is_cover, uploaded_by) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isii', $event_id, $path, $isCover, $me['id']);
            $stmt->execute();
            $stmt->close();
            $uploaded = 1;
        }
    }

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=' . ($uploaded > 0 ? 'photo_uploaded' : 'bad_photo'));
}

// -------- Officer: upload/change the dedicated event profile/cover photo --------
if ($action === 'upload_event_cover' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $event = get_event($conn, $event_id);

    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    ensure_event_management_schema($conn);
    $path = save_uploaded_image('cover_photo', __DIR__ . '/uploads/events', 'uploads/events');

    if ($path === null) {
        redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=bad_photo');
    }

    // Remove the previous cover flag and make this newly uploaded image the cover.
    $stmt = $conn->prepare("UPDATE event_photos SET is_cover = 0 WHERE event_id = ?");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO event_photos (event_id, file_path, is_cover, uploaded_by) VALUES (?, ?, 1, ?)");
    $stmt->bind_param('isi', $event_id, $path, $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=photo_cover_updated');
}

// -------- Officer: delete a photo from their own event --------
if ($action === 'delete_event_photo' && $role === 'officer') {
    $photo_id = (int) ($_POST['photo_id'] ?? 0);
    $event_id = (int) ($_POST['event_id'] ?? 0);

    $stmt = $conn->prepare("SELECT p.file_path FROM event_photos p JOIN events e ON e.id = p.event_id WHERE p.id = ? AND e.created_by = ?");
    $stmt->bind_param('ii', $photo_id, $me['id']);
    $stmt->execute();
    $photo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($photo) {
        $wasCoverStmt = $conn->prepare("SELECT is_cover FROM event_photos WHERE id = ?");
        $wasCoverStmt->bind_param('i', $photo_id);
        $wasCoverStmt->execute();
        $wasCover = (int)$wasCoverStmt->get_result()->fetch_assoc()['is_cover'] === 1;
        $wasCoverStmt->close();

        $stmt = $conn->prepare("DELETE FROM event_photos WHERE id = ?");
        $stmt->bind_param('i', $photo_id);
        $stmt->execute();
        $stmt->close();
        $fullPath = __DIR__ . '/' . $photo['file_path'];
        if (is_file($fullPath)) @unlink($fullPath);

        // The cover is managed separately; deleting it leaves the event without a cover until a new one is uploaded.
    }

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=photo_deleted');
}

// -------- Officer: save the accomplishment report narrative for their own event --------
if ($action === 'save_accomplishment_report' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $narrative = trim($_POST['narrative'] ?? '');
    $recommendations = trim($_POST['recommendations'] ?? '');

    $event = get_event($conn, $event_id);
    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    $stmt = $conn->prepare("INSERT INTO event_reports (event_id, narrative, recommendations, updated_by, updated_at)
                             VALUES (?, ?, ?, ?, NOW())
                             ON DUPLICATE KEY UPDATE narrative = VALUES(narrative), recommendations = VALUES(recommendations), updated_by = VALUES(updated_by), updated_at = NOW()");
    $stmt->bind_param('issi', $event_id, $narrative, $recommendations, $me['id']);
    $stmt->execute();
    $stmt->close();

    redirect('accomplishment_report.php?event_id=' . $event_id . '&msg=report_saved');
}

// -------- Officer: save the editable accomplishment report document --------
if ($action === 'save_report_document' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $template = (string) ($_POST['template'] ?? 'standard');
    if (!array_key_exists($template, report_templates())) {
        $template = 'standard';
    }
    $back = 'accomplishment_report.php' . ($event_id > 0 ? '?event_id=' . $event_id . '&' : '?');

    if ($event_id > 0) {
        $ownEvent = get_event($conn, $event_id);
        if (!$ownEvent || (int) $ownEvent['created_by'] !== (int) $me['id']) {
            redirect('index.php?view=myEvents');
        }
    }

    $raw = (string) ($_POST['content'] ?? '');
    if (strlen($raw) > 900000) {
        redirect($back . 'error=report_too_large');
    }

    // Only simple formatting is kept. Scripts and outside content are removed.
    $clean = sanitize_report_html($raw);
    if (strlen($clean) > 600000) {
        redirect($back . 'error=report_too_large');
    }

    $saved = save_report_document($conn, (int) $me['id'], $event_id > 0 ? $event_id : null, $template, $clean);
    redirect($back . ($saved ? 'msg=report_saved' : 'error=report_save_failed'));
}

// -------- Officer: add a committee member to their own event --------
if ($action === 'add_event_organizer' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $user_id = (int) ($_POST['user_id'] ?? 0);
    $committee = trim($_POST['committee'] ?? '');
    if ($committee !== '' && mb_strlen($committee) > 100) {
        $committee = mb_substr($committee, 0, 100);
    }
    $committee = $committee !== '' ? $committee : null;
    $event = get_event($conn, $event_id);
    if (!$event || (int)$event['created_by'] !== (int)$me['id'] || $user_id <= 0 || $user_id === (int)$me['id']) {
        redirect('index.php?view=editEvent&event_id=' . $event_id);
    }

    $ustmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role IN ('officer','admin') LIMIT 1");
    $ustmt->bind_param('i', $user_id);
    $ustmt->execute();
    $candidate = $ustmt->get_result()->fetch_assoc();
    $ustmt->close();
    if (!$candidate) redirect('index.php?view=editEvent&event_id=' . $event_id . '&error=invalid_organizer');

    $stmt = $conn->prepare("INSERT INTO event_organizers (event_id, user_id, added_by, committee) VALUES (?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE committee = VALUES(committee)");
    $stmt->bind_param('iiis', $event_id, $user_id, $me['id'], $committee);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=organizer_added');
}

// -------- Officer: remove an organizer from their own event --------
if ($action === 'remove_event_organizer' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $organizer_id = (int) ($_POST['organizer_id'] ?? 0);
    $event = get_event($conn, $event_id);
    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    $stmt = $conn->prepare("DELETE FROM event_organizers WHERE id = ? AND event_id = ?");
    $stmt->bind_param('ii', $organizer_id, $event_id);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=organizer_removed');
}

// -------- Officer: choose which uploaded photo is the event profile/cover photo --------
if ($action === 'set_event_cover' && $role === 'officer') {
    $event_id = (int) ($_POST['event_id'] ?? 0);
    $photo_id = (int) ($_POST['photo_id'] ?? 0);
    $event = get_event($conn, $event_id);
    if (!$event || (int)$event['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }

    $stmt = $conn->prepare("UPDATE event_photos SET is_cover = 0 WHERE event_id = ?");
    $stmt->bind_param('i', $event_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE event_photos SET is_cover = 1 WHERE id = ? AND event_id = ?");
    $stmt->bind_param('ii', $photo_id, $event_id);
    $stmt->execute();
    $stmt->close();

    redirect('index.php?view=editEvent&event_id=' . $event_id . '&msg=photo_cover_updated');
}

// ===== Polls & Surveys module =====

// -------- Officer/Admin: create a poll --------
if ($action === 'create_poll' && $role === 'officer') {
    $question = trim($_POST['question'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rawOptions = $_POST['options'] ?? []; $options = []; foreach ($rawOptions as $i=>$opt) { $opt=trim((string)$opt); if ($opt!=='') $options[]=['text'=>$opt,'index'=>(int)$i]; }

    if ($question === '' || count($options) < 2) {
        redirect('index.php?view=polls&error=missing_fields');
    }
    $options = array_slice($options, 0, 6);

    $stmt = $conn->prepare("INSERT INTO polls (question, description, status, created_by) VALUES (?, ?, 'open', ?)");
    $stmt->bind_param('ssi', $question, $description, $me['id']);
    $stmt->execute();
    $pollId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text, image_path) VALUES (?, ?, ?)");
    foreach ($options as $opt) {
        $imagePath = save_uploaded_image('option_images', __DIR__ . '/uploads/polls', 'uploads/polls', $opt['index']);
        $stmt->bind_param('iss', $pollId, $opt['text'], $imagePath);
        $stmt->execute();
    }
    $stmt->close();

    redirect('index.php?view=polls&msg=poll_created');
}

// -------- Officer: edit their own poll --------
if ($action === 'update_poll' && $role === 'officer') {
    $poll_id = (int) ($_POST['poll_id'] ?? 0);
    $poll = get_poll($conn, $poll_id);
    if (!$poll || (int)$poll['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=polls&error=poll_not_allowed');
    }

    $question = trim($_POST['question'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rawOptions = $_POST['options'] ?? []; $options = []; foreach ($rawOptions as $i=>$opt) { $opt=trim((string)$opt); if ($opt!=='') $options[]=['text'=>$opt,'index'=>(int)$i]; }
    $options = array_slice($options, 0, 6);

    $totalVotes = total_poll_votes($conn, $poll_id);
    if ($question === '' || ($totalVotes === 0 && count($options) < 2)) {
        redirect('index.php?view=pollDetail&id=' . $poll_id . '&error=missing_fields');
    }

    if ($totalVotes > 0) {
        // Once voting has started, preserve the choices so historical votes remain meaningful.
        $stmt = $conn->prepare("UPDATE polls SET question = ?, description = ? WHERE id = ? AND created_by = ?");
        $stmt->bind_param('ssii', $question, $description, $poll_id, $me['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE polls SET question = ?, description = ? WHERE id = ? AND created_by = ?");
            $stmt->bind_param('ssii', $question, $description, $poll_id, $me['id']);
            $stmt->execute();
            $stmt->close();

            // With no votes yet, safely replace the answer choices.
            $stmt = $conn->prepare("DELETE FROM poll_options WHERE poll_id = ?");
            $stmt->bind_param('i', $poll_id);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text, image_path) VALUES (?, ?, ?)");
            foreach ($options as $opt) {
                $imagePath = save_uploaded_image('option_images', __DIR__ . '/uploads/polls', 'uploads/polls', $opt['index']);
                $stmt->bind_param('iss', $poll_id, $opt['text'], $imagePath);
                $stmt->execute();
            }
            $stmt->close();
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
            redirect('index.php?view=pollDetail&id=' . $poll_id . '&error=update_failed');
        }
    }

    redirect('index.php?view=pollDetail&id=' . $poll_id . '&msg=poll_updated');
}

// -------- Officer: delete their own poll --------
if ($action === 'delete_poll' && $role === 'officer') {
    $poll_id = (int) ($_POST['poll_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM polls WHERE id = ? AND created_by = ?");
    $stmt->bind_param('ii', $poll_id, $me['id']);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    redirect('index.php?view=polls&msg=' . ($deleted ? 'poll_deleted' : 'poll_not_deleted'));
}

// -------- Officer/Admin: close their own poll --------
if ($action === 'close_poll' && $role === 'officer') {
    $poll_id = (int) ($_POST['poll_id'] ?? 0);
    $poll = get_poll($conn, $poll_id);

    if ($poll && (int)$poll['created_by'] === (int)$me['id']) {
        $stmt = $conn->prepare("UPDATE polls SET status = 'closed' WHERE id = ?");
        $stmt->bind_param('i', $poll_id);
        $stmt->execute();
        $stmt->close();
    }

    redirect('index.php?view=pollDetail&id=' . $poll_id . '&msg=poll_closed');
}

// -------- Students only: vote on an open poll (once) --------
if ($action === 'vote_poll' && $role !== 'student') {
    $poll_id = (int) ($_POST['poll_id'] ?? 0);
    redirect('index.php?view=pollDetail&id=' . $poll_id . '&error=student_vote_only');
}

if ($action === 'vote_poll' && $role === 'student') {
    $poll_id = (int) ($_POST['poll_id'] ?? 0);
    $option_id = (int) ($_POST['option_id'] ?? 0);

    $poll = get_poll($conn, $poll_id);
    if (!$poll || $poll['status'] !== 'open') {
        redirect('index.php?view=pollDetail&id=' . $poll_id . '&error=poll_closed');
    }

    // Confirm the option actually belongs to this poll before recording the vote.
    $stmt = $conn->prepare("SELECT id FROM poll_options WHERE id = ? AND poll_id = ?");
    $stmt->bind_param('ii', $option_id, $poll_id);
    $stmt->execute();
    $validOption = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$validOption) redirect('index.php?view=pollDetail&id=' . $poll_id);

    $stmt = $conn->prepare("INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?, ?, ?)");
    $stmt->bind_param('iii', $poll_id, $option_id, $me['id']);

    try {
        $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        // UNIQUE(poll_id, user_id) already blocks a second vote at the DB level.
        redirect('index.php?view=pollDetail&id=' . $poll_id . '&error=already_voted');
    }
    $stmt->close();

    redirect('index.php?view=pollDetail&id=' . $poll_id . '&msg=vote_cast');
}

// -------- Any logged-in role: change their own password --------
if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->bind_param('i', $me['id']);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password_hash'])) {
        redirect('index.php?view=changePassword&error=wrong_password');
    }
    if (strlen($new) < 8) {
        redirect('index.php?view=changePassword&error=password_too_short');
    }
    if ($new !== $confirm) {
        redirect('index.php?view=changePassword&error=password_mismatch');
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->bind_param('si', $newHash, $me['id']);
    $stmt->execute();
    $stmt->close();

    log_activity($conn, $me['id'], $me['email'] ?? '', 'password_changed');
    redirect('index.php?view=profile&msg=password_changed');
}

redirect('index.php');