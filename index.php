<?php
// SBO Nexus: Event Management module
require 'config.php';
require 'includes/functions.php';
require 'includes/auth.php';
require_once 'includes/mailer.php';

ob_start();

require_login();
$me = current_user();
$role = $me['role'];

// The signed-in officer's position (Governor, Secretary, and so on), shown beside their name.
$myPositionTitle = '';
if ($role === 'officer') {
    $posStmt = $conn->prepare("SELECT position_title FROM users WHERE id = ?");
    $posStmt->bind_param('i', $me['id']);
    $posStmt->execute();
    $posRow = $posStmt->get_result()->fetch_assoc();
    $posStmt->close();
    $myPositionTitle = trim((string) ($posRow['position_title'] ?? ''));
}
ensure_event_management_schema($conn);

// ---- Profile photo upload (standard POST/redirect; no AJAX/JSON dependency) ----
// Keeping this upload inside index.php avoids localhost servers returning an
// HTML error/redirect to an AJAX request that expects JSON.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_profile_photo') {
    $profileId = (int)$me['id'];
    $photoPath = save_uploaded_image('photo', __DIR__ . '/uploads/profiles', 'uploads/profiles');

    if ($photoPath === null) {
        redirect('index.php?view=profile&error=bad_photo');
    }

    $oldPhoto = '';
    $stmt = $conn->prepare("SELECT photo FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $profileId);
        $stmt->execute();
        $stmt->bind_result($oldPhotoValue);
        if ($stmt->fetch() && $oldPhotoValue) $oldPhoto = (string)$oldPhotoValue;
        $stmt->close();
    }

    $stmt = $conn->prepare("UPDATE users SET photo = ? WHERE id = ?");
    if (!$stmt) {
        $newFile = __DIR__ . '/' . $photoPath;
        if (is_file($newFile)) @unlink($newFile);
        redirect('index.php?view=profile&error=bad_photo');
    }
    $stmt->bind_param('si', $photoPath, $profileId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        $newFile = __DIR__ . '/' . $photoPath;
        if (is_file($newFile)) @unlink($newFile);
        redirect('index.php?view=profile&error=bad_photo');
    }

    $_SESSION['photo'] = $photoPath;

    if ($oldPhoto !== '' && strpos($oldPhoto, 'uploads/profiles/') === 0) {
        $oldFile = __DIR__ . '/' . str_replace('/', DIRECTORY_SEPARATOR, $oldPhoto);
        $profileDir = realpath(__DIR__ . '/uploads/profiles');
        if (is_file($oldFile) && $profileDir && realpath(dirname($oldFile)) === $profileDir) {
            @unlink($oldFile);
        }
    }

    redirect('index.php?view=profile&msg=photo_updated');
}

$defaultView = 'dashboard';
$view = $_GET['view'] ?? $defaultView;

// Guard views that don't belong to this role — typing the URL directly
// shouldn't grant access a nav link wouldn't offer.
$settingsSubViews = ['personalInfo', 'changePassword', 'privacySecurity', 'aboutSystem', 'helpSupport'];
$allowedViews = [
    'student' => array_merge(['dashboard', 'browse', 'myRegs', 'mySchedule', 'eventDetail', 'profile', 'polls', 'pollDetail'], $settingsSubViews),
    'officer' => array_merge(['dashboard', 'myEvents', 'allEvents', 'roster', 'attendance', 'eventDetail', 'profile', 'polls', 'pollDetail', 'editEvent'], $settingsSubViews),
    'admin' => array_merge(['dashboard', 'activityLog', 'queue', 'allEvents', 'roster', 'attendance', 'eventDetail', 'profile', 'polls', 'pollDetail', 'manageStudents'], $settingsSubViews),
];
if (!in_array($view, $allowedViews[$role], true)) {
    http_response_code(403);
    $deniedMessage = 'You do not have permission to access this page. This page is restricted to the Adviser/Admin.';
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Access Denied · SBO Nexus</title>
      <?php include 'includes/style.php'; ?>
    </head>
    <body>
      <div class="auth-wrap">
        <div class="auth-card" style="max-width:520px;text-align:center;">
          <div style="font-size:48px;margin-bottom:12px;">🔒</div>
          <h1 class="auth-title" style="font-size:28px;">Access Denied</h1>
          <p class="sub" style="margin:12px 0 22px;"><?= h($deniedMessage) ?></p>
          <a href="index.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$messages = [
    'submitted' => 'Event submitted for admin approval.',
    'event_updated' => 'Event updated.',
    'withdrawn' => 'Event withdrawn.',
    'event_deleted' => 'Event deleted successfully.',
    'event_deleted_notified' => 'Event deleted. Students who joined were notified by email.',
    'delete_failed' => 'The event could not be deleted.',
    'decided' => 'Decision recorded.',
    'decided_notified' => 'Event approved. Students were notified by email.',
    'email_pref_saved' => 'Your email notification setting was saved.',
    'registered' => 'You are going to this event.',
    'cancelled' => 'Registration cancelled.',
    'feedback_posted' => 'Feedback posted.',
    'feedback_deleted' => 'Feedback removed.',
    'photo_uploaded' => 'Photo added.',
    'photo_deleted' => 'Photo removed.',
    'photo_updated' => 'Profile photo updated.',
    'organizer_added' => 'Committee member added.',
    'organizer_removed' => 'Committee member removed.',
    'photo_cover_updated' => 'Event profile photo updated.',
    'invalid_organizer' => 'That account cannot be added as a committee member.',
    'poll_created' => 'Poll created.',
    'poll_closed' => 'Poll closed.',
    'poll_updated' => 'Poll updated.',
    'poll_deleted' => 'Poll deleted.',
    'vote_cast' => 'Your vote was recorded.',
    'password_changed' => 'Your password has been updated.',
    'role_updated' => 'User role updated successfully.',
    'adviser_granted' => 'Adviser access granted.',
    'position_updated' => 'Officer position updated.',
    'password_reset' => 'Password reset. Give the temporary password below to the student.',
    'log_cleared' => 'Activity log lines older than 90 days were removed.',
    'adviser_transferred' => 'Adviser access was transferred. Your account is now a Student account.',
    'adviser_removed' => 'Adviser access removed.',
    'checked_in' => 'You have been marked present. Thanks for checking in!',
    'already_checked_in' => 'You are already marked present for this event.',
    'attendance_marked' => 'Student marked present.',
    'attendance_removed' => 'Check-in removed. The student is no longer marked present.',
    'attendance_open' => 'Attendance started. Students can check in now.',
    'attendance_paused' => 'Attendance paused. Students cannot check in until you start it again.',
    'attendance_closed' => 'Attendance stopped. Students can no longer check in. You can start it again if needed.',
];
$errors = [
    'missing_fields' => 'Please fill in all required fields.',
    'not_open' => 'Joining is not open for this event.',
    'full' => 'That event is already full.',
    'duplicate' => 'You have already joined this event.',
    'not_registered' => 'Your participation could not be cancelled because it was already removed.',
    'checkin_not_registered' => 'You need to join this event before checking in.',
    'no_geofence' => 'This event has no location pin set, so attendance check-in is unavailable. Please contact the organizer.',
    'not_ongoing' => 'Attendance check-in is only available while the event is happening.',
    'no_location' => 'We could not read your device location. Please allow location access and try again.',
    'too_far' => 'You are too far from the event venue to check in. Please make sure you are at the venue.',
    'checkin_paused' => 'Check-in is paused by the organizers right now. Please ask an officer.',
    'checkin_closed' => 'Attendance check-in is closed for this event. Please ask an officer if you were present.',
    'low_accuracy' => 'Your location is not accurate enough to confirm you are at the venue. Turn on GPS and High accuracy, move near a window or outdoors, wait a few seconds, and try again.',
    'not_allowed' => 'That page is not available for your account.',
    'empty_feedback' => 'Write something before posting.',
    'bad_photo' => 'That file could not be used. Upload a JPG, PNG, WEBP, or GIF under 5MB.',
    'poll_closed' => 'This poll is closed and no longer accepting votes.',
    'wrong_password' => 'Your current password is incorrect.',
    'password_too_short' => 'New password must be at least 8 characters.',
    'password_mismatch' => 'New password and confirmation do not match.',
    'already_voted' => 'You have already voted on this poll.',
    'student_vote_only' => 'Only students can vote on polls.',
    'poll_not_allowed' => 'You can only manage polls you created.',
    'update_failed' => 'The poll could not be updated. Please try again.',
    'poll_not_deleted' => 'The poll could not be deleted.',
    'invalid_role_change' => 'Invalid role change request.',
    'role_change_denied' => 'That account cannot be changed.',
    'role_not_changed' => 'No role change was made.',
    'mail_failed' => 'The event was approved, but the email notice could not be sent. Check the mail settings.',
    'mail_failed_cancel' => 'The event was deleted, but the cancellation email could not be sent. Check the mail settings.',
    'reset_invalid' => 'That password could not be reset. Only student and officer accounts can be reset.',
    'position_required' => 'Choose a position for the officer. If you pick Other, type the position name.',
    'adviser_invalid' => 'That Adviser change could not be made. Choose an existing student or officer account.',
    'adviser_wrong_password' => 'Your password is incorrect. Adviser access was not changed.',
    'adviser_last_admin' => 'At least one Adviser must remain. Grant access to another account first.',
];

// ---- roster sub-page (registrant list / attendance) ----
if ($view === 'roster' || $view === 'attendance') {
    $event_id = (int) ($_GET['event_id'] ?? 0);
    $event = get_event($conn, $event_id);
    if (!$event) redirect('index.php');
    $regs = get_registrations($conn, $event_id);
}

// ---- event detail sub-page (full info + feedback thread) ----
if ($view === 'eventDetail') {
    $event_id = (int) ($_GET['event_id'] ?? 0);
    $event = get_event($conn, $event_id);
    if (!$event) redirect('index.php');
    // Students may only view approved events; officer/admin may view any (including their own pending submissions).
    if ($role === 'student' && $event['status'] !== 'approved') redirect('index.php?view=browse');

    $organizer = get_user($conn, $event['created_by']);
    $eventOrganizers = get_event_organizers($conn, $event_id);
    $feedback = get_feedback($conn, $event_id);
    $eventPhotos = get_event_photos($conn, $event_id);
    $eventHeroPhoto = get_event_hero_photo($conn, $event_id);
    $ratingSummary = get_rating_summary($conn, $event_id);
    $eventRegCount = count_registrations($conn, $event_id);
    // Small avatar stack for students who joined this event.
    $eventJoinedStudents = get_registrant_avatars($conn, $event_id, 5);
    $canManagePhotos = $role === 'officer' && (int)$event['created_by'] === (int)$me['id'];

    $myRating = null;
    $rstmt2 = $conn->prepare("SELECT rating FROM event_feedback WHERE event_id = ? AND user_id = ? AND rating IS NOT NULL ORDER BY created_at DESC LIMIT 1");
    $rstmt2->bind_param('ii', $event_id, $me['id']);
    $rstmt2->execute();
    $myRatingRow = $rstmt2->get_result()->fetch_assoc();
    if ($myRatingRow) $myRating = (int) $myRatingRow['rating'];

    $myEventReg = null;
    if ($role === 'student') {
        $rstmt = $conn->prepare("SELECT id, attended FROM registrations WHERE event_id = ? AND user_id = ?");
        $rstmt->bind_param('ii', $event_id, $me['id']);
        $rstmt->execute();
        $myEventReg = $rstmt->get_result()->fetch_assoc();
    }
}

// ---- profile / personal info sub-pages ----
if (in_array($view, ['profile', 'personalInfo'], true)) {
    $profileUser = get_user($conn, $me['id']);
}

// ---- edit event sub-page (officer only, own events) ----
if ($view === 'editEvent') {
    $edit_event_id = (int) ($_GET['event_id'] ?? 0);
    $editEvent = get_event($conn, $edit_event_id);
    if (!$editEvent || (int)$editEvent['created_by'] !== (int)$me['id']) {
        redirect('index.php?view=myEvents');
    }
    $editEventOrganizers = get_event_organizers($conn, $edit_event_id);
    $organizerCandidates = get_event_organizer_candidates($conn, $edit_event_id);
    $editEventPhotos = get_event_photos($conn, $edit_event_id);
    $editEventHeroPhoto = get_event_hero_photo($conn, $edit_event_id);
}


// ---- poll detail sub-page ----
if ($view === 'pollDetail') {
    $poll_id = (int) ($_GET['id'] ?? 0);
    $poll = get_poll($conn, $poll_id);
    if (!$poll) redirect('index.php?view=polls');
    $pollOptions = get_poll_options($conn, $poll_id);
    $pollTotalVotes = total_poll_votes($conn, $poll_id);
    $myVoteOptionId = get_my_vote($conn, $poll_id, $me['id']);
}

$pendingCount = $conn->query("SELECT COUNT(*) c FROM events WHERE status='pending'")->fetch_assoc()['c'];
$totalEvents = $conn->query("SELECT COUNT(*) c FROM events")->fetch_assoc()['c'];
$upcomingCount = $conn->query("SELECT COUNT(*) c FROM events WHERE status='approved' AND event_date >= CURDATE()")->fetch_assoc()['c'];
$totalRegs = total_registrations($conn);

// ---- student dashboard ----
if ($view === 'dashboard' && $role === 'student') {
    $dashUpcoming = array_filter(get_events($conn, 'approved'), fn($e) => !event_is_past($e));
    usort($dashUpcoming, fn($a, $b) => strcmp($a['event_date'], $b['event_date']));
    $dashUpcoming = array_slice($dashUpcoming, 0, 3);

    $dashPast = array_filter(get_events($conn, 'approved'), fn($e) => event_is_past($e));
    usort($dashPast, fn($a, $b) => strcmp($b['event_date'], $a['event_date']));
    $dashPast = array_slice($dashPast, 0, 2);

    $stmt = $conn->prepare("SELECT COUNT(*) c FROM registrations WHERE user_id = ?");
    $stmt->bind_param('i', $me['id']);
    $stmt->execute();
    $myRegsCount = $stmt->get_result()->fetch_assoc()['c'];
}

// ---- my schedule (student) ----
if ($view === 'mySchedule' && $role === 'student') {
    $schedMonth = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $schedMonth)) $schedMonth = date('Y-m');
    $schedEvents = get_my_schedule_events($conn, $me['id']);
    $schedEventsByDate = [];
    foreach ($schedEvents as $se) { $schedEventsByDate[$se['event_date']][] = $se; }

    // Selected day defaults to today if today falls within the displayed
    // month, otherwise the 1st of that month.
    $todayStr = date('Y-m-d');
    $selectedDay = $_GET['day'] ?? (strpos($todayStr, $schedMonth) === 0 ? $todayStr : $schedMonth . '-01');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDay) || strpos($selectedDay, $schedMonth) !== 0) {
        $selectedDay = $schedMonth . '-01';
    }
    $selectedDayEvents = $schedEventsByDate[$selectedDay] ?? [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SBO Nexus · Event Management</title>
<link rel="icon" type="image/png" href="assets/sbo-logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<?php if (in_array($view, ['eventDetail', 'myEvents', 'allEvents', 'editEvent'], true)): ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<?php endif; ?>
<?php include 'includes/style.php'; ?>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0b6b3a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="icon-192.png">
</head>
<body>
<div class="app">
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-link">
        <img src="assets/sbo-logo.png" alt="SBO logo" class="brand-logo">
        <span class="brand-copy"><strong>SBO NEXUS</strong><small>EVENT MANAGEMENT · ICS</small></span>
      </div>
    </div>

    <div class="panel-user-area">
      <div class="panel-dropdown-wrap">
        <button type="button" class="panel-trigger" id="panelTrigger" aria-expanded="false" aria-controls="panelMenu">
          <span>Panel</span><?= icon('chevron-down', 'panel-chevron') ?>
        </button>
        <div class="panel-menu" id="panelMenu" role="menu" aria-hidden="true">
          <?php if ($role === 'student'): ?>
            <a class="panel-menu-item <?= $view==='dashboard'?'active':'' ?>" href="index.php?view=dashboard" role="menuitem"><?= icon('home') ?><span>Dashboard</span></a>
            <a class="panel-menu-item <?= $view==='profile'?'active':'' ?>" href="index.php?view=profile" role="menuitem"><?= icon('user') ?><span>Profile</span></a>
            <div class="panel-menu-divider"></div>
            <a class="panel-menu-item <?= in_array($view,['browse','eventDetail'])?'active':'' ?>" href="index.php?view=browse" role="menuitem"><?= icon('search') ?><span>Browse Events</span></a>
            <a class="panel-menu-item <?= $view==='mySchedule'?'active':'' ?>" href="index.php?view=mySchedule" role="menuitem"><?= icon('calendar') ?><span>My Schedule</span></a>
            <a class="panel-menu-item <?= $view==='myRegs'?'active':'' ?>" href="index.php?view=myRegs" role="menuitem"><?= icon('clipboard') ?><span>Events</span></a>
            <a class="panel-menu-item <?= in_array($view,['polls','pollDetail'])?'active':'' ?>" href="index.php?view=polls" role="menuitem"><?= icon('clipboard') ?><span>Polls &amp; Surveys</span></a>
          <?php elseif ($role === 'officer'): ?>
            <a class="panel-menu-item <?= $view==='dashboard'?'active':'' ?>" href="index.php?view=dashboard" role="menuitem"><?= icon('home') ?><span>Dashboard</span></a>
            <a class="panel-menu-item <?= $view==='profile'?'active':'' ?>" href="index.php?view=profile" role="menuitem"><?= icon('user') ?><span>Profile</span></a>
            <div class="panel-menu-divider"></div>
            <a class="panel-menu-item <?= $view==='myEvents'?'active':'' ?>" href="index.php?view=myEvents" role="menuitem"><?= icon('calendar') ?><span>My Events</span></a>
            <a class="panel-menu-item <?= $view==='allEvents'?'active':'' ?>" href="index.php?view=allEvents" role="menuitem"><?= icon('search') ?><span>All Events</span></a>
            <a class="panel-menu-item <?= in_array($view,['polls','pollDetail'])?'active':'' ?>" href="index.php?view=polls" role="menuitem"><?= icon('clipboard') ?><span>Polls &amp; Surveys</span></a>
          <?php else: ?>
            <a class="panel-menu-item <?= $view==='dashboard'?'active':'' ?>" href="index.php?view=dashboard" role="menuitem"><?= icon('home') ?><span>Dashboard</span></a>
            <a class="panel-menu-item <?= $view==='profile'?'active':'' ?>" href="index.php?view=profile" role="menuitem"><?= icon('user') ?><span>Profile</span></a>
            <div class="panel-menu-divider"></div>
            <a class="panel-menu-item <?= $view==='queue'?'active':'' ?>" href="index.php?view=queue" role="menuitem"><?= icon('clipboard') ?><span>Approval Queue</span><?php if($pendingCount>0):?><span class="panel-badge"><?= (int)$pendingCount ?></span><?php endif; ?></a>
            <a class="panel-menu-item <?= $view==='allEvents'?'active':'' ?>" href="index.php?view=allEvents" role="menuitem"><?= icon('search') ?><span>All Events</span></a>
            <a class="panel-menu-item <?= in_array($view,['polls','pollDetail'])?'active':'' ?>" href="index.php?view=polls" role="menuitem"><?= icon('clipboard') ?><span>Polls &amp; Surveys</span></a>
            <a class="panel-menu-item <?= $view==='manageStudents'?'active':'' ?>" href="index.php?view=manageStudents" role="menuitem"><?= icon('users') ?><span>Students &amp; Officers</span></a>
            <a class="panel-menu-item <?= $view==='activityLog'?'active':'' ?>" href="index.php?view=activityLog" role="menuitem"><?= icon('clock') ?><span>Activity Log</span></a>
          <?php endif; ?>
        </div>
      </div>
      <div class="user-link">
        <?= avatar_html($me, 38) ?>
        <span class="user-copy"><strong><?= h($me['full_name']) ?></strong><small><?= $role === 'student' ? 'Student' : ($role === 'officer' ? h($myPositionTitle !== '' ? $myPositionTitle : 'SBO Officer') : 'Adviser · Admin') ?></small></span>
      </div>
    </div>

  </aside>

  <main class="main">
<?php
// ---------------- flash messages ----------------
if (isset($_GET['msg']) && isset($messages[$_GET['msg']])) {
    echo '<div class="flash flash-ok">' . h($messages[$_GET['msg']]) . '</div>';
}
if (isset($_GET['error']) && isset($errors[$_GET['error']])) {
    echo '<div class="flash flash-err">' . h($errors[$_GET['error']]) . '</div>';
}

// ---------------- shared ticket card renderer ----------------
function ticket_card($ev, $regCount, $actionsHtml) {
    $badgeClass = ['pending' => 'badge-pending', 'approved' => 'badge-approved', 'rejected' => 'badge-rejected'][$ev['status']];
    $badgeLabel = ['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Not approved'][$ev['status']];
    $capHtml = '<span style="display:flex;align-items:center;">' . icon('users') . '<span class="mono" style="font-size:11.5px;color:var(--slate)">' . $regCount . ' going</span></span>';

    $catSlug = strtolower($ev['category']);
    $countdown = $ev['status'] === 'approved' ? days_until_label($ev['event_date']) : null;

    echo '<div class="ticket">';
    echo '  <div class="ticket-top-row">';
    echo '    <span class="ticket-date-badge">' . h(fmt_event_date_range($ev)) . '</span>';
    echo '    <span class="badge ' . $badgeClass . '">' . $badgeLabel . '</span>';
    echo '  </div>';
    echo '  <p class="ticket-title"><a href="index.php?view=eventDetail&event_id=' . (int)$ev['id'] . '" style="color:inherit;">' . h($ev['title']) . '</a></p>';
    if ($countdown) echo '  <span class="countdown-chip" style="margin-bottom:8px;display:inline-block;">' . h($countdown) . '</span>';
    echo '  <div class="ticket-meta">';
    echo '    <span class="cat-chip ' . $catSlug . '">' . category_icon($ev['category']) . h($ev['category']) . '</span>';
    echo '    <span>' . icon('clock') . h(fmt_event_time_range($ev)) . '</span>';
    echo '    <span class="event-duration-badge">' . event_duration_days($ev) . ' day' . (event_duration_days($ev) === 1 ? '' : 's') . '</span>';
    echo '  </div>';
    echo '  <div class="ticket-meta" style="margin-top:2px;"><span>' . icon('pin') . h($ev['venue']) . '</span></div>';
    if ($ev['description']) echo '  <p class="ticket-desc">' . h($ev['description']) . '</p>';
    echo '  <div class="ticket-actions">' . $capHtml . $actionsHtml . '</div>';
    echo '</div>';
}

// Compact list-row style used on the Dashboard and My Schedule — distinct
// from ticket_card's grid tiles, matching the reference layout's agenda rows.
function agenda_row($conn, $ev, $actionsHtml) {
    $regs = get_registrant_avatars($conn, $ev['id'], 4);
    $totalRegs = count_registrations($conn, $ev['id']);
    $catSlug = strtolower($ev['category']);
    $countdown = days_until_label($ev['event_date']);

    echo '<div class="agenda-row">';
    echo '  <div class="agenda-date"><div class="am">' . fmt_month($ev['event_date']) . '</div><div class="ad">' . fmt_day($ev['event_date']) . '</div><div style="font-size:9.5px;color:#9CA7C4;">' . fmt_year($ev['event_date']) . '</div><div class="event-duration-badge" style="font-size:9px;margin-top:4px;">' . event_duration_days($ev) . 'd</div></div>';
    echo '  <div class="agenda-body">';
    echo '    <div class="agenda-top"><p class="agenda-title"><a href="index.php?view=eventDetail&event_id=' . (int)$ev['id'] . '" style="color:inherit;">' . h($ev['title']) . '</a>' . ($countdown ? ' <span class="countdown-chip">' . h($countdown) . '</span>' : '') . '</p></div>';
    echo '    <div class="agenda-meta">';
    echo '      <span class="cat-chip ' . $catSlug . '">' . category_icon($ev['category']) . h($ev['category']) . '</span>';
    echo '      <span>' . icon('clock') . h(fmt_event_time_range($ev)) . '</span>';
    echo '      <span class="event-duration-badge">' . event_duration_days($ev) . ' day' . (event_duration_days($ev) === 1 ? '' : 's') . '</span>';
    echo '      <span>' . icon('pin') . h($ev['venue']) . '</span>';
    echo '    </div>';
    echo '    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">';
    if (!empty($regs)) {
        echo '      <div class="avatar-stack">';
        foreach ($regs as $r) { echo avatar_html($r, 26); }
        if ($totalRegs > count($regs)) echo '        <span class="more-chip">+' . ($totalRegs - count($regs)) . '</span>';
        echo '      </div>';
        echo '      <span class="mono" style="font-size:11.5px;color:var(--slate)">' . $totalRegs . ($ev['capacity'] ? '/' . (int)$ev['capacity'] : '') . ' going</span>';
    } else {
        echo '      <span class="mono" style="font-size:11.5px;color:var(--slate)">' . $totalRegs . ($ev['capacity'] ? '/' . (int)$ev['capacity'] : '') . ' going</span>';
    }
    if ($actionsHtml) echo $actionsHtml;
    echo '    </div>';
    echo '  </div>';
    echo '</div>';
}

// Photo-topped card style for Browse Events, matching the reference layout:
// hero image (real uploaded photo, or a category-colored placeholder —
// never a fake stock photo), status badge, category, title, meta, rating.
function hero_card($conn, $ev) {
    $catColor = category_color($ev['category']);
    $catSlug = strtolower($ev['category']);
    $isPast = event_is_past($ev);
    $heroPhoto = get_event_hero_photo($conn, $ev['id']);
    $rating = get_rating_summary($conn, $ev['id']);

    echo '<a class="hero-card" href="index.php?view=eventDetail&event_id=' . (int)$ev['id'] . '">';
    echo '  <div class="hero-card-media">';
    if ($heroPhoto) {
        echo '    <img src="' . h($heroPhoto) . '" class="hero-card-img" alt="">';
    } else {
        echo '    <div class="hero-card-placeholder" style="background:' . $catColor . ';">' . category_icon($ev['category']) . '</div>';
    }
    echo '    <span class="hero-status-badge ' . ($isPast ? '' : 'upcoming') . '">' . ($isPast ? 'Past' : 'Upcoming') . '</span>';
    echo '  </div>';
    echo '  <div class="hero-card-body">';
    echo '    <div class="hero-card-cat">' . h($ev['category']) . '</div>';
    echo '    <p class="hero-card-title">' . h($ev['title']) . '</p>';
    if ($ev['description']) echo '    <p class="hero-card-desc">' . h($ev['description']) . '</p>';
    echo '    <div class="hero-card-meta">';
    echo '      <span>' . icon('calendar') . h(fmt_event_date_range($ev)) . ' · ' . h(fmt_event_time_range($ev)) . ' · ' . event_duration_days($ev) . ' day' . (event_duration_days($ev) === 1 ? '' : 's') . '</span>';
    echo '      <span>' . icon('pin') . h($ev['venue']) . '</span>';
    echo '    </div>';
    if ($rating['avg']) {
        echo '    <div class="hero-card-rating">' . star_html($rating['avg'], 13) . '<span>' . $rating['avg'] . ' &middot; ' . $rating['count'] . ' rating' . ($rating['count'] === 1 ? '' : 's') . '</span></div>';
    }
    echo '  </div>';
    echo '</a>';
}

// ================== STUDENT DASHBOARD ==================
if ($view === 'dashboard' && $role === 'student') {
    ?>
    <div class="welcome-banner">
      <div class="dashboard-kicker">ICS · SBO NEXUS</div>
      <h2>Welcome back, <?= h(explode(' ', $me['full_name'])[0]) ?> 👋</h2>
      <p>Stay informed, discover opportunities, and take part in campus activities.</p>
    </div>

    <div class="stats dashboard-stat-cards">
      <a href="index.php?view=browse" class="stat stat-linked">
        <div class="stat-top"><div class="stat-copy"><div class="n"><?= (int)$totalEvents ?></div><div class="l">Total Events</div></div><span class="stat-icon i-green"><?= icon('calendar') ?></span></div>
        <div class="stat-link">View all events &rarr;</div>
      </a>
      <a href="index.php?view=browse" class="stat stat-linked">
        <div class="stat-top"><div class="stat-copy"><div class="n"><?= (int)$upcomingCount ?></div><div class="l">Upcoming</div></div><span class="stat-icon i-blue"><?= icon('calendar') ?></span></div>
        <div class="stat-link">See what's next &rarr;</div>
      </a>
      <a href="index.php?view=mySchedule" class="stat stat-linked">
        <div class="stat-top"><div class="stat-copy"><div class="n"><?= (int)$myRegsCount ?></div><div class="l">My Schedule</div></div><span class="stat-icon i-purple"><?= icon('book') ?></span></div>
        <div class="stat-link">View joined events &rarr;</div>
      </a>
      <a href="index.php?view=polls" class="stat stat-linked">
        <div class="stat-top"><div class="stat-copy"><div class="n"><?= (int)($conn->query("SELECT COUNT(*) FROM polls")->fetch_row()[0] ?? 0) ?></div><div class="l">Total Polls</div></div><span class="stat-icon i-green"><?= icon('clipboard') ?></span></div>
        <div class="stat-link">View polls &amp; surveys &rarr;</div>
      </a>
    </div>

    <section class="weather-outfit-card weather-mini-card" id="weatherOutfitCard" aria-label="Weather and outfit suggestions">
  <div class="weather-mini-top"><span class="weather-mini-label">TODAY'S WEATHER</span><span class="weather-location-row"><span class="weather-location-pin">⌖</span><span id="weatherLocation">Manolo Fortich, Bukidnon</span><button type="button" class="weather-locate-btn" id="weatherUseLocation" title="Use my location" aria-label="Use my location">Locate</button></span></div>
  <div class="weather-main">
    <div class="weather-current-row"><div class="weather-symbol" id="weatherSymbol" aria-hidden="true">🌤️</div><div class="weather-mini-reading"><div class="weather-temperature" id="weatherTemperature">--°</div><div class="weather-condition" id="weatherCondition">Loading weather…</div><div class="weather-feels" id="weatherFeels">Checking current conditions</div></div></div>
    <div class="weather-metrics"><div><span>Humidity</span><strong id="weatherHumidity">--%</strong></div><div><span>Rain chance</span><strong id="weatherRain">--%</strong></div><div><span>Wind</span><strong id="weatherWind">-- km/h</strong></div></div>
  </div>
  <div class="weather-outfit-panel"><div class="weather-outfit-icon" id="outfitIcon">👕</div><div class="weather-outfit-copy"><div class="weather-outfit-label">WHAT TO WEAR</div><h4 id="outfitTitle">Preparing outfit tips</h4><p id="outfitAdvice">Practical clothing suggestions will appear here.</p><div class="weather-outfit-note" id="outfitExtra">Check the forecast before leaving.</div></div></div>
  <div class="weather-search-row"><label for="weatherCityInput">Check another place</label><div class="weather-search-controls"><input id="weatherCityInput" type="text" placeholder="Enter city or town (e.g. Malaybalay)" maxlength="80"><button type="button" id="weatherSearchButton" class="btn btn-primary">Check</button></div><div class="weather-status" id="weatherStatus" role="status" aria-live="polite">Weather updates automatically when the dashboard opens.</div></div>
</section>

    <div class="dashboard-shell">
      <div class="dashboard-main">
      <div class="detail-card">
      <div class="ticket-top-row" style="margin-bottom:8px;">
        <div><h3 style="margin:0;font-size:17px;">Upcoming Events</h3><div style="font-size:12px;color:var(--slate);margin-top:3px;">What’s happening next at ICS</div></div>
        <a href="index.php?view=browse" style="font-size:12.5px;font-weight:700;color:var(--ink);">View all →</a>
      </div>
      <?php if (empty($dashUpcoming)): ?>
        <div class="feedback-empty">No upcoming events right now — check back soon.</div>
      <?php else: foreach ($dashUpcoming as $ev):
        $myReg = null;
        $rstmt = $conn->prepare("SELECT id FROM registrations WHERE event_id = ? AND user_id = ?");
        $rstmt->bind_param('ii', $ev['id'], $me['id']);
        $rstmt->execute();
        $myReg = $rstmt->get_result()->fetch_assoc();
        $regCount = count_registrations($conn, $ev['id']);
        if ($myReg) {
            $rowActions = '<form method="post" action="actions.php" style="display:inline;margin-left:auto;"><input type="hidden" name="action" value="cancel_registration"><input type="hidden" name="registration_id" value="' . (int)$myReg['id'] . '"><button class="btn btn-sm btn-danger">Cancel</button></form>';
        } else {
            $rowActions = '<button class="btn btn-sm btn-gold" style="margin-left:auto;" onclick="openReg(' . $ev['id'] . ', \'' . h(addslashes($ev['title'])) . '\', \'' . fmt_date($ev['event_date']) . '\')">Join This Event</button>';
        }
        agenda_row($conn, $ev, $rowActions);
      endforeach; endif; ?>
    </div>

    <?php if (!empty($dashPast)): ?>
    <div class="detail-card">
      <div class="ticket-top-row" style="margin-bottom:8px;">
        <h3 style="margin:0;font-size:15px;">Past Events</h3>
        <a href="index.php?view=browse" style="font-size:12.5px;font-weight:600;color:var(--ink);">View all</a>
      </div>
      <?php foreach ($dashPast as $ev): agenda_row($conn, $ev, ''); endforeach; ?>
    </div>
    <?php endif; ?>
      </div>
      <aside class="dashboard-side">
        <div class="quick-card">
          <h3>Make your voice count</h3>
          <p style="font-size:12.5px;line-height:1.6;color:var(--slate);margin:0 0 13px;">Check active polls and surveys from the SBO to share your ideas and feedback.</p>
          <a href="index.php?view=polls" class="btn btn-primary" style="width:100%;">Open polls</a>
        </div>
      </aside>
    </div>
    <?php
}

// ================== OFFICER DASHBOARD ==================
elseif ($view === 'dashboard' && $role === 'officer') {
    $officerEvents = get_events($conn, null, $me['id']);
    $officerUpcoming = array_filter($officerEvents, fn($e) => $e['status'] === 'approved' && !event_is_past($e));
    usort($officerUpcoming, fn($a, $b) => strcmp($a['event_date'], $b['event_date']));
    $officerUpcoming = array_slice($officerUpcoming, 0, 4);
    $officerPast = array_filter($officerEvents, fn($e) => $e['status'] === 'approved' && event_is_past($e));
    usort($officerPast, fn($a, $b) => strcmp($b['event_date'], $a['event_date']));
    $officerPast = array_slice($officerPast, 0, 3);
    $myEventCount = count($officerEvents);
    $myApprovedCount = count(array_filter($officerEvents, fn($e) => $e['status'] === 'approved'));
    $myPendingCount = count(array_filter($officerEvents, fn($e) => $e['status'] === 'pending'));
    $myRegistrationsCount = 0;
    $regStmt = $conn->prepare("SELECT COUNT(*) c FROM registrations r JOIN events e ON e.id = r.event_id WHERE e.created_by = ?");
    $regStmt->bind_param('i', $me['id']); $regStmt->execute();
    $myRegistrationsCount = (int)$regStmt->get_result()->fetch_assoc()['c']; $regStmt->close();
    ?>
    <div class="welcome-banner officer-welcome-banner">
      <div class="dashboard-kicker">ICS · SBO NEXUS · OFFICER PANEL</div>
      <h2>Welcome back, <?= h(explode(' ', $me['full_name'])[0]) ?> 👋</h2>
      <p>Manage your events, monitor student participation, and keep ICS activities organized in one place.</p>
    </div>

    <section class="weather-outfit-card weather-mini-card" id="weatherOutfitCard" aria-label="Weather and outfit suggestions">
  <div class="weather-mini-top"><span class="weather-mini-label">TODAY'S WEATHER</span><span class="weather-location-row"><span class="weather-location-pin">⌖</span><span id="weatherLocation">Manolo Fortich, Bukidnon</span><button type="button" class="weather-locate-btn" id="weatherUseLocation" title="Use my location" aria-label="Use my location">Locate</button></span></div>
  <div class="weather-main">
    <div class="weather-current-row"><div class="weather-symbol" id="weatherSymbol" aria-hidden="true">🌤️</div><div class="weather-mini-reading"><div class="weather-temperature" id="weatherTemperature">--°</div><div class="weather-condition" id="weatherCondition">Loading weather…</div><div class="weather-feels" id="weatherFeels">Checking current conditions</div></div></div>
    <div class="weather-metrics"><div><span>Humidity</span><strong id="weatherHumidity">--%</strong></div><div><span>Rain chance</span><strong id="weatherRain">--%</strong></div><div><span>Wind</span><strong id="weatherWind">-- km/h</strong></div></div>
  </div>
  <div class="weather-outfit-panel"><div class="weather-outfit-icon" id="outfitIcon">👕</div><div class="weather-outfit-copy"><div class="weather-outfit-label">WHAT TO WEAR</div><h4 id="outfitTitle">Preparing outfit tips</h4><p id="outfitAdvice">Practical clothing suggestions will appear here.</p><div class="weather-outfit-note" id="outfitExtra">Check the forecast before leaving.</div></div></div>
  <div class="weather-search-row"><label for="weatherCityInput">Check another place</label><div class="weather-search-controls"><input id="weatherCityInput" type="text" placeholder="Enter city or town (e.g. Malaybalay)" maxlength="80"><button type="button" id="weatherSearchButton" class="btn btn-primary">Check</button></div><div class="weather-status" id="weatherStatus" role="status" aria-live="polite">Weather updates automatically when the dashboard opens.</div></div>
</section>
    <div class="stats officer-dashboard-stats">
      <a class="stat stat-linked" href="index.php?view=myEvents"><div class="stat-top"><div><div class="n"><?= $myEventCount ?></div><div class="l">My Events</div></div></div><div class="stat-link">Manage events →</div></a>
      <a class="stat stat-linked" href="index.php?view=myEvents"><div class="stat-top"><div><div class="n"><?= $myApprovedCount ?></div><div class="l">Approved</div></div></div><div class="stat-link">View active events →</div></a>
      <a class="stat stat-linked" href="index.php?view=myEvents"><div class="stat-top"><div><div class="n"><?= $myPendingCount ?></div><div class="l">Pending Approval</div></div></div><div class="stat-link">Track submissions →</div></a>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $myRegistrationsCount ?></div><div class="l">Student Participation</div></div></div><div class="stat-link">Across your events</div></div>
    </div>
    <div class="dashboard-shell officer-dashboard-shell">
      <div class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">UP NEXT</div><h3>Upcoming events</h3><p>Keep an eye on the activities you are organizing.</p></div><a href="index.php?view=myEvents" class="officer-view-link">View all →</a></div>
        <?php if (empty($officerUpcoming)): ?>
          <div class="officer-empty"><div class="officer-empty-icon"><?= icon('calendar') ?></div><strong>No upcoming events yet</strong><p>Create an event to start organizing your next ICS activity.</p><a href="index.php?view=myEvents" class="btn btn-primary btn-sm">Open My Events</a></div>
        <?php else: foreach ($officerUpcoming as $ev):
          $regCount = count_registrations($conn, $ev['id']);
          agenda_row($conn, $ev, '<a class="btn btn-sm" href="index.php?view=eventDetail&event_id=' . (int)$ev['id'] . '">Open event</a>');
        endforeach; endif; ?>
      <?php if (!empty($officerPast)): ?>
      <div class="detail-card officer-past-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">HISTORY</div><h3>Recently completed</h3><p>Your latest approved events.</p></div></div>
        <?php foreach ($officerPast as $ev): agenda_row($conn, $ev, '<a class="btn btn-sm" href="index.php?view=roster&event_id=' . (int)$ev['id'] . '">Joined Students</a>'); endforeach; ?>
      </div>
      <?php endif; ?>
      </div>
      <aside class="dashboard-side officer-dashboard-side">
        <div class="quick-card officer-quick-card"><div class="officer-quick-icon"><?= icon('users') ?></div><h3>Student participation</h3><p>See who is going, manage attendance, and review participation for your approved events.</p><a href="index.php?view=myEvents" class="btn btn-primary">View my events</a></div>
        <div class="quick-card officer-quick-card"><div class="officer-quick-icon"><?= icon('clipboard') ?></div><h3>Polls & surveys</h3><p>Create polls, monitor responses, and collect student feedback for your activities.</p><a href="index.php?view=polls" class="btn">Open polls</a></div>
      </aside>
    </div>
    <?php
}

// ================== ADVISER DASHBOARD ==================
elseif ($view === 'dashboard' && $role === 'admin') {
    $one = function ($sql) use ($conn) {
        $r = $conn->query($sql);
        $row = $r ? $r->fetch_row() : null;
        return $row ? $row[0] : 0;
    };

    $studentCount = (int) $one("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $officerCount = (int) $one("SELECT COUNT(*) FROM users WHERE role = 'officer'");
    $completedCount = (int) $one("SELECT COUNT(*) FROM events WHERE status = 'approved' AND COALESCE(event_end_date, event_date) < CURDATE()");
    $openPolls = (int) $one("SELECT COUNT(*) FROM polls WHERE status = 'open'");
    $pollVotes = (int) $one("SELECT COUNT(*) FROM poll_votes");
    $feedbackCount = (int) $one("SELECT COUNT(*) FROM event_feedback");
    $avgRating = $one("SELECT AVG(rating) FROM event_feedback WHERE rating IS NOT NULL");

    // Attendance across every completed event.
    $attRow = $conn->query("SELECT COUNT(r.id) regs, COALESCE(SUM(r.attended), 0) att
                            FROM events e JOIN registrations r ON r.event_id = e.id
                            WHERE e.status = 'approved' AND COALESCE(e.event_end_date, e.event_date) < CURDATE()")->fetch_assoc();
    $doneRegs = (int) ($attRow['regs'] ?? 0);
    $doneAtt = (int) ($attRow['att'] ?? 0);
    $overallAttendance = $doneRegs > 0 ? round($doneAtt / $doneRegs * 100) : 0;

    // Events per month, last 6 months (approved events).
    $monthKeys = [];
    for ($i = 5; $i >= 0; $i--) {
        $monthKeys[date('Y-m', strtotime("first day of -$i month"))] = 0;
    }
    $startMonth = array_key_first($monthKeys) . '-01';
    $mStmt = $conn->prepare("SELECT DATE_FORMAT(event_date, '%Y-%m') ym, COUNT(*) c FROM events WHERE status = 'approved' AND event_date >= ? GROUP BY ym");
    $mStmt->bind_param('s', $startMonth);
    $mStmt->execute();
    foreach ($mStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $m) {
        if (isset($monthKeys[$m['ym']])) $monthKeys[$m['ym']] = (int) $m['c'];
    }
    $mStmt->close();
    $monthMax = max(1, max($monthKeys));

    // Students by year level.
    $yearData = [];
    $yr = $conn->query("SELECT COALESCE(year_level, 'Not set') yl, COUNT(*) c FROM users WHERE role = 'student' GROUP BY yl");
    if ($yr) foreach ($yr->fetch_all(MYSQLI_ASSOC) as $y) $yearData[$y['yl']] = (int) $y['c'];
    $yearOrder = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Not set'];
    $yearMax = max(1, $yearData ? max($yearData) : 1);

    // Attendance of the latest completed events.
    $recentDone = [];
    $rd = $conn->query("SELECT e.id, e.title, e.event_date, COUNT(r.id) regs, COALESCE(SUM(r.attended), 0) att
                        FROM events e LEFT JOIN registrations r ON r.event_id = e.id
                        WHERE e.status = 'approved' AND COALESCE(e.event_end_date, e.event_date) < CURDATE()
                        GROUP BY e.id ORDER BY e.event_date DESC LIMIT 6");
    if ($rd) $recentDone = $rd->fetch_all(MYSQLI_ASSOC);

    // Waiting for a decision.
    $pendingList = [];
    $pl = $conn->query("SELECT e.id, e.title, e.event_date, e.venue, u.full_name creator
                        FROM events e JOIN users u ON u.id = e.created_by
                        WHERE e.status = 'pending' ORDER BY e.created_at ASC LIMIT 5");
    if ($pl) $pendingList = $pl->fetch_all(MYSQLI_ASSOC);

    // Coming up next.
    $nextEvents = [];
    $ne = $conn->query("SELECT e.id, e.title, e.event_date, e.venue, u.full_name creator
                        FROM events e JOIN users u ON u.id = e.created_by
                        WHERE e.status = 'approved' AND e.event_date >= CURDATE()
                        ORDER BY e.event_date ASC, e.event_time ASC LIMIT 5");
    if ($ne) $nextEvents = $ne->fetch_all(MYSQLI_ASSOC);

    // How active each officer has been.
    $officerRows = [];
    $or = $conn->query("SELECT u.id, u.full_name, u.position_title, COUNT(DISTINCT e.id) events, COUNT(r.id) regs
                        FROM users u
                        LEFT JOIN events e ON e.created_by = u.id AND e.status = 'approved'
                        LEFT JOIN registrations r ON r.event_id = e.id
                        WHERE u.role = 'officer'
                        GROUP BY u.id ORDER BY events DESC, regs DESC, u.full_name ASC LIMIT 6");
    if ($or) $officerRows = $or->fetch_all(MYSQLI_ASSOC);

    // Newest accounts.
    $newUsers = [];
    $nu = $conn->query("SELECT full_name, email, role, created_at FROM users ORDER BY id DESC LIMIT 5");
    if ($nu) $newUsers = $nu->fetch_all(MYSQLI_ASSOC);

    $roleNames = ['student' => 'Student', 'officer' => 'Officer', 'admin' => 'Adviser'];
    ?>
    <div class="welcome-banner officer-welcome-banner">
      <div class="dashboard-kicker">ICS · SBO NEXUS · ADVISER PANEL</div>
      <h2>Welcome back, <?= h(explode(' ', $me['full_name'])[0]) ?></h2>
      <p>See how the organization is doing, what needs your decision, and how students are taking part.</p>
    </div>

    <section class="weather-outfit-card weather-mini-card" id="weatherOutfitCard" aria-label="Weather and outfit suggestions">
  <div class="weather-mini-top"><span class="weather-mini-label">TODAY'S WEATHER</span><span class="weather-location-row"><span class="weather-location-pin">⌖</span><span id="weatherLocation">Manolo Fortich, Bukidnon</span><button type="button" class="weather-locate-btn" id="weatherUseLocation" title="Use my location" aria-label="Use my location">Locate</button></span></div>
  <div class="weather-main">
    <div class="weather-current-row"><div class="weather-symbol" id="weatherSymbol" aria-hidden="true">🌤️</div><div class="weather-mini-reading"><div class="weather-temperature" id="weatherTemperature">--°</div><div class="weather-condition" id="weatherCondition">Loading weather…</div><div class="weather-feels" id="weatherFeels">Checking current conditions</div></div></div>
    <div class="weather-metrics"><div><span>Humidity</span><strong id="weatherHumidity">--%</strong></div><div><span>Rain chance</span><strong id="weatherRain">--%</strong></div><div><span>Wind</span><strong id="weatherWind">-- km/h</strong></div></div>
  </div>
  <div class="weather-outfit-panel"><div class="weather-outfit-icon" id="outfitIcon">👕</div><div class="weather-outfit-copy"><div class="weather-outfit-label">WHAT TO WEAR</div><h4 id="outfitTitle">Preparing outfit tips</h4><p id="outfitAdvice">Practical clothing suggestions will appear here.</p><div class="weather-outfit-note" id="outfitExtra">Check the forecast before leaving.</div></div></div>
  <div class="weather-search-row"><label for="weatherCityInput">Check another place</label><div class="weather-search-controls"><input id="weatherCityInput" type="text" placeholder="Enter city or town (e.g. Malaybalay)" maxlength="80"><button type="button" id="weatherSearchButton" class="btn btn-primary">Check</button></div><div class="weather-status" id="weatherStatus" role="status" aria-live="polite">Weather updates automatically when the dashboard opens.</div></div>
</section>

    <div class="stats officer-dashboard-stats">
      <a class="stat stat-linked" href="index.php?view=manageStudents&role=student"><div class="stat-top"><div><div class="n"><?= $studentCount ?></div><div class="l">Students</div></div></div><div class="stat-link">See student accounts →</div></a>
      <a class="stat stat-linked" href="index.php?view=manageStudents&role=officer"><div class="stat-top"><div><div class="n"><?= $officerCount ?></div><div class="l">SBO Officers</div></div></div><div class="stat-link">See officers and positions →</div></a>
      <a class="stat stat-linked" href="index.php?view=queue"><div class="stat-top"><div><div class="n"><?= (int) $pendingCount ?></div><div class="l">Pending approval</div></div></div><div class="stat-link">Open the queue →</div></a>
      <a class="stat stat-linked" href="index.php?view=allEvents"><div class="stat-top"><div><div class="n"><?= (int) $upcomingCount ?></div><div class="l">Upcoming events</div></div></div><div class="stat-link">See all events →</div></a>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $completedCount ?></div><div class="l">Completed events</div></div></div><div class="stat-link">Approved and finished</div></div>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= (int) $totalRegs ?></div><div class="l">Students Going</div></div></div><div class="stat-link">Across all events</div></div>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $overallAttendance ?>%</div><div class="l">Attendance rate</div></div></div><div class="stat-link"><?= $doneAtt ?> of <?= $doneRegs ?> at completed events</div></div>
      <a class="stat stat-linked" href="index.php?view=polls"><div class="stat-top"><div><div class="n"><?= $openPolls ?></div><div class="l">Open polls</div></div></div><div class="stat-link"><?= $pollVotes ?> vote<?= $pollVotes === 1 ? '' : 's' ?> so far →</div></a>
    </div>

    <div class="adm-grid">
      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">NEEDS YOU</div><h3>Waiting for approval</h3><p>Events officers submitted that students cannot see yet.</p></div><a href="index.php?view=queue" class="officer-view-link">Open queue →</a></div>
        <?php if (empty($pendingList)): ?>
          <div class="adm-empty">Nothing is waiting. All submitted events have been decided.</div>
        <?php else: foreach ($pendingList as $p): ?>
          <div class="adm-row">
            <div class="adm-row-main"><strong><?= h($p['title']) ?></strong><span><?= h(fmt_date($p['event_date'])) ?> · <?= h($p['venue']) ?> · by <?= h($p['creator']) ?></span></div>
            <a class="btn btn-sm btn-primary" href="index.php?view=queue">Review</a>
          </div>
        <?php endforeach; endif; ?>
      </section>

      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">UP NEXT</div><h3>Upcoming events</h3><p>The next approved events on the calendar.</p></div><a href="index.php?view=allEvents" class="officer-view-link">All events →</a></div>
        <?php if (empty($nextEvents)): ?>
          <div class="adm-empty">No upcoming approved events yet.</div>
        <?php else: foreach ($nextEvents as $n): ?>
          <div class="adm-row">
            <div class="adm-row-main"><strong><?= h($n['title']) ?></strong><span><?= h(fmt_date($n['event_date'])) ?> · <?= h($n['venue']) ?> · <?= (int) count_registrations($conn, $n['id']) ?> going</span></div>
            <a class="btn btn-sm" href="index.php?view=roster&event_id=<?= (int) $n['id'] ?>">Joined Students</a>
          </div>
        <?php endforeach; endif; ?>
      </section>
    </div>

    <div class="adm-grid">
      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">TREND</div><h3>Events per month</h3><p>Approved events over the last 6 months.</p></div></div>
        <div class="col-chart">
          <?php foreach ($monthKeys as $ym => $c): ?>
            <div class="col-item">
              <div class="col-value"><?= (int) $c ?></div>
              <div class="col-bar" style="height:<?= $c > 0 ? max(6, round($c / $monthMax * 100)) : 2 ?>%"></div>
              <div class="col-label"><?= h(date('M', strtotime($ym . '-01'))) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">STUDENTS</div><h3>Students by year level</h3><p><?= $studentCount ?> student account<?= $studentCount === 1 ? '' : 's' ?> in total.</p></div></div>
        <?php if ($studentCount === 0): ?>
          <div class="adm-empty">No student accounts yet.</div>
        <?php else: foreach ($yearOrder as $yl): if (empty($yearData[$yl])) continue; ?>
          <div class="bar-row">
            <div class="bar-label"><?= h($yl) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= round($yearData[$yl] / $yearMax * 100) ?>%"></div></div>
            <div class="bar-value"><?= (int) $yearData[$yl] ?></div>
          </div>
        <?php endforeach; endif; ?>
      </section>
    </div>

    <div class="adm-grid">
      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">PARTICIPATION</div><h3>Attendance at recent events</h3><p>Students who checked in compared with those who are going.</p></div></div>
        <?php if (empty($recentDone)): ?>
          <div class="adm-empty">No completed events yet.</div>
        <?php else: foreach ($recentDone as $d):
            $rate = (int) $d['regs'] > 0 ? round($d['att'] / $d['regs'] * 100) : 0; ?>
          <div class="bar-row bar-row-wide">
            <div class="bar-label" title="<?= h($d['title']) ?>"><?= h($d['title']) ?></div>
            <div class="bar-track"><div class="bar-fill" style="width:<?= $rate ?>%"></div></div>
            <div class="bar-value"><?= $rate ?>% <span><?= (int) $d['att'] ?>/<?= (int) $d['regs'] ?></span></div>
          </div>
        <?php endforeach; endif; ?>
      </section>

      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">OFFICERS</div><h3>Officer activity</h3><p>Approved events each officer has organized.</p></div><a href="index.php?view=manageStudents" class="officer-view-link">Manage →</a></div>
        <?php if (empty($officerRows)): ?>
          <div class="adm-empty">No officers yet. Assign officers from Students &amp; Officers.</div>
        <?php else: foreach ($officerRows as $o): ?>
          <div class="adm-row">
            <div class="adm-row-main"><strong><?= h($o['full_name']) ?></strong><span><?= h($o['position_title'] ?: 'Position not set') ?></span></div>
            <div class="adm-pill"><?= (int) $o['events'] ?> event<?= (int) $o['events'] === 1 ? '' : 's' ?> · <?= (int) $o['regs'] ?> going</div>
          </div>
        <?php endforeach; endif; ?>
      </section>
    </div>

    <div class="adm-grid">
      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">FEEDBACK</div><h3>Student feedback</h3><p>What students say about events.</p></div></div>
        <div class="adm-feedback">
          <div><div class="n"><?= $avgRating !== null && $avgRating !== 0 ? number_format((float) $avgRating, 1) : 'None' ?></div><div class="l">Average rating out of 5</div></div>
          <div><div class="n"><?= $feedbackCount ?></div><div class="l">Feedback received</div></div>
        </div>
      </section>

      <section class="detail-card">
        <div class="officer-section-head"><div><div class="officer-section-kicker">PEOPLE</div><h3>Newest accounts</h3><p>The latest people to join the system.</p></div></div>
        <?php if (empty($newUsers)): ?>
          <div class="adm-empty">No accounts yet.</div>
        <?php else: foreach ($newUsers as $u): ?>
          <div class="adm-row">
            <div class="adm-row-main"><strong><?= h($u['full_name']) ?></strong><span><?= h($u['email']) ?></span></div>
            <div class="adm-pill"><?= h($roleNames[$u['role']] ?? $u['role']) ?><?= !empty($u['created_at']) ? ' · ' . h(date('M j', strtotime($u['created_at']))) : '' ?></div>
          </div>
        <?php endforeach; endif; ?>
      </section>
    </div>
    <?php
}

// ================== ACTIVITY LOG (Adviser) ==================
elseif ($view === 'activityLog' && $role === 'admin') {
    ensure_activity_log_schema($conn);

    $f = [
        'q' => trim((string) ($_GET['q'] ?? '')),
        'role' => (string) ($_GET['role'] ?? ''),
        'ev' => (string) ($_GET['ev'] ?? ''),
        'from' => (string) ($_GET['from'] ?? ''),
        'to' => (string) ($_GET['to'] ?? ''),
    ];
    $perPage = 25;
    $totalRows = activity_log_count($conn, $f);
    $pages = max(1, (int) ceil($totalRows / $perPage));
    $pageNo = max(1, min($pages, (int) ($_GET['page'] ?? 1)));
    $logRows = activity_log_rows($conn, $f, $perPage, ($pageNo - 1) * $perPage);

    $since24 = date('Y-m-d H:i:s', strtotime('-24 hours'));
    $since7 = date('Y-m-d H:i:s', strtotime('-7 days'));
    $today = date('Y-m-d') . ' 00:00:00';
    $count = function ($sql, $arg) use ($conn) {
        $st = $conn->prepare($sql);
        $st->bind_param('s', $arg);
        $st->execute();
        $n = (int) $st->get_result()->fetch_row()[0];
        $st->close();
        return $n;
    };
    $loginsToday = $count("SELECT COUNT(*) FROM login_logs WHERE event = 'login' AND created_at >= ?", $today);
    $peopleDay = $count("SELECT COUNT(DISTINCT user_id) FROM login_logs WHERE event = 'login' AND user_id IS NOT NULL AND created_at >= ?", $since24);
    $failedDay = $count("SELECT COUNT(*) FROM login_logs WHERE event = 'login_failed' AND created_at >= ?", $since24);
    $logins7 = $count("SELECT COUNT(*) FROM login_logs WHERE event = 'login' AND created_at >= ?", $since7);

    $eventLabels = activity_event_labels();
    $eventBadge = [
        'login' => 'badge-approved', 'register' => 'badge-approved', 'login_failed' => 'badge-rejected',
        'logout' => 'badge-pending', 'password_changed' => 'badge-pending', 'password_reset' => 'badge-pending',
    ];
    $roleNames = ['student' => 'Student', 'officer' => 'SBO Officer', 'admin' => 'Adviser'];
    $qs = http_build_query(array_filter($f, fn($v) => $v !== ''));
    $pageUrl = function ($n) use ($qs) { return 'index.php?view=activityLog' . ($qs !== '' ? '&' . $qs : '') . '&page=' . (int) $n; };
    ?>
    <div class="topbar">
      <div>
        <h2>Activity Log</h2>
        <p class="sub">Who signed in, when, and from what device. Times are Philippine time. Lines older than <?= (int) ACTIVITY_LOG_KEEP_DAYS ?> days are removed automatically.</p>
      </div>
    </div>

    <div class="stats officer-dashboard-stats">
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $loginsToday ?></div><div class="l">Logins today</div></div></div><div class="stat-link">Since midnight</div></div>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $peopleDay ?></div><div class="l">People signed in</div></div></div><div class="stat-link">In the last 24 hours</div></div>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $failedDay ?></div><div class="l">Failed attempts</div></div></div><div class="stat-link">In the last 24 hours</div></div>
      <div class="stat"><div class="stat-top"><div><div class="n"><?= $logins7 ?></div><div class="l">Logins this week</div></div></div><div class="stat-link">In the last 7 days</div></div>
    </div>

    <form method="get" action="index.php" class="detail-card log-filters" style="max-width:none;margin-bottom:16px;">
      <input type="hidden" name="view" value="activityLog">
      <div class="log-filter-grid">
        <div class="field"><label>Search</label><input type="text" name="q" value="<?= h($f['q']) ?>" placeholder="Name, email, or IP address"></div>
        <div class="field"><label>Who</label>
          <select name="role">
            <option value="">Everyone</option>
            <?php foreach ($roleNames as $rk => $rn): ?><option value="<?= h($rk) ?>" <?= $f['role'] === $rk ? 'selected' : '' ?>><?= h($rn) ?>s</option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Activity</label>
          <select name="ev">
            <option value="">All activity</option>
            <?php foreach ($eventLabels as $ek => $el): ?><option value="<?= h($ek) ?>" <?= $f['ev'] === $ek ? 'selected' : '' ?>><?= h($el) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>From</label><input type="date" name="from" value="<?= h($f['from']) ?>"></div>
        <div class="field"><label>To</label><input type="date" name="to" value="<?= h($f['to']) ?>"></div>
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a class="btn btn-sm" href="index.php?view=activityLog">Clear</a>
        <a class="btn btn-sm" href="activity_export.php<?= $qs !== '' ? '?' . h($qs) : '' ?>">Download CSV</a>
      </div>
    </form>

    <?php if (empty($logRows)): ?>
      <div class="detail-card" style="max-width:none;text-align:center;color:var(--slate);padding:40px 20px;">
        <?= $totalRows === 0 && $qs === '' ? 'No activity has been recorded yet. Sign-ins will appear here from now on.' : 'No activity matches these filters.' ?>
      </div>
    <?php else: ?>
      <div class="log-table-wrap">
        <table class="log-table">
          <thead><tr><th>When</th><th>Person</th><th>Activity</th><th>How</th><th>Device</th><th>IP address</th></tr></thead>
          <tbody>
          <?php foreach ($logRows as $r):
              $dev = describe_user_agent($r['user_agent']);
              $ago = activity_ago($r['created_at']);
              $failed = $r['event'] === 'login_failed'; ?>
            <tr class="<?= $failed ? 'log-failed' : '' ?>">
              <td class="mono"><?= h(date('M j, Y', strtotime($r['created_at']))) ?><br><strong><?= h(date('g:i A', strtotime($r['created_at']))) ?></strong><?= $ago !== '' ? '<br><span class="sub">' . h($ago) . '</span>' : '' ?></td>
              <td>
                <strong><?= h($r['full_name'] ?: 'Unknown account') ?></strong><br>
                <span class="sub"><?= h($r['email']) ?></span>
                <?php if (!empty($r['user_role'])): ?><br><span class="sub"><?= h($roleNames[$r['user_role']] ?? $r['user_role']) ?></span><?php endif; ?>
              </td>
              <td>
                <span class="badge <?= h($eventBadge[$r['event']] ?? 'badge-pending') ?>"><?= h($eventLabels[$r['event']] ?? $r['event']) ?></span>
                <?php if ($r['detail'] !== ''): ?><br><span class="sub"><?= h($r['detail']) ?></span><?php endif; ?>
              </td>
              <td><?= $r['method'] === 'google' ? 'Google' : ($r['method'] === 'password' ? 'Password' : '') ?></td>
              <td><?= h($dev['label']) ?><?= $dev['type'] !== '' ? '<br><span class="sub">' . h($dev['type']) . '</span>' : '' ?></td>
              <td class="mono"><?= h($r['ip'] ?: 'Unknown') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="log-pager">
        <span class="sub">Page <?= $pageNo ?> of <?= $pages ?> · <?= $totalRows ?> record<?= $totalRows === 1 ? '' : 's' ?></span>
        <span>
          <?php if ($pageNo > 1): ?><a class="btn btn-sm" href="<?= h($pageUrl($pageNo - 1)) ?>">&larr; Newer</a><?php endif; ?>
          <?php if ($pageNo < $pages): ?><a class="btn btn-sm" href="<?= h($pageUrl($pageNo + 1)) ?>">Older &rarr;</a><?php endif; ?>
        </span>
      </div>
    <?php endif; ?>

    <form method="post" action="actions.php" style="margin-top:22px;" onsubmit="return confirm('Remove all activity lines older than 90 days? This cannot be undone.');">
      <input type="hidden" name="action" value="purge_activity_log">
      <button type="submit" class="btn btn-sm btn-danger">Clear lines older than 90 days</button>
    </form>
    <?php
}

// ================== MY SCHEDULE ==================
elseif ($view === 'mySchedule' && $role === 'student') {
    $ts = strtotime($schedMonth . '-01');
    $monthLabel = date('F Y', $ts);
    $daysInMonth = (int) date('t', $ts);
    $firstDow = (int) date('w', $ts); // 0=Sun
    $prevMonth = date('Y-m', strtotime('-1 month', $ts));
    $nextMonth = date('Y-m', strtotime('+1 month', $ts));
    $todayMonth = date('Y-m');
    $selectedLabel = $selectedDay === $todayStr ? 'Today' : date('D, M j', strtotime($selectedDay));
    ?>
    <?php
    $monthEventCount = 0;
    $upcomingCount = 0;
    foreach ($schedEvents as $se) {
        if (substr($se['event_date'], 0, 7) === $schedMonth) $monthEventCount++;
        if ($se['event_date'] >= $todayStr) $upcomingCount++;
    }
    $selectedCount = count($selectedDayEvents);
    ?>
    <div class="schedule-hero">
      <div>
        <span class="schedule-kicker">STUDENT EVENT PLANNER</span>
        <h2>My Schedule</h2>
        <p>See all the events you've joined, explore them by date, and keep your school activities organized.</p>
      </div>
      <div class="schedule-hero-icon"><?= icon('calendar') ?></div>
    </div>

    <div class="schedule-stats">
      <div class="schedule-stat"><div class="schedule-stat-icon"><?= icon('calendar') ?></div><div><strong><?= (int)count($schedEvents) ?></strong><span>Events joined</span></div></div>
      <div class="schedule-stat"><div class="schedule-stat-icon"><?= icon('clock') ?></div><div><strong><?= (int)$upcomingCount ?></strong><span>Upcoming events</span></div></div>
      <div class="schedule-stat"><div class="schedule-stat-icon"><?= icon('calendar') ?></div><div><strong><?= (int)$monthEventCount ?></strong><span>Events this month</span></div></div>
    </div>

    <div class="schedule-layout">
      <section class="detail-card schedule-calendar-card">
        <div class="schedule-calendar-head">
          <div><span class="schedule-mini-label">YOUR EVENTS</span><h3><?= h($monthLabel) ?></h3></div>
          <div class="schedule-nav">
            <a class="btn btn-sm" aria-label="Previous month" href="index.php?view=mySchedule&month=<?= $prevMonth ?>">&larr;</a>
            <a class="btn btn-sm" href="index.php?view=mySchedule&month=<?= $todayMonth ?>&day=<?= $todayStr ?>">Today</a>
            <a class="btn btn-sm" aria-label="Next month" href="index.php?view=mySchedule&month=<?= $nextMonth ?>">&rarr;</a>
          </div>
        </div>
        <div class="schedule-legend"><span><i class="legend-dot event"></i> Going</span><span><i class="legend-dot today"></i> Today</span><span>Click a date to view details</span></div>
        <div class="cal-grid schedule-cal-grid">
          <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?><div class="cal-dow"><?= $d ?></div><?php endforeach; ?>
          <?php for ($i = 0; $i < $firstDow; $i++): ?><div class="cal-day empty"></div><?php endfor; ?>
          <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr = date('Y-m-d', strtotime($schedMonth . '-' . str_pad($d, 2, '0', STR_PAD_LEFT)));
            $dayEvents = $schedEventsByDate[$dateStr] ?? [];
            $hasEvent = !empty($dayEvents);
            $isToday = $dateStr === $todayStr;
            $isSelected = $dateStr === $selectedDay;
          ?>
          <a class="cal-day schedule-cal-day <?= $hasEvent ? 'has-event' : '' ?> <?= $isToday ? 'today' : '' ?> <?= $isSelected ? 'selected' : '' ?>" href="index.php?view=mySchedule&month=<?= $schedMonth ?>&day=<?= $dateStr ?>" title="<?= $hasEvent ? h(implode(' • ', array_column($dayEvents, 'title'))) : 'No events' ?>">
            <span class="cal-number"><?= $d ?></span>
            <?php if ($hasEvent): ?>
              <span class="calendar-event-count"><?= count($dayEvents) ?> event<?= count($dayEvents) > 1 ? 's' : '' ?></span>
              <span class="calendar-event-list">
                <?php foreach (array_slice($dayEvents, 0, 2) as $ce): ?><span><?= h($ce['title']) ?></span><?php endforeach; ?>
                <?php if (count($dayEvents) > 2): ?><span class="calendar-more">+<?= count($dayEvents)-2 ?> more</span><?php endif; ?>
              </span>
            <?php endif; ?>
          </a>
          <?php endfor; ?>
        </div>
      </section>

      <section class="detail-card schedule-day-card">
        <div class="schedule-day-head">
          <div><span class="schedule-mini-label">SELECTED DAY</span><h3><?= h($selectedLabel) ?></h3><p><?= date('F j, Y', strtotime($selectedDay)) ?></p></div>
          <div class="day-count"><?= $selectedCount ?><small>event<?= $selectedCount === 1 ? '' : 's' ?></small></div>
        </div>
        <?php if (empty($selectedDayEvents)): ?>
          <div class="schedule-empty"><div class="schedule-empty-icon"><?= icon('calendar') ?></div><strong>No events on this day</strong><p>Pick another date on the calendar to see your joined activities.</p><a class="btn btn-sm btn-primary" href="index.php?view=browse">Browse Events</a></div>
        <?php else: foreach ($selectedDayEvents as $ev):
          $accent = category_color($ev['category']);
          echo '<a class="schedule-event-card" href="index.php?view=eventDetail&event_id=' . (int)$ev['id'] . '">';
          echo '<span class="schedule-event-accent" style="background:' . $accent . ';"></span>';
          echo '<div class="schedule-event-time">' . h(fmt_time($ev['event_time'])) . '</div>';
          echo '<div class="schedule-event-content"><h4>' . h($ev['title']) . '</h4><div><span>' . icon('pin') . h($ev['venue']) . '</span><span class="schedule-category">' . h($ev['category']) . '</span></div></div>';
          echo '<span class="schedule-event-arrow">&rarr;</span></a>';
        endforeach; endif; ?>
      </section>
    </div>
    <?php
}

// ================== ROSTER PAGE ==================
elseif ($view === 'roster') {
    ?>
    <a class="back-link" href="index.php?view=<?= $role==='officer'?'myEvents':($role==='admin'?'allEvents':'browse') ?>">&larr; Back</a>
    <div class="topbar">
      <div><h2><?= h($event['title']) ?></h2><p class="sub"><?= h(fmt_event_date_range($event)) ?> · <?= count($regs) ?> going</p></div>
      <div class="topbar-actions">
        <a class="btn btn-sm btn-gold" href="index.php?view=attendance&event_id=<?= (int)$event_id ?>"><?= icon('pin', 'meta-icon') ?> Attendance list</a>
      </div>
    </div>
    <table>
      <thead><tr><th>Name</th><th>ID number</th><th>Year level</th><th>Course</th></tr></thead>
      <tbody>
      <?php if (empty($regs)): ?>
        <tr><td colspan="4" style="color:var(--slate);text-align:center;padding:20px;">No students have joined yet.</td></tr>
      <?php else: foreach ($regs as $r): ?>
        <tr>
          <td><?= h($r['full_name']) ?></td>
          <td class="mono"><?= h($r['student_id']) ?></td>
          <td><?= h($r['year_level'] ?: '—') ?></td>
          <td><?= h($r['course'] ?: '—') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
    <?php
}

// ================== ATTENDANCE LIST (who has checked in) ==================
elseif ($view === 'attendance') {
    $isOwner = $role === 'officer' && (int)$event['created_by'] === (int)$me['id'];
    $radiusM = event_geofence_radius($event);
    $attStatus = $event['attendance_status'] ?? 'auto';
    $attState = attendance_checkin_state($event);
    $stateLabel = [
        'open' => 'Open (started by you)',
        'auto' => 'Open (following the event schedule)',
        'paused' => 'Paused',
        'closed' => 'Stopped',
        'ended' => 'Ended',
        'not_ongoing' => 'Waiting for the event to start (press Start to open it early)',
    ][$attState['reason']] ?? 'Unknown';
    $attendedRegs = array_values(array_filter($regs, fn($r) => (int)$r['attended'] === 1));
    $pendingRegs = array_values(array_filter($regs, fn($r) => (int)$r['attended'] !== 1));
    $attendedByYear = group_registrations_by_year($attendedRegs);
    $yearLevelOrder = ['1st Year', '2nd Year', '3rd Year', '4th Year', 'Year level not set'];

    // Two self check-ins with the exact same coordinates are very unlikely from
    // real GPS, so they are flagged for the officer to review.
    $coordCounts = [];
    foreach ($attendedRegs as $r) {
        if ($r['checkin_method'] === 'geofence' && $r['checkin_lat'] !== null && $r['checkin_lng'] !== null) {
            $k = $r['checkin_lat'] . ',' . $r['checkin_lng'];
            $coordCounts[$k] = ($coordCounts[$k] ?? 0) + 1;
        }
    }
    ?>
    <a class="back-link" href="index.php?view=roster&event_id=<?= (int)$event_id ?>">&larr; Back to joined students</a>
    <div class="topbar">
      <div><h2><?= h($event['title']) ?> — Attendance</h2><p class="sub"><?= count($attendedRegs) ?> of <?= count($regs) ?> students going have checked in</p></div>
    </div>

    <?php if ($isOwner): ?>
      <div class="detail-card" style="max-width:1100px;margin-bottom:18px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;">
          <div>
            <strong>Check-in range: <?= (int)$radiusM ?> meters</strong>
            <div class="sub" style="margin-top:4px;">
              Students must be within this distance of the pin and have a clear GPS signal.
              Attendance is: <strong><?= h($stateLabel) ?></strong>
            </div>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <a class="btn btn-sm" href="index.php?view=editEvent&event_id=<?= (int)$event['id'] ?>">Change range or pin</a>
            <?php foreach ([
                ['start', 'Start attendance', 'btn-primary', $attStatus !== 'open', ''],
                ['pause', 'Pause', '', in_array($attStatus, ['open', 'auto'], true), ''],
                ['stop', 'Stop', 'btn-danger', $attStatus !== 'closed', 'Stop attendance? Students will no longer be able to check in.'],
            ] as $btn): ?>
              <form method="post" action="actions.php" style="display:inline;"<?= $btn[4] !== '' ? ' onsubmit="return confirm(\'' . $btn[4] . '\');"' : '' ?>>
                <input type="hidden" name="action" value="set_checkin_status">
                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                <input type="hidden" name="status" value="<?= $btn[0] ?>">
                <button type="submit" class="btn btn-sm <?= $btn[2] ?>" <?= $btn[3] ? '' : 'disabled' ?>><?= $btn[1] ?></button>
              </form>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!empty($attendedRegs)): ?>
      <div class="field" style="max-width:340px;margin-bottom:14px;">
        <input type="text" id="attendanceSearch" placeholder="Search by name…" oninput="filterAttendance(this.value)">
      </div>
    <?php endif; ?>

    <?php if (empty($attendedRegs)): ?>
      <div style="color:var(--slate);text-align:center;padding:40px 20px;">No students have checked in for attendance yet.</div>
    <?php else: ?>
      <?php foreach ($yearLevelOrder as $yl): if (empty($attendedByYear[$yl])) continue; ?>
        <div class="roster-year-group" data-year-group>
          <h3 style="margin:22px 0 8px;font-size:15px;"><?= h($yl) ?> <span class="mono" style="font-weight:400;color:var(--slate);font-size:12.5px;">(<?= count($attendedByYear[$yl]) ?>)</span></h3>
          <table>
            <thead><tr><th>Name</th><th>ID number</th><th>Course</th><th>How</th><th>Checked in at</th><th>Location check</th><?php if ($isOwner): ?><th></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($attendedByYear[$yl] as $r):
                $shared = false;
                if ($r['checkin_method'] === 'geofence' && $r['checkin_lat'] !== null && $r['checkin_lng'] !== null) {
                    $shared = ($coordCounts[$r['checkin_lat'] . ',' . $r['checkin_lng']] ?? 0) > 1;
                }
            ?>
              <tr data-name="<?= h(mb_strtolower($r['full_name'])) ?>">
                <td><?= h($r['full_name']) ?></td>
                <td class="mono"><?= h($r['student_id']) ?></td>
                <td><?= h($r['course'] ?: '—') ?></td>
                <td>
                  <?php if ($r['checkin_method'] === 'geofence'): ?>
                    <span class="badge badge-approved">📍 Self check-in</span>
                  <?php else: ?>
                    <span class="badge badge-pending">✓ Marked by officer</span>
                  <?php endif; ?>
                </td>
                <td class="mono" style="font-size:12.5px;"><?= h(fmt_datetime_full($r['checked_in_at'] ?? null)) ?: '—' ?></td>
                <td class="mono" style="font-size:12.5px;">
                  <?php if ($r['checkin_distance_m'] !== null): ?>
                    <?= (int)$r['checkin_distance_m'] ?> m from pin, GPS ±<?= (int)$r['checkin_accuracy_m'] ?> m
                    <?php if ($shared): ?><br><span class="badge badge-rejected">Review: same exact location as another check-in</span><?php endif; ?>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <?php if ($isOwner): ?>
                <td>
                  <form method="post" action="actions.php" style="display:inline;" onsubmit="return confirm('Remove this check-in? The student will no longer be marked present.');">
                    <input type="hidden" name="action" value="set_attendance">
                    <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                    <input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="mark" value="0">
                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                  </form>
                </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endforeach; ?>
      <script>
      function filterAttendance(q) {
          q = q.trim().toLowerCase();
          document.querySelectorAll('.roster-year-group').forEach(function (group) {
              var anyVisible = false;
              group.querySelectorAll('tbody tr[data-name]').forEach(function (row) {
                  var match = row.getAttribute('data-name').indexOf(q) !== -1;
                  row.style.display = match ? '' : 'none';
                  if (match) anyVisible = true;
              });
              group.style.display = anyVisible ? '' : 'none';
          });
      }
      </script>
    <?php endif; ?>

    <?php if ($isOwner && !empty($pendingRegs)): ?>
      <h3 style="margin:30px 0 8px;font-size:15px;">Not checked in yet <span class="mono" style="font-weight:400;color:var(--slate);font-size:12.5px;">(<?= count($pendingRegs) ?>)</span></h3>
      <p class="sub" style="margin-bottom:8px;">If a student is at the event but their GPS failed, you can mark them present yourself after checking in person.</p>
      <table>
        <thead><tr><th>Name</th><th>ID number</th><th>Course</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pendingRegs as $r): ?>
          <tr>
            <td><?= h($r['full_name']) ?></td>
            <td class="mono"><?= h($r['student_id']) ?></td>
            <td><?= h($r['course'] ?: '—') ?></td>
            <td>
              <form method="post" action="actions.php" style="display:inline;">
                <input type="hidden" name="action" value="set_attendance">
                <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                <input type="hidden" name="registration_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="mark" value="1">
                <button type="submit" class="btn btn-sm btn-primary">Mark present</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
    <?php
}

// ================== EVENT DETAIL + FEEDBACK ==================
elseif ($view === 'eventDetail') {
    $backView = $role === 'student' ? 'browse' : ($role === 'officer' ? 'myEvents' : 'allEvents');
    $catColor = category_color($event['category']);
    $catSlug = strtolower($event['category']);
    ?>
    <a class="back-link" href="index.php?view=<?= $backView ?>">&larr; Back</a>

    <?php if ($eventHeroPhoto): ?>
      <img src="<?= h($eventHeroPhoto) ?>" class="detail-hero-img" alt="">
    <?php else: ?>
      <div class="detail-hero-placeholder" style="background:<?= $catColor ?>;"><?= category_icon($event['category']) ?></div>
    <?php endif; ?>

    <div>
      <div class="detail-card">
          <div class="ticket-top-row" style="margin-bottom:8px;">
            <div class="detail-rating-row">
              <span class="cat-chip <?= $catSlug ?>"><?= category_icon($event['category']) ?><?= h($event['category']) ?></span>
              <?php if ($ratingSummary['avg']): ?>
                <?= star_html($ratingSummary['avg'], 15) ?>
                <span style="font-size:12.5px;color:var(--slate);"><?= $ratingSummary['avg'] ?> &middot; <?= $ratingSummary['count'] ?> rating<?= $ratingSummary['count'] === 1 ? '' : 's' ?></span>
              <?php endif; ?>
            </div>
            <?php
            $badgeClass = ['pending' => 'badge-pending', 'approved' => 'badge-approved', 'rejected' => 'badge-rejected'][$event['status']];
            $badgeLabel = ['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Not approved'][$event['status']];
            ?>
            <span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
          </div>

          <h2 style="margin:0 0 10px;font-size:22px;"><?= h($event['title']) ?></h2>

          <div class="detail-grid">
            <div class="detail-item"><div class="k"><?= icon('calendar') ?>Date</div><div class="v"><?= h(fmt_event_date_range($event)) ?></div></div>
            <div class="detail-item"><div class="k"><?= icon('clock') ?>Time</div><div class="v"><?= h(fmt_event_time_range($event)) ?></div></div>
            <div class="detail-item"><div class="k">⏱ Duration</div><div class="v"><?= event_duration_days($event) ?> day<?= event_duration_days($event) === 1 ? "" : "s" ?></div></div>
            <div class="detail-item"><div class="k"><?= icon('pin') ?>Venue</div><div class="v"><?= h($event['venue']) ?></div></div>
            <div class="detail-item detail-joined-item">
              <div class="k"><?= icon('users') ?>Joined</div>
              <div class="detail-joined-row">
                <div class="detail-joined-avatars" aria-label="<?= (int)$eventRegCount ?> students joined">
                  <?php foreach ($eventJoinedStudents as $joinedStudent): ?>
                    <?= avatar_html($joinedStudent, 38) ?>
                  <?php endforeach; ?>
                </div>
                <?php if ($eventRegCount > count($eventJoinedStudents)): ?>
                  <span class="detail-joined-more">+<?= (int)($eventRegCount - count($eventJoinedStudents)) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($event['description']): ?>
            <section class="detail-about-event" aria-labelledby="detailAboutEventTitle">
              <h3 class="detail-about-event-title" id="detailAboutEventTitle">About This Event</h3>
              <p class="detail-desc"><?= nl2br(h($event['description'])) ?></p>
            </section>
          <?php endif; ?>

          <?php if ($role === 'student' && $event['status'] === 'approved'): ?>
            <div style="margin-top:18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <?php if (event_is_past($event)): ?>
                <span class="mono" style="font-size:12px;color:var(--slate)">Event ended</span>
              <?php elseif ($myEventReg): ?>

                <?php if ((int)$myEventReg['attended'] === 1): ?>
                  <span class="badge badge-approved">✓ Attendance confirmed</span>
                <?php elseif ($event['latitude'] === null || $event['longitude'] === null): ?>
                  <span class="mono" style="font-size:12px;color:var(--slate)">Attendance check-in not set up for this event</span>
                <?php elseif (!attendance_checkin_state($event)['open']): ?>
                  <span class="mono" style="font-size:12px;color:var(--slate)"><?= h(str_ireplace('Check-in opens once the event starts', 'Attendance opens once the event starts', attendance_state_text(attendance_checkin_state($event)['reason']))) ?></span>
                <?php else: ?>
                  <form method="post" action="actions.php" id="checkinForm" style="display:inline;">
                    <input type="hidden" name="action" value="check_in_attendance">
                    <input type="hidden" name="event_id" value="<?= (int)$event['id'] ?>">
                    <input type="hidden" name="lat" id="checkinLat">
                    <input type="hidden" name="lng" id="checkinLng">
                    <input type="hidden" name="acc" id="checkinAcc">
                    <button type="button" class="btn btn-sm btn-gold" onclick="submitCheckIn(this)"><?= icon('pin', 'meta-icon') ?> Check in (uses your location)</button>
                    <span class="mono" style="font-size:11.5px;color:var(--slate);">Allowed within <?= (int) event_geofence_radius($event) ?> m of the venue</span>
                  </form>
                  <script>
                  function submitCheckIn(btn) {
                      if (!navigator.geolocation) {
                          alert('Your browser does not support location access, so check-in is not possible here.');
                          return;
                      }
                      btn.disabled = true;
                      btn.textContent = 'Getting your location…';
                      navigator.geolocation.getCurrentPosition(
                          function (pos) {
                              document.getElementById('checkinLat').value = pos.coords.latitude;
                              document.getElementById('checkinLng').value = pos.coords.longitude;
                              document.getElementById('checkinAcc').value = pos.coords.accuracy;
                              document.getElementById('checkinForm').submit();
                          },
                          function () {
                              alert('Location access was blocked or unavailable. Please allow location access in your browser and try again.');
                              btn.disabled = false;
                              btn.textContent = 'Check in (uses your location)';
                          },
                          { enableHighAccuracy: true, timeout: 15000 }
                      );
                  }
                  </script>
                <?php endif; ?>
              <?php else: ?>
                <button class="btn btn-sm btn-gold" onclick="openReg(<?= $event['id'] ?>, '<?= h(addslashes($event['title'])) ?>', '<?= fmt_date($event['event_date']) ?>')">Join This Event</button>
              <?php endif; ?>
            </div>
          <?php elseif (in_array($role, ['officer','admin'], true)): ?>
            <div style="margin-top:18px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
              <?php if ($role === 'officer' && (int)$event['created_by'] === (int)$me['id']): ?>
                <a class="btn btn-sm btn-primary" href="index.php?view=editEvent&event_id=<?= (int)$event['id'] ?>">Edit Event</a>
              <?php endif; ?>
              <a class="btn btn-sm" href="index.php?view=roster&event_id=<?= (int)$event['id'] ?>">View Joined Students</a>
              <a class="btn btn-sm" href="index.php?view=<?= $role === 'officer' ? 'myEvents' : 'queue' ?>">Back to <?= $role === 'officer' ? 'My Events' : 'Approval Queue' ?></a>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($event['latitude'] !== null && $event['longitude'] !== null): ?>
          <div class="section-title">Location</div>
          <div id="eventMap" class="event-map" data-lat="<?= h($event['latitude']) ?>" data-lng="<?= h($event['longitude']) ?>" data-venue="<?= h($event['venue']) ?>"></div>
        <?php endif; ?>

        <div class="section-title">Committees</div>
        <div class="organizer-list">
          <div class="organizer-card">
            <?= avatar_html($organizer ?? ['full_name' => 'ICS-SBO'], 56) ?>
            <div>
              <div class="oname"><?= h($organizer['full_name'] ?? 'ICS-SBO') ?></div>
              <div class="oposition"><?= h($organizer['position_title'] ?? 'Event Lead') ?></div>
              <div class="oemail"><?= icon('mail') ?><?= h($organizer['email'] ?? '') ?></div>
            </div>
          </div>
          <?php foreach ($eventOrganizers as $eo): ?>
            <?php if ((int)$eo['id'] === (int)$event['created_by']) continue; ?>
            <div class="organizer-card">
              <?= avatar_html($eo, 56) ?>
              <div>
                <div class="oname"><?= h($eo['full_name']) ?></div>
                <div class="oposition"><?= h($eo['committee'] ?: ($eo['position_title'] ?: 'Committee Member')) ?></div>
                <div class="oemail"><?= icon('mail') ?><?= h($eo['email']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="section-title">Photos of the Event<?= count($eventPhotos) ? ' (' . count($eventPhotos) . ')' : '' ?></div>
        <?php if ($canManagePhotos): ?>
          <form method="post" action="actions.php" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;margin-bottom:12px;">
            <input type="hidden" name="action" value="upload_event_photo">
            <input type="hidden" name="event_id" value="<?= (int)$event_id ?>">
            <input type="file" name="photo" accept="image/*" required style="flex:1;">
            <button type="submit" class="btn btn-sm btn-primary">Upload photo</button>
          </form>
        <?php endif; ?>
        <?php if (empty($eventPhotos)): ?>
          <div class="feedback-empty" style="border:1px dashed var(--line);border-radius:10px;background:var(--paper-2);">
            <?= $canManagePhotos ? 'No photos yet — upload the first one above.' : 'No photos have been posted for this event yet.' ?>
          </div>
        <?php else: ?>
          <div class="photo-grid">
            <?php foreach ($eventPhotos as $ph): ?>
              <div class="ph">
                <img src="<?= h($ph['file_path']) ?>" alt="Event photo" loading="lazy">
                <?php if ($canManagePhotos): ?>
                  <div class="photo-actions">
                    <?php if (!empty($ph['is_cover'])): ?>
                      <span class="cover-photo-badge">Event profile photo</span>
                    <?php else: ?>
                      <form method="post" action="actions.php">
                        <input type="hidden" name="action" value="set_event_cover">
                        <input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>">
                        <input type="hidden" name="event_id" value="<?= (int)$event_id ?>">
                        <button type="submit" class="photo-action-btn">Set as profile photo</button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="actions.php" onsubmit="return confirm('Delete this photo?')">
                      <input type="hidden" name="action" value="delete_event_photo">
                      <input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>">
                      <input type="hidden" name="event_id" value="<?= (int)$event_id ?>">
                      <button type="submit" class="del-photo">Delete</button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
    </div>

    <div class="overlay" id="feedbackOverlay">
      <div class="modal" style="max-width:520px;">
        <h3>Feedback (<?= count($feedback) ?>)</h3>
        <p class="msub">See what others thought, and share your own.</p>
        <div class="feedback-thread" style="max-height:320px;overflow-y:auto;margin-bottom:14px;">
          <?php if (empty($feedback)): ?>
            <div class="feedback-empty">No feedback yet — be the first to share a thought about this event.</div>
          <?php else: foreach ($feedback as $fb):
            $mine = (int)$fb['user_id'] === (int)$me['id'];
            $roleLabel = $fb['role'] === 'officer' ? 'Officer' : ($fb['role'] === 'admin' ? 'Adviser' : 'Student');
            ?>
            <div class="bubble <?= $mine ? 'mine' : '' ?>">
              <div class="bubble-head">
                <span class="bubble-name"><?= h($fb['full_name']) ?></span>
                <span class="bubble-role <?= h($fb['role']) ?>"><?= $roleLabel ?></span>
                <span class="bubble-time"><?= fmt_datetime($fb['created_at']) ?></span>
              </div>
              <?php if ($fb['rating']): ?><div style="margin-bottom:3px;"><?= star_html($fb['rating'], 12) ?></div><?php endif; ?>
              <div class="bubble-msg"><?= nl2br(h($fb['message'])) ?></div>
              <?php if ($mine): ?>
                <form method="post" action="actions.php" onsubmit="return confirm('Remove this feedback?')">
                  <input type="hidden" name="action" value="delete_feedback">
                  <input type="hidden" name="feedback_id" value="<?= (int)$fb['id'] ?>">
                  <input type="hidden" name="event_id" value="<?= (int)$event_id ?>">
                  <button type="submit" class="bubble-del">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <form method="post" action="actions.php" style="display:flex;flex-direction:column;">
          <input type="hidden" name="action" value="post_feedback">
          <input type="hidden" name="event_id" value="<?= (int)$event_id ?>">
          <?php if ($role === 'student'): ?>
            <label style="font-size:12px;font-weight:600;color:var(--ink);margin-bottom:4px;"><?= $myRating ? 'Update your rating' : 'Rate this event (optional)' ?></label>
            <div class="star-input" style="margin-bottom:10px;">
              <input type="radio" name="rating" value="5" id="r5" <?= $myRating===5?'checked':'' ?>><label for="r5">★</label>
              <input type="radio" name="rating" value="4" id="r4" <?= $myRating===4?'checked':'' ?>><label for="r4">★</label>
              <input type="radio" name="rating" value="3" id="r3" <?= $myRating===3?'checked':'' ?>><label for="r3">★</label>
              <input type="radio" name="rating" value="2" id="r2" <?= $myRating===2?'checked':'' ?>><label for="r2">★</label>
              <input type="radio" name="rating" value="1" id="r1" <?= $myRating===1?'checked':'' ?>><label for="r1">★</label>
            </div>
          <?php endif; ?>
          <textarea name="message" placeholder="Share a comment, question, or feedback about this event…" maxlength="500" required style="margin-bottom:10px;"></textarea>
          <div class="modal-actions">
            <button type="button" class="btn" onclick="document.getElementById('feedbackOverlay').classList.remove('show')">Close</button>
            <button type="submit" class="btn btn-primary">Post</button>
          </div>
        </form>
      </div>
    </div>

    <button type="button" class="floating-rate-btn" style="right:20px !important;left:auto !important;" onclick="document.getElementById('feedbackOverlay').classList.add('show')">
      <?= icon('star', 'meta-icon') ?>
      <?php if ($role === 'student'): ?>
        <?= $myRating ? 'Edit your rating' : 'Rate this event' ?>
        <?php if ($myRating): ?><span class="rate-count"><?= $myRating ?></span><?php endif; ?>
      <?php else: ?>
        Feedback<?php if (count($feedback) > 0): ?><span class="rate-count"><?= count($feedback) ?></span><?php endif; ?>
      <?php endif; ?>
    </button>

    <!-- Keep the floating rating/feedback controls in the document body so they
         are always viewport-fixed and cannot be clipped by cards or .main. -->
    <script>
      (function () {
        var floatingRate = document.querySelector('.floating-rate-btn');
        var feedbackOverlay = document.getElementById('feedbackOverlay');

        if (floatingRate && floatingRate.parentNode !== document.body) {
          document.body.appendChild(floatingRate);
        }
        if (feedbackOverlay && feedbackOverlay.parentNode !== document.body) {
          document.body.appendChild(feedbackOverlay);
        }
      })();
    </script>

    <?php if ($event['latitude'] !== null && $event['longitude'] !== null): ?>
    <script>
      (function(){
        var el = document.getElementById('eventMap');
        if (!el) return;
        if (typeof L === 'undefined') {
          el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--slate);font-size:12.5px;text-align:center;padding:0 16px;">Map could not load — check your internet connection and refresh.</div>';
          return;
        }
        var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
        var map = L.map('eventMap', { scrollWheelZoom: false }).setView([lat, lng], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors',
          maxZoom: 19
        }).addTo(map);
        L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.venue);
      })();
    </script>
    <?php endif; ?>
    <?php
}

// ================== STUDENT: BROWSE ==================
elseif ($view === 'browse') {
    $searchQuery = trim($_GET['q'] ?? '');
    $approved = get_events($conn, 'approved', null, $searchQuery !== '' ? $searchQuery : null);
    ?>
    <div class="browse-events-header">
      <div class="browse-events-search-top">
        <div class="search-center-wrap">
      <form method="get" action="index.php" class="event-search-form">
        <input type="hidden" name="view" value="browse">
        <div class="event-search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.3-4.3"/></svg>
          <input type="search" name="q" aria-label="Search events" placeholder="Search for an event…" value="<?= h($searchQuery) ?>" autocomplete="off">
          <?php if ($searchQuery !== ''): ?>
          <a class="event-search-clear" href="index.php?view=browse" aria-label="Clear search">&times;</a>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary event-search-button">Search</button>
      </form>
        </div>
      </div>
    </div>

    <div class="hero-cards">
    <?php if (empty($approved)): ?>
      <div class="empty" style="grid-column:1/-1;"><div class="big">No matching events</div>Try a different search.</div>
    <?php else: foreach ($approved as $ev): hero_card($conn, $ev); endforeach; endif; ?>
    </div>
    <?php
}

// ================== STUDENT: EVENTS (UPCOMING / PAST) ==================
elseif ($view === 'myRegs') {
    $period = strtolower(trim($_GET['period'] ?? 'upcoming'));
    if (!in_array($period, ['upcoming', 'past'], true)) {
        $period = 'upcoming';
    }
    $stmt = $conn->prepare("SELECT r.*, e.title, e.event_date, e.event_time, e.venue, e.category, e.description, e.capacity, e.status FROM registrations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? ORDER BY e.event_date " . ($period === 'past' ? 'DESC' : 'ASC'));
    $stmt->bind_param('i', $me['id']);
    $stmt->execute();
    $allRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $rows = array_values(array_filter($allRows, function ($row) use ($period) {
        $past = event_is_past($row);
        return $period === 'past' ? $past : !$past;
    }));
    ?>
    <div class="topbar">
      <div><h2>Events</h2><p class="sub">Your upcoming events and the activities you've attended.</p></div>
    </div>
    <div class="event-period-tabs" role="tablist" aria-label="Event period">
      <a class="event-period-tab <?= $period === 'upcoming' ? 'active' : '' ?>" href="index.php?view=myRegs&amp;period=upcoming" role="tab" aria-selected="<?= $period === 'upcoming' ? 'true' : 'false' ?>">Upcoming</a>
      <a class="event-period-tab <?= $period === 'past' ? 'active' : '' ?>" href="index.php?view=myRegs&amp;period=past" role="tab" aria-selected="<?= $period === 'past' ? 'true' : 'false' ?>">Past</a>
    </div>
    <div class="tickets">
    <?php if (empty($rows)): ?>
      <div class="empty"><div class="big">No <?= $period === 'past' ? 'past' : 'upcoming' ?> events yet</div>
        <?= $period === 'past' ? 'Events you have joined will appear here after they end.' : 'Events you join will appear here.' ?>
      </div>
    <?php else: foreach ($rows as $r):
      $eventId = (int)$r['event_id'];
      $regCount = count_registrations($conn, $eventId);
      $eventPhoto = get_event_hero_photo($conn, $eventId);
      $goingStudents = get_registrant_avatars($conn, $eventId, 5);
      $dateTs = strtotime($r['event_date']);
      $eventDay = $dateTs ? date('D', $dateTs) : '';
      $eventDateLabel = $dateTs ? date('M j', $dateTs) : fmt_event_date_range($r);
      $isPastEvent = event_is_past($r);
    ?>
      <article class="going-event-card">
        <div class="going-event-main">
          <div class="going-event-date"><strong><?= h($eventDateLabel) ?></strong><span><?= h($eventDay) ?></span></div>
          <div class="going-event-content">
            <div class="going-event-time"><?= h(fmt_event_time_range($r)) ?></div>
            <a class="going-event-title" href="index.php?view=eventDetail&amp;event_id=<?= $eventId ?>"><?= h($r['title']) ?></a>
            <div class="going-event-meta">
              <span><?= icon('pin') ?> <?= h($r['venue']) ?></span>
            </div>
            <div class="going-event-bottom">
              <?php if (!$isPastEvent): ?>
                <span class="going-status-pill">Going</span>
              <?php else: ?>
                <span class="going-status-pill going-status-past"><?= !empty($r['attended']) ? 'Attended' : 'Past event' ?></span>
              <?php endif; ?>
              <div class="going-people" aria-label="Students going to this event">
                <div class="going-avatar-stack">
                  <?php foreach ($goingStudents as $student): ?><?= avatar_html($student, 34) ?><?php endforeach; ?>
                </div>
                <?php if ($regCount > 0): ?><span class="going-people-count"><?= $regCount > count($goingStudents) ? '+' . ($regCount - count($goingStudents)) : $regCount ?><?= $regCount > count($goingStudents) ? '' : ' going' ?></span><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <a class="going-event-poster" href="index.php?view=eventDetail&amp;event_id=<?= $eventId ?>" aria-label="View <?= h($r['title']) ?>">
          <?php if ($eventPhoto): ?><img src="<?= h($eventPhoto) ?>" alt="<?= h($r['title']) ?> event poster" loading="lazy"><?php else: ?><span class="going-event-poster-placeholder"><?= category_icon($r['category']) ?><small><?= h($r['category']) ?></small></span><?php endif; ?>
        </a>
      </article>
    <?php endforeach; endif; ?>
    </div>
    <?php
}

// ================== OFFICER: MY EVENTS / ALL EVENTS ==================
elseif ($view === 'myEvents' || ($view === 'allEvents' && $role === 'officer')) {
    $mineOnly = $view === 'myEvents';
    $searchQuery = trim($_GET['q'] ?? '');
    $searchArg = $searchQuery !== '' ? $searchQuery : null;
    $list = $mineOnly ? get_events($conn, null, $me['id'], $searchArg) : get_events($conn, null, null, $searchArg);
    ?>
    <div class="topbar">
      <div><h2><?= $mineOnly ? 'My events' : 'All events' ?></h2><p class="sub"><?= $mineOnly ? 'Events you have submitted, at every approval stage.' : 'Every event across the ICS-SBO, regardless of who created it.' ?></p></div>
      <div class="topbar-actions">
        <?php if ($mineOnly): ?>
          <a class="btn btn-sm" href="accomplishment_report.php"><?= icon('clipboard', 'meta-icon') ?> All-events summary report</a>
        <?php endif; ?>
        <button class="btn btn-primary" id="openNewEventBtn" onclick="document.getElementById('newEventOverlay').classList.add('show')">+ New event</button>
      </div>
    </div>
    <div class="stats">
      <div class="stat"><div class="n"><?= (int)$totalEvents ?></div><div class="l">Total events</div></div>
      <div class="stat"><div class="n"><?= (int)$upcomingCount ?></div><div class="l">Upcoming, approved</div></div>
      <div class="stat"><div class="n"><?= (int)$pendingCount ?></div><div class="l">Pending approval</div></div>
      <div class="stat"><div class="n"><?= (int)$totalRegs ?></div><div class="l">Total Students Going</div></div>
    </div>
    <div class="browse-events-header">
      <div class="browse-events-search-top">
        <div class="search-center-wrap">
      <form method="get" action="index.php" class="event-search-form">
        <input type="hidden" name="view" value="<?= h($view) ?>">
        <div class="event-search-box">
          <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="10.5" cy="10.5" r="6.5"/><path d="M20 20l-4.3-4.3"/></svg>
          <input type="search" name="q" aria-label="Search events" placeholder="<?= $mineOnly ? 'Search my events…' : 'Search all events…' ?>" value="<?= h($searchQuery) ?>" autocomplete="off">
          <?php if ($searchQuery !== ''): ?>
          <a class="event-search-clear" href="index.php?view=<?= h($view) ?>" aria-label="Clear search">&times;</a>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary event-search-button">Search</button>
      </form>
        </div>
      </div>
    </div>

    <div class="tickets">
    <?php if (empty($list)): ?>
      <?php if ($searchQuery !== ''): ?>
        <div class="empty"><div class="big">No matching events</div>Try a different search.</div>
      <?php else: ?>
        <div class="empty"><div class="big">No events to show</div>Create one to get started.</div>
      <?php endif; ?>
    <?php else: foreach ($list as $ev):
      $regCount = count_registrations($conn, $ev['id']);
      $actions = '<a class="btn btn-sm" href="index.php?view=roster&event_id=' . $ev['id'] . '">View joined students (' . $regCount . ')</a>';
      if ((int)$ev['created_by'] === (int)$me['id']) {
        $actions .= ' <a class="btn btn-sm" href="index.php?view=editEvent&event_id=' . $ev['id'] . '">Edit</a>';
      }
      if ((int)$ev['created_by'] === (int)$me['id']) {
        $actions .= ' <form method="post" action="actions.php" style="display:inline;" onsubmit="return confirm(\'Delete this event permanently? This will also remove its registrations, feedback, and event photos. Students who joined an upcoming approved event will be emailed that it was cancelled.\')"><input type="hidden" name="action" value="delete_event"><input type="hidden" name="event_id" value="' . (int)$ev['id'] . '"><button class="btn btn-sm btn-danger">Delete</button></form>';
      }
      if ($ev['status'] === 'approved' && (int)$ev['created_by'] === (int)$me['id']) {
        $actions .= ' <a class="btn btn-sm btn-gold" href="index.php?view=attendance&event_id=' . $ev['id'] . '">Attendance list</a>';
        $actions .= ' <a class="btn btn-sm" href="accomplishment_report.php?event_id=' . $ev['id'] . '">Accomplishment report</a>';
      }
      ticket_card($ev, $regCount, $actions);
    endforeach; endif; ?>
    </div>

    <div class="overlay" id="newEventOverlay">
      <div class="modal">
        <h3>New event</h3>
        <p class="msub">Submitted events go to the admin for approval before students can join.</p>
        <form method="post" action="actions.php">
          <input type="hidden" name="action" value="create_event">
          <input type="hidden" name="latitude" id="f-lat">
          <input type="hidden" name="longitude" id="f-lng">
          <div class="field"><label>Event title</label><input type="text" name="title" placeholder="ICS Sportsfest 2026" required></div>
          <div class="field"><label>Description</label><textarea name="description" placeholder="What is this event about, and who should join?"></textarea></div>
          <div class="row2">
            <div class="field"><label>From</label><input type="date" name="event_date" required></div>
            <div class="field"><label>Time from</label><input type="time" name="event_time"></div>
          </div>
          <div class="row2">
            <div class="field"><label>To</label><input type="date" name="event_end_date" required></div>
            <div class="field"><label>Time to</label><input type="time" name="event_end_time"></div>
          </div>
          <p class="hint">Duration is calculated automatically from the From and To dates.</p>
          <div class="row2">
            <div class="field"><label>Venue</label><input type="text" name="venue" placeholder="ICS Covered Court" required></div>
            <div class="field"><label>Category</label>
              <select name="category">
                <option>Academic</option><option>Sports</option><option>Cultural</option><option>Outreach</option><option>Organizational</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Pin the location on the map (optional)</label>
            <div id="pickerMap" class="picker-map"></div>
            <p class="hint">Click the map to drop a pin at the venue's real location.</p>
          </div>
          <div class="field">
            <label>Check-in range: <strong id="radiusLabel"><?= (int) 100 ?></strong> meters</label>
            <input type="range" name="geofence_radius" id="geoRadius" min="20" max="500" step="10" value="<?= (int) 100 ?>">
            <p class="hint">Students can only check in within this distance of the pin. A smaller range is stricter, so use about 30 to 50 m for one room or building and 100 m or more for an open field. The circle on the map shows the range.</p>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" onclick="document.getElementById('newEventOverlay').classList.remove('show')">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit for approval</button>
          </div>
        </form>
      </div>
    </div>
    <script>
      (function(){
        var pickerMap = null, pickerMarker = null, pickerCircle = null;
        function drawRange(){
          var slider = document.getElementById('geoRadius');
          var label = document.getElementById('radiusLabel');
          if (slider && label) { label.textContent = slider.value; }
          if (!pickerMap || !pickerMarker || !slider) return;
          if (pickerCircle) { pickerCircle.setLatLng(pickerMarker.getLatLng()); pickerCircle.setRadius(parseInt(slider.value, 10)); }
          else { pickerCircle = L.circle(pickerMarker.getLatLng(), { radius: parseInt(slider.value, 10), color: '#0b6b3a', fillOpacity: 0.15 }).addTo(pickerMap); }
        }
        (function(){ var sl = document.getElementById('geoRadius'); if (sl) sl.addEventListener('input', drawRange); })();
        // NBSC campus, Manolo Fortich, Bukidnon — used only as the map's
        // starting view. Click anywhere to drop the real pin.
        var DEFAULT_LAT = 8.3667, DEFAULT_LNG = 124.8667;

        function initPickerMap(){
          if (pickerMap) { setTimeout(function(){ pickerMap.invalidateSize(); }, 50); return; }
          if (typeof L === 'undefined') {
            var el = document.getElementById('pickerMap');
            if (el) el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--slate);font-size:12.5px;text-align:center;padding:0 16px;">Map could not load — check your internet connection. You can still submit the event without a pin.</div>';
            return;
          }
          pickerMap = L.map('pickerMap').setView([DEFAULT_LAT, DEFAULT_LNG], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
          }).addTo(pickerMap);
          pickerMap.on('click', function(e){
            document.getElementById('f-lat').value = e.latlng.lat.toFixed(7);
            document.getElementById('f-lng').value = e.latlng.lng.toFixed(7);
            if (pickerMarker) { pickerMarker.setLatLng(e.latlng); }
            else { pickerMarker = L.marker(e.latlng).addTo(pickerMap); }
            drawRange();
          });
          setTimeout(function(){ pickerMap.invalidateSize(); }, 100);
        }

        var openBtn = document.getElementById('openNewEventBtn');
        if (openBtn) {
          openBtn.addEventListener('click', function(){ setTimeout(initPickerMap, 60); });
        }
      })();
    </script>
    <?php
}

// ================== ADMIN: APPROVAL QUEUE ==================
elseif ($view === 'queue') {
    $pending = get_events($conn, 'pending');
    ?>
    <div class="topbar"><div><h2>Approval queue</h2><p class="sub">Events submitted by SBO officers, waiting on your review.</p></div></div>
    <div class="stats">
      <div class="stat"><div class="n"><?= (int)$totalEvents ?></div><div class="l">Total events</div></div>
      <div class="stat"><div class="n"><?= (int)$upcomingCount ?></div><div class="l">Upcoming, approved</div></div>
      <div class="stat"><div class="n"><?= (int)$pendingCount ?></div><div class="l">Pending approval</div></div>
      <div class="stat"><div class="n"><?= (int)$totalRegs ?></div><div class="l">Total Students Going</div></div>
    </div>
    <div class="tickets">
    <?php if (empty($pending)): ?>
      <div class="empty"><div class="big">Queue is clear</div>Nothing waiting on your approval right now.</div>
    <?php else: foreach ($pending as $ev):
      $regCount = count_registrations($conn, $ev['id']);
      $actions = '<form method="post" action="actions.php" style="display:inline;"><input type="hidden" name="action" value="decide_event"><input type="hidden" name="event_id" value="' . (int)$ev['id'] . '"><input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-approve">Approve</button></form>';
      $actions .= ' <form method="post" action="actions.php" style="display:inline;"><input type="hidden" name="action" value="decide_event"><input type="hidden" name="event_id" value="' . (int)$ev['id'] . '"><input type="hidden" name="decision" value="rejected"><button class="btn btn-sm btn-danger">Reject</button></form>';
      $actions .= ' <a class="btn btn-sm" href="index.php?view=roster&event_id=' . $ev['id'] . '">Details</a>';
      ticket_card($ev, $regCount, $actions);
    endforeach; endif; ?>
    </div>
    <?php
}

// ================== ADMIN: ALL EVENTS ==================
elseif ($view === 'allEvents' && $role === 'admin') {
    $list = get_events($conn);
    ?>
    <div class="topbar"><div><h2>All events</h2><p class="sub">Full record across every status.</p></div></div>
    <div class="stats">
      <div class="stat"><div class="n"><?= (int)$totalEvents ?></div><div class="l">Total events</div></div>
      <div class="stat"><div class="n"><?= (int)$upcomingCount ?></div><div class="l">Upcoming, approved</div></div>
      <div class="stat"><div class="n"><?= (int)$pendingCount ?></div><div class="l">Pending approval</div></div>
      <div class="stat"><div class="n"><?= (int)$totalRegs ?></div><div class="l">Total Students Going</div></div>
    </div>
    <div class="tickets">
    <?php if (empty($list)): ?>
      <div class="empty"><div class="big">No events recorded</div></div>
    <?php else: foreach ($list as $ev):
      $regCount = count_registrations($conn, $ev['id']);
      $actions = '<a class="btn btn-sm" href="index.php?view=roster&event_id=' . $ev['id'] . '">View joined students (' . $regCount . ')</a>';
      if ($ev['status'] === 'pending') {
        $actions .= ' <form method="post" action="actions.php" style="display:inline;"><input type="hidden" name="action" value="decide_event"><input type="hidden" name="event_id" value="' . (int)$ev['id'] . '"><input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-approve">Approve</button></form>';
      }
      ticket_card($ev, $regCount, $actions);
    endforeach; endif; ?>
    </div>
    <?php
}

// ================== ADMIN: STUDENTS & OFFICERS ==================
elseif ($view === 'manageStudents') {
    require_role('admin');

    $students = [];

    $stmt = $conn->prepare("
        SELECT
            id,
            role,
            full_name,
            email,
            student_id,
            year_level,
            course,
            position_title
        FROM users
        WHERE role IN ('student', 'officer')
        ORDER BY
            CASE
                WHEN role = 'officer' THEN 1
                ELSE 2
            END,
            full_name ASC
    ");

    if ($stmt) {
        $stmt->execute();
        $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Optional filter from the dashboard cards: ?role=student or ?role=officer
    $allAccounts = $students;
    $roleFilter = in_array($_GET['role'] ?? '', ['student', 'officer'], true) ? $_GET['role'] : '';
    $studentTotal = count(array_filter($allAccounts, fn($a) => $a['role'] === 'student'));
    $officerTotal = count(array_filter($allAccounts, fn($a) => $a['role'] === 'officer'));
    if ($roleFilter !== '') {
        $students = array_values(array_filter($allAccounts, fn($a) => $a['role'] === $roleFilter));
    }

    $advisers = [];
    $advRes = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'admin' ORDER BY full_name ASC");
    if ($advRes) {
        $advisers = $advRes->fetch_all(MYSQLI_ASSOC);
    }

    // A temporary password is shown once, right after the Adviser resets one.
    $tempNotice = $_SESSION['temp_password_notice'] ?? null;
    unset($_SESSION['temp_password_notice']);

    $lastLogins = activity_last_logins($conn);
    ?>

    <div class="topbar">
        <div>
            <h2>Students &amp; Officers</h2>
            <p class="sub">
                Manage student accounts and assign or remove SBO Officer access.
            
            </p>
        </div>
    </div>

    <?php if (!empty($tempNotice)): ?>
    <div class="detail-card" style="max-width:1100px;margin-bottom:18px;border:2px solid #1F8B4C;">
        <strong style="font-size:17px;">Temporary password for <?= h($tempNotice['name']) ?></strong>
        <div class="sub" style="margin:4px 0 12px;"><?= h($tempNotice['email']) ?></div>
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
            <code id="tempPassword" style="font-size:22px;letter-spacing:2px;padding:8px 14px;background:#EAF7EF;border-radius:8px;"><?= h($tempNotice['password']) ?></code>
            <button type="button" class="btn btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('tempPassword').textContent); this.textContent='Copied';">Copy</button>
        </div>
        <p class="sub" style="margin-top:12px;">
            Give this to the student in person or privately. It is shown only once, so write it down before leaving this page.
            The student will be asked to choose a new password when they sign in.
        </p>
    </div>
    <?php endif; ?>

    <div class="detail-card" style="max-width:1100px;margin-bottom:18px;">

        <strong style="font-size:17px;">Adviser access</strong>
        <div class="sub" style="margin:4px 0 14px;">
            Hand the system over to a new Adviser without using the database.
            At least one Adviser always remains.
        </div>

        <div style="margin-bottom:16px;">
            <div class="sub" style="margin-bottom:6px;">Current Advisers</div>
            <?php foreach ($advisers as $adv): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);">
                    <span>
                        <strong><?= h($adv['full_name']) ?></strong>
                        <span class="sub"> <?= h($adv['email']) ?></span>
                        <?php if ((int)$adv['id'] === (int)$me['id']): ?>
                            <span class="badge badge-approved">You</span>
                        <?php endif; ?>
                    </span>
                    <?php if ((int)$adv['id'] !== (int)$me['id'] && count($advisers) > 1): ?>
                        <form method="post" action="actions.php" style="display:inline;"
                              onsubmit="return confirm('Remove Adviser access from this account and return it to Student?');">
                            <input type="hidden" name="action" value="remove_adviser">
                            <input type="hidden" name="user_id" value="<?= (int)$adv['id'] ?>">
                            <button type="submit" class="btn btn-sm">Remove Adviser access</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($allAccounts)): ?>
        <form method="post" action="actions.php" id="adviserForm">
            <input type="hidden" name="action" value="grant_adviser" id="adviserAction">

            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
                <div style="flex:2;min-width:220px;">
                    <label class="sub" for="adviserTarget" style="display:block;margin-bottom:4px;">New Adviser</label>
                    <select name="user_id" id="adviserTarget" required
                            style="width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card,#fff);color:var(--text,#222);">
                        <option value="">Choose a student or officer</option>
                        <?php foreach ($allAccounts as $st): ?>
                            <option value="<?= (int)$st['id'] ?>"><?= h($st['full_name']) ?> (<?= h($st['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="flex:1;min-width:180px;">
                    <label class="sub" for="adviserPassword" style="display:block;margin-bottom:4px;">Your password (for transfer)</label>
                    <input type="password" name="current_password" id="adviserPassword" autocomplete="current-password"
                           style="width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid var(--line);border-radius:10px;background:var(--card,#fff);color:var(--text,#222);">
                </div>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
                <button type="button" class="btn btn-primary btn-sm" id="btnGrantAdviser">Add as co-Adviser</button>
                <button type="button" class="btn btn-sm" id="btnTransferAdviser">Transfer my Adviser access</button>
            </div>
            <div class="sub" style="margin-top:8px;">
                Add as co-Adviser: the person becomes an Adviser and you keep your access.
                Transfer: the person becomes the Adviser and your account becomes a Student account.
            </div>
        </form>

        <script>
        (function () {
            var form = document.getElementById('adviserForm');
            var action = document.getElementById('adviserAction');
            var target = document.getElementById('adviserTarget');
            var pass = document.getElementById('adviserPassword');

            function chosenName() {
                return target.options[target.selectedIndex].text;
            }

            document.getElementById('btnGrantAdviser').addEventListener('click', function () {
                if (!target.value) { target.focus(); return; }
                if (!confirm('Give Adviser access to ' + chosenName() + '? You will keep your own access.')) return;
                action.value = 'grant_adviser';
                form.submit();
            });

            document.getElementById('btnTransferAdviser').addEventListener('click', function () {
                if (!target.value) { target.focus(); return; }
                if (!pass.value) { pass.focus(); alert('Enter your password to transfer Adviser access.'); return; }
                if (!confirm('Transfer Adviser access to ' + chosenName() + '? Your account will become a Student account.')) return;
                action.value = 'transfer_adviser';
                form.submit();
            });
        })();
        </script>
        <?php endif; ?>

    </div>

    <div class="detail-card" style="max-width:1100px;">

        <!-- SHOW: everyone, only students, or only officers -->
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
            <a class="btn btn-sm <?= $roleFilter === '' ? 'btn-primary' : '' ?>" href="index.php?view=manageStudents">All accounts (<?= count($allAccounts) ?>)</a>
            <a class="btn btn-sm <?= $roleFilter === 'student' ? 'btn-primary' : '' ?>" href="index.php?view=manageStudents&role=student">Students (<?= $studentTotal ?>)</a>
            <a class="btn btn-sm <?= $roleFilter === 'officer' ? 'btn-primary' : '' ?>" href="index.php?view=manageStudents&role=officer">SBO Officers (<?= $officerTotal ?>)</a>
        </div>

        <!-- SEARCH -->
        <div style="
            margin-bottom:20px;
            padding:16px;
            border:1px solid var(--line);
            border-radius:14px;
            background:rgba(255,255,255,.04);
        ">

            <div style="
                display:flex;
                align-items:center;
                gap:9px;
                margin-bottom:9px;
            ">
                <span style="font-size:18px;"></span>

                <strong style="font-size:16px;">
                    Search Students &amp; Officers
                </strong>
            </div>

            <div style="position:relative;">

                <input
                    type="search"
                    id="userSearch"
                    placeholder="Search by name, email, ID number, course, year level, or position..."
                    autocomplete="off"
                    aria-label="Search students and officers"
                    style="
                        width:100%;
                        box-sizing:border-box;
                        padding:13px 16px;
                        border:1px solid var(--line);
                        border-radius:10px;
                        background:var(--card,#fff);
                        color:var(--text,#222);
                        font-size:14px;
                        outline:none;
                    "
                >

            </div>

            <div
                id="searchResultCount"
                class="sub"
                style="margin-top:8px;"
            >
                Showing <?= count($students) ?>
                account<?= count($students) === 1 ? '' : 's' ?>
            </div>

        </div>


        <!-- ACCOUNT LIST -->

        <div style="margin-bottom:16px;">
            <strong style="font-size:17px;">
                Student accounts
            </strong>

            <div class="sub" style="margin-top:4px;">
                
            </div>
        </div>


        <?php if (empty($students)): ?>

            <div class="empty">
                <div class="big">
                    No student accounts
                </div>

                <p>
                    Students who join with an official NBSC Google account
                    will appear here.
                </p>
            </div>

        <?php else: ?>

            <div style="overflow-x:auto;">

                <table
                    id="usersTable"
                    style="
                        width:100%;
                        border-collapse:collapse;
                        min-width:760px;
                    "
                >

                    <thead>

                        <tr
                            style="
                                text-align:left;
                                border-bottom:1px solid var(--line);
                            "
                        >

                            <th style="padding:11px 10px;">
                                Student / Officer
                            </th>

                            <th style="padding:11px 10px;">
                                ID / Course
                            </th>

                            <th style="padding:11px 10px;">
                                Role
                            </th>

                            <th
                                style="
                                    padding:11px 10px;
                                    text-align:right;
                                "
                            >
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($students as $student): ?>

                        <?php
                        /*
                         * Everything below is searchable:
                         * - Full name
                         * - Email
                         * - Student ID
                         * - Year level
                         * - Course
                         * - Officer position
                         * - Role
                         */

                        $searchText = strtolower(
                            trim(
                                ($student['full_name'] ?? '') . ' ' .
                                ($student['email'] ?? '') . ' ' .
                                ($student['student_id'] ?? '') . ' ' .
                                ($student['year_level'] ?? '') . ' ' .
                                ($student['course'] ?? '') . ' ' .
                                ($student['position_title'] ?? '') . ' ' .
                                ($student['role'] ?? '')
                            )
                        );
                        ?>

                        <tr
                            class="user-row"
                            data-search="<?= h($searchText) ?>"
                            style="
                                border-bottom:1px solid var(--line);
                            "
                        >

                            <!-- NAME / EMAIL -->

                            <td style="padding:13px 10px;">

                                <strong>
                                    <?= h($student['full_name']) ?>
                                </strong>

                                <br>

                                <span class="sub">
                                    <?= h($student['email']) ?>
                                </span>

                            </td>


                            <!-- ID / COURSE -->

                            <td style="padding:13px 10px;">

                                <span class="mono">
                                    <?= h(
                                        $student['student_id']
                                        ?: '—'
                                    ) ?>
                                </span>

                                <br>

                                <span class="sub">

                                    <?= h(
                                        $student['course']
                                        ?: '—'
                                    ) ?>

                                    ·

                                    <?= h(
                                        $student['year_level']
                                        ?: '—'
                                    ) ?>

                                    <?php if (
                                        $student['role'] === 'officer'
                                        && !empty($student['position_title'])
                                    ): ?>

                                        <br>

                                        <span
                                            style="
                                                font-size:12px;
                                                font-weight:600;
                                            "
                                        >
                                            <?= h(
                                                $student['position_title']
                                            ) ?>
                                        </span>

                                    <?php endif; ?>

                                </span>

                            
                                <?php if (!empty($lastLogins[(int)$student['id']])): ?>
                                    <br><span class="sub" style="font-size:11.5px;">Last login: <?= h(date('M j, g:i A', strtotime($lastLogins[(int)$student['id']]))) ?></span>
                                <?php else: ?>
                                    <br><span class="sub" style="font-size:11.5px;">Never logged in</span>
                                <?php endif; ?>

                            </td>


                            <!-- ROLE -->

                            <td style="padding:13px 10px;">

                                <span
                                    class="badge
                                    <?= $student['role'] === 'officer'
                                        ? 'badge-approved'
                                        : 'badge-pending' ?>"
                                >

                                    <?= $student['role'] === 'officer'
                                        ? 'SBO Officer'
                                        : 'Student' ?>

                                </span>

                            </td>


                            <!-- ACTION -->

                            <td style="padding:13px 10px;text-align:right;">
                                <?php
                                $positions = sbo_officer_positions();
                                $curPos = (string) ($student['position_title'] ?? '');
                                $curInList = in_array($curPos, $positions, true);
                                $showCustom = $student['role'] === 'officer' && $curPos !== '' && !$curInList;
                                $posChange = "var c=this.form.querySelector('.pos-custom');var o=this.value==='__other';c.style.display=o?'inline-block':'none';c.required=o;";
                                $selStyle = 'padding:7px 8px;border:1px solid var(--line);border-radius:8px;background:var(--card,#fff);color:var(--text,#222);font-size:13px;max-width:190px;';
                                ?>

                                <?php if ($student['role'] === 'officer'): ?>
                                    <form method="post" action="actions.php" style="display:inline-flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;align-items:center;margin-bottom:6px;">
                                        <input type="hidden" name="action" value="set_officer_position">
                                        <input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                                        <select name="position" required onchange="<?= h($posChange) ?>" style="<?= $selStyle ?>">
                                            <?php foreach ($positions as $p): ?>
                                                <option value="<?= h($p) ?>" <?= $curPos === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                                            <?php endforeach; ?>
                                            <option value="__other" <?= $showCustom ? 'selected' : '' ?>>Other (type the position)</option>
                                        </select>
                                        <input type="text" name="position_custom" class="pos-custom" maxlength="100" placeholder="Type position" value="<?= $showCustom ? h($curPos) : '' ?>" <?= $showCustom ? 'required' : '' ?> style="<?= $selStyle ?>display:<?= $showCustom ? 'inline-block' : 'none' ?>;">
                                        <button type="submit" class="btn btn-sm">Save position</button>
                                    </form>
                                    <br>
                                    <form method="post" action="actions.php" style="display:inline;" onsubmit="return confirm('Remove SBO Officer access and return this account to Student?');">
                                        <input type="hidden" name="action" value="change_user_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                                        <input type="hidden" name="new_role" value="student">
                                        <button type="submit" class="btn btn-sm">Return to Student</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="actions.php" style="display:inline-flex;flex-wrap:wrap;gap:6px;justify-content:flex-end;align-items:center;" onsubmit="return confirm('Assign this student as an SBO Officer with the chosen position?');">
                                        <input type="hidden" name="action" value="change_user_role">
                                        <input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                                        <input type="hidden" name="new_role" value="officer">
                                        <select name="position" required onchange="<?= h($posChange) ?>" style="<?= $selStyle ?>">
                                            <option value="">Choose position</option>
                                            <?php foreach ($positions as $p): ?>
                                                <option value="<?= h($p) ?>"><?= h($p) ?></option>
                                            <?php endforeach; ?>
                                            <option value="__other">Other (type the position)</option>
                                        </select>
                                        <input type="text" name="position_custom" class="pos-custom" maxlength="100" placeholder="Type position" style="<?= $selStyle ?>display:none;">
                                        <button type="submit" class="btn btn-sm btn-primary">Assign SBO Officer</button>
                                    </form>
                                <?php endif; ?>
                                <br>
                                <form method="post" action="actions.php" style="display:inline;" onsubmit="return confirm('Reset this account password? A temporary password will be created and the old password will stop working.');">
                                    <input type="hidden" name="action" value="reset_user_password">
                                    <input type="hidden" name="user_id" value="<?= (int)$student['id'] ?>">
                                    <button type="submit" class="btn btn-sm" style="margin-top:6px;">Reset password</button>
                                </form>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <!-- NO SEARCH RESULTS -->

            <div
                id="noSearchResults"
                class="empty"
                style="display:none;margin-top:20px;"
            >

                <div class="big">
                    No matching accounts
                </div>

                <p>
                    Try searching by name, email, ID number,
                    course, year level, or officer position.
                </p>

            </div>

        <?php endif; ?>

    </div>


    <!-- LIVE SEARCH JAVASCRIPT -->

    <script>
    (function () {

        const searchInput =
            document.getElementById('userSearch');

        const rows =
            document.querySelectorAll('.user-row');

        const resultCount =
            document.getElementById('searchResultCount');

        const noResults =
            document.getElementById('noSearchResults');


        if (!searchInput) {
            return;
        }


        function searchUsers() {

            const query =
                searchInput.value
                    .toLowerCase()
                    .trim();

            let visible = 0;


            rows.forEach(function (row) {

                const searchableText =
                    (
                        row.getAttribute('data-search')
                        || ''
                    ).toLowerCase();

                const matches =
                    query === ''
                    || searchableText.includes(query);


                row.style.display =
                    matches ? '' : 'none';


                if (matches) {
                    visible++;
                }

            });


            /*
             * Update result count
             */

            if (resultCount) {

                if (query === '') {

                    resultCount.textContent =
                        'Showing ' +
                        visible +
                        ' account' +
                        (visible === 1 ? '' : 's');

                } else {

                    resultCount.textContent =
                        'Found ' +
                        visible +
                        ' matching account' +
                        (visible === 1 ? '' : 's');

                }

            }


            /*
             * Show "No matching accounts"
             */

            if (noResults) {

                noResults.style.display =
                    visible === 0
                    ? 'block'
                    : 'none';

            }

        }


        /*
         * Search while typing
         */

        searchInput.addEventListener(
            'input',
            searchUsers
        );


        /*
         * ESC clears search
         */

        searchInput.addEventListener(
            'keydown',
            function (event) {

                if (event.key === 'Escape') {

                    searchInput.value = '';

                    searchUsers();

                    searchInput.blur();

                }

            }
        );

    })();
    </script>

    <?php
}

// ================== PROFILE ==================
elseif ($view === 'profile') {
    $roleLabel = $role === 'student' ? 'Student' : ($role === 'officer' ? ($myPositionTitle !== '' ? $myPositionTitle . ' · SBO Officer' : 'SBO Officer') : 'Adviser · Admin');
    $subLabel = $role === 'student' && $profileUser['year_level'] ? 'ICS · ' . $profileUser['year_level'] : $roleLabel;
    ?>
    <div class="topbar"><div><h2>Profile &amp; Account</h2><p class="sub">Manage your profile, account security, and preferences.</p></div></div>

    <div class="settings-header">
      <?= avatar_html($profileUser, 60) ?>
      <div>
        <div class="name"><?= h($profileUser['full_name']) ?></div>
        <div class="sub"><?= h($subLabel) ?></div>
      </div>
    </div>

    <div class="settings-group-title">Account &amp; Security</div>
    <div class="settings-menu">
      <a class="settings-row" href="index.php?view=personalInfo">
        <span class="settings-icon"><?= icon('user', 'meta-icon') ?></span>
        <span class="settings-label">Personal Information</span>
        <?= icon('chevron-right', 'settings-chevron') ?>
      </a>
      <a class="settings-row" href="index.php?view=changePassword">
        <span class="settings-icon"><?= icon('lock', 'meta-icon') ?></span>
        <span class="settings-label">Change Password</span>
        <?= icon('chevron-right', 'settings-chevron') ?>
      </a>
      <a class="settings-row" href="index.php?view=privacySecurity">
        <span class="settings-icon"><?= icon('shield', 'meta-icon') ?></span>
        <span class="settings-label">Privacy &amp; Security</span>
        <?= icon('chevron-right', 'settings-chevron') ?>
      </a>
    </div>

    <div class="settings-group-title">About</div>
    <div class="settings-menu">
      <a class="settings-row" href="index.php?view=aboutSystem">
        <span class="settings-icon"><?= icon('info', 'meta-icon') ?></span>
        <span class="settings-label">About SBO Nexus</span>
        <?= icon('chevron-right', 'settings-chevron') ?>
      </a>
      <a class="settings-row" href="index.php?view=helpSupport">
        <span class="settings-icon"><?= icon('help', 'meta-icon') ?></span>
        <span class="settings-label">Help &amp; Support</span>
        <?= icon('chevron-right', 'settings-chevron') ?>
      </a>
    </div>

    <div class="settings-group-title">&nbsp;</div>
    <div class="settings-menu">
      <a class="settings-row danger" href="auth/logout.php">
        <span class="settings-icon"><?= icon('logout', 'meta-icon') ?></span>
        <span class="settings-label">Log Out</span>
      </a>
    </div>
    <?php
}

// ================== PERSONAL INFORMATION ==================
elseif ($view === 'personalInfo') {
    ?>
    <a class="profile-back" href="index.php?view=profile"><?= icon('chevron-right') ?><span>Back to Profile</span></a>
    <div class="profile-page-heading">
      <div>
        <div class="profile-kicker">ACCOUNT · PROFILE</div>
      </div>
      <div class="profile-heading-badge"><?= icon('shield') ?><span>Account information</span></div>
    </div>

    <div class="personal-profile-card">
      <div class="personal-profile-bg personal-bg-one"></div>
      <div class="personal-profile-bg personal-bg-two"></div>
      <div class="profile-identity-row">
        <div class="profile-avatar-wrap">
          <div class="profile-avatar-ring"><?= avatar_html($profileUser, 142) ?></div>
          <span class="profile-camera-badge"><?= icon('camera') ?></span>
          <span class="profile-sparkle sparkle-one"><?= archer_icon() ?></span><span class="profile-sparkle sparkle-two"><?= archer_icon() ?></span>
        </div>
        <div class="profile-identity-main">
          <h3><?= h($profileUser['full_name']) ?></h3>
          <span class="profile-role-pill"><?= icon('graduation') ?><span><?= h($role === 'student' ? 'Student' : ($role === 'officer' ? ($myPositionTitle !== '' ? $myPositionTitle . ' · SBO Officer' : 'SBO Officer') : 'Adviser · Admin')) ?></span></span>
        </div>
        <form class="profile-photo-form" id="profilePhotoForm" method="post" action="index.php?view=profile" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_profile_photo">
          <label class="profile-file-picker">
            <?= icon('upload') ?><span class="profile-file-name">Choose a profile photo</span>
            <input type="file" name="photo" accept="image/*" required>
          </label>
          <button type="submit" class="btn btn-primary profile-update-photo"><?= icon('camera') ?><span>Update photo</span></button>
        </form>
      </div>

      <div class="profile-info-grid">
        <div class="profile-info-item"><div class="profile-info-icon"><?= icon('mail') ?></div><div><div class="k">EMAIL</div><div class="v"><?= h($profileUser['email']) ?></div></div></div>
        <?php if ($profileUser['student_id']): ?><div class="profile-info-item"><div class="profile-info-icon"><?= icon('user') ?></div><div><div class="k">ID NUMBER</div><div class="v mono"><?= h($profileUser['student_id']) ?></div></div></div><?php endif; ?>
        <?php if ($profileUser['year_level']): ?><div class="profile-info-item"><div class="profile-info-icon"><?= icon('graduation') ?></div><div><div class="k">YEAR LEVEL</div><div class="v"><?= h($profileUser['year_level']) ?></div></div></div><?php endif; ?>
        <?php if ($profileUser['course']): ?><div class="profile-info-item"><div class="profile-info-icon"><?= icon('book') ?></div><div><div class="k">COURSE</div><div class="v"><?= h($profileUser['course']) ?></div></div></div><?php endif; ?>
        <?php
        $profileInstitute = '';
        if (in_array(($profileUser['course'] ?? ''), ['BS Information Technology', 'Bachelor of Science in Information Technology'], true)) {
            $profileInstitute = 'Institute of Computer Studies';
        } elseif (($profileUser['course'] ?? '') === 'BS Business Administration') {
            $profileInstitute = 'Institute of Business Management';
        } elseif (($profileUser['course'] ?? '') === 'Bachelor of Education') {
            $profileInstitute = 'Institute of Teacher Education';
        }
        ?>
        <?php if ($profileInstitute): ?><div class="profile-info-item"><div class="profile-info-icon"><?= icon('building') ?></div><div><div class="k">INSTITUTE</div><div class="v"><?= h($profileInstitute) ?></div></div></div><?php endif; ?>
      </div>

    </div>

    <script>
    (function(){
      const form = document.getElementById('profilePhotoForm');
      if (!form) return;
      const input = form.querySelector('input[type="file"]');
      const name = form.querySelector('.profile-file-name');
      if (!input) return;

      // Selecting a file must NOT change the saved/current profile photo.
      // The real profile image is updated only after the user submits
      // the form with the "Update photo" button.
      input.addEventListener('change', function(){
        if (input.files && input.files[0] && name) {
          name.textContent = input.files[0].name;
        }
      });
    })();
    </script>

    <?php
}

// ================== CHANGE PASSWORD ==================
elseif ($view === 'changePassword') {
    ?>
    <a class="back-link" href="index.php?view=profile">&larr; Back to Profile</a>
    <div class="topbar"><div><h2>Change password</h2><p class="sub">Update the password for your account.</p></div></div>

    <div class="detail-card" style="max-width:440px;">
      <form method="post" action="actions.php">
        <input type="hidden" name="action" value="change_password">
        <div class="field"><label>Current password</label><input type="password" name="current_password" required></div>
        <div class="field"><label>New password</label><input type="password" name="new_password" minlength="8" required><p class="hint">At least 8 characters.</p></div>
        <div class="field"><label>Confirm new password</label><input type="password" name="confirm_password" minlength="8" required></div>
        <button type="submit" class="btn btn-primary full-w">Update password</button>
      </form>
    </div>
    <?php
}

// ================== PRIVACY & SECURITY ==================
elseif ($view === 'privacySecurity') {
    ?>
    <a class="back-link" href="index.php?view=profile">&larr; Back to Profile</a>
    <div class="topbar"><div><h2>Privacy &amp; Security</h2><p class="sub">How your information is used within SBO Nexus.</p></div></div>

    <div class="detail-card" style="max-width:560px;">
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;">
        Your name, ID number, year level, course, and photo are visible to SBO officers and your adviser so they can manage events, attendance, and your event activity. Other students only see your name, role, and photo when you post public feedback, comments, or poll activity — your ID number, email, and course are never shown to other students.
      </p>
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;margin-top:12px;">
        To request a correction to your account details or ask that your data be removed, contact your SBO adviser directly.
      </p>
    </div>

    <?php if ($role === 'student'):
        ensure_email_notification_schema($conn);
        $prefStmt = $conn->prepare("SELECT email_notifications FROM users WHERE id = ?");
        $prefStmt->bind_param('i', $me['id']);
        $prefStmt->execute();
        $prefRow = $prefStmt->get_result()->fetch_assoc();
        $prefStmt->close();
        $emailOn = !$prefRow || (int) $prefRow['email_notifications'] === 1;
    ?>
    <div class="detail-card" style="max-width:560px;margin-top:16px;">
      <strong>Email notifications</strong>
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;margin:6px 0 12px;">
        When the Adviser approves a new SBO event, we email you the details at your NBSC address. Your email is never shown to other students.
      </p>
      <form method="post" action="actions.php">
        <input type="hidden" name="action" value="update_email_preference">
        <label style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
          <input type="checkbox" name="email_notifications" value="1" <?= $emailOn ? 'checked' : '' ?>>
          <span>Email me when a new event is posted</span>
        </label>
        <button type="submit" class="btn btn-primary btn-sm">Save</button>
      </form>
    </div>
    <?php endif; ?>
    <?php
}

// ================== ABOUT SBO NEXUS ==================
elseif ($view === 'aboutSystem') {
    ?>
    <a class="back-link" href="index.php?view=profile">&larr; Back to Profile</a>
    <div class="topbar"><div><h2>About SBO Nexus</h2><p class="sub">Event management for the ICS Supreme Body Organization.</p></div></div>

    <div class="detail-card" style="max-width:560px;">
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;">
        SBO Nexus is the event management and student governance system for the ICS Supreme Body Organization at Northern Bukidnon State College. It covers event creation and approval, event participation, event feedback and ratings, and polls and surveys — built to keep the ICS student body informed and involved.
      </p>
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;margin-top:12px;">
        Built for the Institute of Computer Studies. One ICS, One Community.
      </p>
    </div>
    <?php
}

// ================== HELP & SUPPORT ==================
elseif ($view === 'helpSupport') {
    ?>
    <a class="back-link" href="index.php?view=profile">&larr; Back to Profile</a>
    <div class="topbar"><div><h2>Help &amp; Support</h2><p class="sub">Need a hand, or found something broken?</p></div></div>

    <div class="detail-card" style="max-width:560px;">
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;">
        For questions about a specific event, reach out to the officer listed under "Meet the Committees" on that event's page. For account issues — wrong year level, course, or ID number — contact your SBO adviser.
      </p>
      <p class="browse-desc" style="-webkit-line-clamp:unset;font-size:13.5px;line-height:1.7;margin-top:12px;">
        To share your thoughts about an event, open its event details and use the feedback and rating section to leave a comment and rate your experience.
      </p>
    </div>
    <?php
}

// ================== EDIT EVENT ==================
elseif ($view === 'editEvent') {
    ?>
    <a class="back-link" href="index.php?view=myEvents">&larr; Back</a>
    <div class="topbar"><div><h2>Edit event</h2><p class="sub">Update the details, or click the map to move the pin.</p></div></div>

    <div class="detail-card" style="max-width:560px;">
      <form method="post" action="actions.php">
        <input type="hidden" name="action" value="update_event">
        <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
        <input type="hidden" name="latitude" id="f-lat" value="<?= h($editEvent['latitude']) ?>">
        <input type="hidden" name="longitude" id="f-lng" value="<?= h($editEvent['longitude']) ?>">
        <div class="field"><label>Event title</label><input type="text" name="title" value="<?= h($editEvent['title']) ?>" required></div>
        <div class="field"><label>Description</label><textarea name="description"><?= h($editEvent['description']) ?></textarea></div>
        <div class="row2">
          <div class="field"><label>From</label><input type="date" name="event_date" value="<?= h($editEvent['event_date']) ?>" required></div>
          <div class="field"><label>Time from</label><input type="time" name="event_time" value="<?= h($editEvent['event_time']) ?>"></div>
        </div>
        <div class="row2">
          <div class="field"><label>To</label><input type="date" name="event_end_date" value="<?= h(event_end_date($editEvent)) ?>" required></div>
          <div class="field"><label>Time to</label><input type="time" name="event_end_time" value="<?= h($editEvent['event_end_time'] ?? '') ?>"></div>
        </div>
        <p class="hint">Duration: <strong><?= event_duration_days($editEvent) ?> day<?= event_duration_days($editEvent) === 1 ? '' : 's' ?></strong>.</p>
        <div class="row2">
          <div class="field"><label>Venue</label><input type="text" name="venue" value="<?= h($editEvent['venue']) ?>" required></div>
          <div class="field"><label>Category</label>
            <select name="category">
              <?php foreach (['Academic','Sports','Cultural','Outreach','Organizational'] as $c): ?>
                <option <?= $editEvent['category']===$c?'selected':'' ?>><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Pin the location on the map</label>
          <div id="pickerMap" class="picker-map" data-lat="<?= h($editEvent['latitude']) ?>" data-lng="<?= h($editEvent['longitude']) ?>"></div>
          <p class="hint">Click anywhere on the map to move the pin. <?= $editEvent['latitude'] !== null ? 'Current pin shown below.' : 'No pin set yet.' ?></p>
        </div>
        <div class="field">
            <label>Check-in range: <strong id="radiusLabel"><?= (int) event_geofence_radius($editEvent) ?></strong> meters</label>
            <input type="range" name="geofence_radius" id="geoRadius" min="20" max="500" step="10" value="<?= (int) event_geofence_radius($editEvent) ?>">
            <p class="hint">Students can only check in within this distance of the pin. A smaller range is stricter, so use about 30 to 50 m for one room or building and 100 m or more for an open field. The circle on the map shows the range.</p>
          </div>
        <div class="modal-actions" style="justify-content:flex-start;">
          <button type="submit" class="btn btn-primary">Save changes</button>
          <a href="index.php?view=myEvents" class="btn">Cancel</a>
        </div>
      </form>

      <div class="field"><label>Event committees</label>
        <p class="hint">Add officers/advisers to this event and note which committee they're on (e.g. Logistics, Documentation, Program).</p>
        <div class="event-organizer-manage">
          <?php if (empty($editEventOrganizers)): ?>
            <div class="manage-empty">No committee members yet.</div>
          <?php else: foreach ($editEventOrganizers as $eo): ?>
            <div class="manage-organizer-row">
              <div><strong><?= h($eo['full_name']) ?></strong><span><?= h($eo['committee'] ?: ($eo['position_title'] ?: 'Committee Member')) ?></span></div>
              <form method="post" action="actions.php" onsubmit="return confirm('Remove this person from the event?')">
                <input type="hidden" name="action" value="remove_event_organizer">
                <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
                <input type="hidden" name="organizer_id" value="<?= (int)$eo['organizer_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
              </form>
            </div>
          <?php endforeach; endif; ?>
          <?php if (!empty($organizerCandidates)): ?>
            <form method="post" action="actions.php" class="add-organizer-row">
              <input type="hidden" name="action" value="add_event_organizer">
              <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
              <select name="user_id" required>
                <option value="">Select a person</option>
                <?php foreach ($organizerCandidates as $candidate): ?>
                  <option value="<?= (int)$candidate['id'] ?>"><?= h($candidate['full_name']) ?><?= $candidate['position_title'] ? ' — ' . h($candidate['position_title']) : '' ?></option>
                <?php endforeach; ?>
              </select>
              <input type="text" name="committee" placeholder="Committee (e.g. Logistics)" maxlength="100">
              <button type="submit" class="btn btn-sm btn-primary">Add to committee</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label>Event Profile / Cover Photo</label>
        <p class="hint">This is a separate cover-photo upload. Officers can replace it anytime without changing the event photo gallery.</p>
        <div class="event-cover-manage">
          <?php if ($editEventHeroPhoto): ?>
            <div class="event-cover-preview">
              <img src="<?= h($editEventHeroPhoto) ?>" alt="Current event profile photo">
              <span class="cover-photo-badge">Current event profile photo</span>
            </div>
          <?php else: ?>
            <div class="event-cover-empty">No event profile photo has been uploaded yet.</div>
          <?php endif; ?>
          <form method="post" action="actions.php" enctype="multipart/form-data" class="event-cover-upload">
            <input type="hidden" name="action" value="upload_event_cover">
            <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
            <input type="file" name="cover_photo" accept="image/jpeg,image/png,image/webp,image/gif" required>
            <button type="submit" class="btn btn-sm btn-primary"><?= $editEventHeroPhoto ? 'Change cover photo' : 'Upload cover photo' ?></button>
          </form>
        </div>
      </div>

      <div class="field">
        <label>Event Photo Gallery</label>
        <p class="hint">Upload photos from the event here. These are separate from the event profile/cover photo.</p>
        <div class="event-media-manage">
          <form method="post" action="actions.php" enctype="multipart/form-data" class="event-media-upload">
            <input type="hidden" name="action" value="upload_event_photo">
            <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
            <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required>
            <button type="submit" class="btn btn-sm btn-primary">Upload photos</button>
          </form>
          <?php if (empty($editEventPhotos)): ?>
            <div class="manage-empty">No event gallery photos yet.</div>
          <?php else: ?>
            <div class="photo-grid event-manage-photo-grid">
              <?php foreach ($editEventPhotos as $ph): ?>
                <?php if (!empty($ph['is_cover'])) continue; ?>
                <div class="ph">
                  <img src="<?= h($ph['file_path']) ?>" alt="Event photo" loading="lazy">
                  <div class="photo-actions">
                    <form method="post" action="actions.php" onsubmit="return confirm('Delete this event photo?')">
                      <input type="hidden" name="action" value="delete_event_photo">
                      <input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>">
                      <input type="hidden" name="event_id" value="<?= (int)$editEvent['id'] ?>">
                      <button type="submit" class="del-photo">Delete</button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <script>
      (function(){
        var el = document.getElementById('pickerMap');
        if (!el) return;
        if (typeof L === 'undefined') {
          el.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--slate);font-size:12.5px;text-align:center;padding:0 16px;">Map could not load — check your internet connection and refresh.</div>';
          return;
        }
        var DEFAULT_LAT = 8.3667, DEFAULT_LNG = 124.8667;
        var hasLat = el.dataset.lat !== '' && el.dataset.lat !== undefined;
        var startLat = hasLat ? parseFloat(el.dataset.lat) : DEFAULT_LAT;
        var startLng = hasLat ? parseFloat(el.dataset.lng) : DEFAULT_LNG;

        var map = L.map('pickerMap').setView([startLat, startLng], hasLat ? 16 : 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap contributors',
          maxZoom: 19
        }).addTo(map);

        var marker = hasLat ? L.marker([startLat, startLng]).addTo(map) : null;
        var circle = null;
        var slider = document.getElementById('geoRadius');
        var label = document.getElementById('radiusLabel');

        function drawRange(){
          if (label && slider) { label.textContent = slider.value; }
          if (!marker || !slider) return;
          if (circle) { circle.setLatLng(marker.getLatLng()); circle.setRadius(parseInt(slider.value, 10)); }
          else { circle = L.circle(marker.getLatLng(), { radius: parseInt(slider.value, 10), color: '#0b6b3a', fillOpacity: 0.15 }).addTo(map); }
        }
        if (slider) { slider.addEventListener('input', drawRange); }
        drawRange();

        map.on('click', function(e){
          document.getElementById('f-lat').value = e.latlng.lat.toFixed(7);
          document.getElementById('f-lng').value = e.latlng.lng.toFixed(7);
          if (marker) { marker.setLatLng(e.latlng); }
          else { marker = L.marker(e.latlng).addTo(map); }
          drawRange();
        });

        setTimeout(function(){ map.invalidateSize(); }, 100);
      })();
    </script>
    <?php
}

// ================== POLLS: LIST ==================
elseif ($view === 'polls') {
    $pollList = get_polls($conn);
    ?>
    <div class="polls-hero">
      <div>
        <div class="polls-kicker">ICS-SBO COMMUNITY VOICE</div>
        <h2>Polls &amp; Surveys</h2>
        <p class="sub">Quick decisions, real student voices. Vote on active polls and see the community results.</p>
      </div>
      <div class="polls-hero-icon" aria-hidden="true"><?= archer_icon() ?></div>
    </div>
    <div class="poll-role-note">
      <?php if ($role === 'student'): ?>
        <span class="role-chip student-chip">Student voter</span> <span>Choose one option on each open poll. Your vote is recorded once.</span>
      <?php elseif ($role === 'officer'): ?>
        <span class="role-chip officer-chip">SBO officer</span> <span>Create polls, monitor results, and close polls when voting ends.</span>
      <?php else: ?>
        <span class="role-chip admin-chip">Administrator</span> <span>View poll activity and results.</span>
      <?php endif; ?>
    </div>
    <div class="topbar" style="margin-top:18px;">
      <div><h3 style="margin:0;font-size:17px;">Community polls</h3><p class="sub"><?= count($pollList) ?> active and past poll<?= count($pollList) === 1 ? '' : 's' ?></p></div>
      <div class="topbar-actions">
        <?php if ($role === 'officer'): ?>
          <button class="btn btn-primary poll-create-btn" onclick="document.getElementById('newPollOverlay').classList.add('show')">+ Create a poll</button>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($pollList)): ?>
      <div class="poll-empty"><div class="poll-empty-icon">◎</div><div class="big">No polls yet</div><p>There are no polls available right now.</p></div>
    <?php else: ?>
      <div class="poll-grid">
      <?php foreach ($pollList as $p):
        $optCount = $conn->prepare("SELECT COUNT(*) c FROM poll_options WHERE poll_id = ?");
        $optCount->bind_param('i', $p['id']);
        $optCount->execute();
        $numOptions = $optCount->get_result()->fetch_assoc()['c'];
        $totalVotes = total_poll_votes($conn, $p['id']);
        $myVote = get_my_vote($conn, $p['id'], $me['id']);
        ?>
        <div class="poll-card">
          <div class="poll-card-glow"></div>
          <div class="poll-card-top">
            <span class="poll-badge <?= $p['status']==='open' ? 'is-open' : 'is-closed' ?>"><span></span><?= $p['status']==='open' ? 'Open for voting' : 'Closed' ?></span>
            <span class="poll-arrow">→</span>
          </div>
          <h3><?= h($p['question']) ?></h3>
          <?php if (!empty($p['description'])): ?><p class="poll-card-desc"><?= h($p['description']) ?></p><?php endif; ?>
          <div class="poll-card-meta">
            <span>By <?= h($p['creator_name']) ?></span><span>•</span><span><?= $numOptions ?> choices</span><span>•</span><span><?= $totalVotes ?> vote<?= $totalVotes === 1 ? '' : 's' ?></span>
          </div>
          <div class="poll-card-footer">
            <div class="poll-card-footer-main">
              <a href="index.php?view=pollDetail&id=<?= (int)$p['id'] ?>" class="poll-view-link">
                <?php if ($role === 'student' && $myVote !== null): ?>
                  <span class="poll-status-text voted-text">✓ You voted</span>
                <?php elseif ($role === 'student' && $p['status'] === 'open'): ?>
                  <span class="poll-status-text vote-now-text">Vote now</span>
                <?php elseif ($role === 'officer' && $p['status'] === 'open'): ?>
                  <span class="poll-status-text manage-text">Manage poll</span>
                <?php else: ?>
                  <span class="poll-status-text">View results</span>
                <?php endif; ?>
              </a>
              <?php if ($role === 'officer' && (int)$p['created_by'] === (int)$me['id']): ?>
                <div class="poll-manage-actions">
                  <a class="poll-action-btn poll-edit" href="index.php?view=pollDetail&id=<?= (int)$p['id'] ?>&edit=1" title="Edit poll" aria-label="Edit poll">✎</a>
                  <form method="post" action="actions.php" onsubmit="return confirm('Delete this poll permanently? All votes and choices will also be removed.');">
                    <input type="hidden" name="action" value="delete_poll">
                    <input type="hidden" name="poll_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="poll-action-btn poll-delete" title="Delete poll" aria-label="Delete poll">⌫</button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($role === 'officer'): ?>
    <div class="overlay" id="newPollOverlay">
      <div class="modal poll-modal">
        <div class="poll-modal-icon"><?= archer_icon() ?></div>
        <h3>Create a community poll</h3>
        <p class="msub">Build a quick question for students. Add 2 to 6 choices.</p>
        <form method="post" action="actions.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create_poll">
          <div class="field"><label>Question</label><input type="text" name="question" placeholder="What should we decide on?" required maxlength="255"></div>
          <div class="field"><label>Description <span>(optional)</span></label><textarea name="description" placeholder="Give students a little context…" maxlength="500"></textarea></div>
          <div class="field" id="pollOptionsWrap">
            <label>Answer choices <span>(optional image for each choice)</span></label>
            <div class="poll-choice-input"><input type="text" name="options[]" placeholder="Choice 1" required><input type="file" name="option_images[]" accept="image/jpeg,image/png,image/webp,image/gif"></div>
            <div class="poll-choice-input"><input type="text" name="options[]" placeholder="Choice 2" required><input type="file" name="option_images[]" accept="image/jpeg,image/png,image/webp,image/gif"></div>
            <div class="poll-choice-input"><input type="text" name="options[]" placeholder="Choice 3 (optional)"><input type="file" name="option_images[]" accept="image/jpeg,image/png,image/webp,image/gif"></div>
            <div class="poll-choice-input"><input type="text" name="options[]" placeholder="Choice 4 (optional)"><input type="file" name="option_images[]" accept="image/jpeg,image/png,image/webp,image/gif"></div>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" onclick="document.getElementById('newPollOverlay').classList.remove('show')">Cancel</button>
            <button type="submit" class="btn btn-primary">Publish poll</button>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>
    <?php
}

// ================== POLL DETAIL ==================
elseif ($view === 'pollDetail') {
    ?>
    <a class="back-link" href="index.php?view=polls">&larr; Back</a>
    <div class="detail-card poll-detail-card" style="max-width:760px;">
      <div class="poll-detail-head">
        <div>
          <div class="poll-detail-kicker">COMMUNITY POLL</div>
          <h2 style="margin:0 0 7px;font-size:24px;"><?= h($poll['question']) ?></h2>
          <div class="concern-meta">By <?= h($poll['creator_name']) ?> · <?= $pollTotalVotes ?> vote<?= $pollTotalVotes === 1 ? '' : 's' ?></div>
        </div>
        <span class="badge <?= $poll['status']==='open' ? 'badge-approved' : 'badge-full' ?>"><?= $poll['status']==='open' ? 'Open' : 'Closed' ?></span>
      </div>
      <?php if ($poll['description']): ?><p class="detail-desc"><?= nl2br(h($poll['description'])) ?></p><?php endif; ?>

      <?php if ($role === 'officer' && (int)$poll['created_by'] === (int)$me['id'] && isset($_GET['edit']) && $_GET['edit'] === '1'):
        $editOptions = $pollOptions;
        $hasVotes = $pollTotalVotes > 0;
      ?>
      <div class="poll-edit-panel">
        <div class="poll-edit-panel-head"><div><strong>Edit poll</strong><span><?= $hasVotes ? 'Question and description can be changed; answer choices are locked after voting starts.' : 'You can update the question, description, and choices.' ?></span></div><a class="poll-edit-close" href="index.php?view=pollDetail&id=<?= (int)$poll['id'] ?>">×</a></div>
        <form method="post" action="actions.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="update_poll">
          <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
          <div class="field"><label>Question</label><input type="text" name="question" value="<?= h($poll['question']) ?>" required maxlength="255"></div>
          <div class="field"><label>Description <span>(optional)</span></label><textarea name="description" maxlength="500"><?= h($poll['description']) ?></textarea></div>
          <div class="field"><label>Answer choices</label>
          <?php foreach ($editOptions as $opt): ?>
            <div class="poll-choice-input"><input type="text" name="options[]" value="<?= h($opt['option_text']) ?>" required maxlength="255" <?= $hasVotes ? 'disabled' : '' ?>><?php if (!empty($opt['image_path'])): ?><img src="<?= h($opt['image_path']) ?>" class="poll-choice-thumb" alt="Choice image"><?php endif; ?><?php if (!$hasVotes): ?><input type="file" name="option_images[]" accept="image/jpeg,image/png,image/webp,image/gif"><?php endif; ?></div>
          <?php endforeach; ?>
          <?php if (!$hasVotes): ?>
            <input type="text" name="options[]" placeholder="Additional choice (optional)" maxlength="255" style="margin-bottom:8px;">
            <input type="text" name="options[]" placeholder="Additional choice (optional)" maxlength="255" style="margin-bottom:8px;">
          <?php endif; ?>
          </div>
          <div class="modal-actions"><a class="btn" href="index.php?view=pollDetail&id=<?= (int)$poll['id'] ?>">Cancel</a><button type="submit" class="btn btn-primary">Save changes</button></div>
        </form>
      </div>
      <?php endif; ?>

      <div style="margin-top:16px;">
      <?php
      $showResults = $role !== 'student' || $myVoteOptionId !== null || $poll['status'] === 'closed';
      foreach ($pollOptions as $opt):
        $pct = $pollTotalVotes > 0 ? round(($opt['votes'] / $pollTotalVotes) * 100) : 0;
        $isMine = $myVoteOptionId === (int)$opt['id'];
        if ($showResults): ?>
          <div class="poll-option <?= $isMine ? 'voted' : '' ?>">
            <div class="poll-option-row">
              <span class="poll-option-label"><?php if (!empty($opt['image_path'])): ?><img src="<?= h($opt['image_path']) ?>" class="poll-choice-display" alt="Choice image"><?php endif; ?><?= h($opt['option_text']) ?><?= $isMine ? ' ✓' : '' ?></span>
              <span class="poll-option-pct"><?= $pct ?>% (<?= (int)$opt['votes'] ?>)</span>
            </div>
            <div class="poll-bar"><div class="poll-bar-fill" style="width:<?= $pct ?>%;"></div></div>
          </div>
        <?php elseif ($role === 'student'): ?>
          <form method="post" action="actions.php" class="vote-form">
            <input type="hidden" name="action" value="vote_poll">
            <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
            <input type="hidden" name="option_id" value="<?= (int)$opt['id'] ?>">
            <button type="submit" class="vote-btn"><span class="vote-radio"></span><span class="vote-choice-content"><?php if (!empty($opt['image_path'])): ?><img src="<?= h($opt['image_path']) ?>" class="poll-choice-display" alt="Choice image"><?php endif; ?><span><?= h($opt['option_text']) ?></span></span><span class="vote-chevron">→</span></button>
          </form>
        <?php endif;
      endforeach; ?>
      </div>

      <?php if ($poll['status'] === 'open' && $role === 'student' && $myVoteOptionId === null): ?>
        <p class="poll-hint">Tap one answer to cast your vote. You can vote only once.</p>
      <?php elseif ($role !== 'student'): ?>
        <p class="poll-hint">Results are visible to SBO staff. Students are the only users who can cast votes.</p>
      <?php endif; ?>

      <?php if ($role === 'officer' && (int)$poll['created_by'] === (int)$me['id']): ?>
        <div class="poll-detail-actions">
          <a class="btn btn-sm" href="index.php?view=pollDetail&id=<?= (int)$poll['id'] ?>&edit=1">✎ Edit poll</a>
          <?php if ($poll['status'] === 'open'): ?>
          <form method="post" action="actions.php" onsubmit="return confirm('Close this poll? No more votes will be accepted.')">
            <input type="hidden" name="action" value="close_poll">
            <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger">Close poll</button>
          </form>
          <?php endif; ?>
          <form method="post" action="actions.php" onsubmit="return confirm('Delete this poll permanently? All votes and choices will also be removed.');">
            <input type="hidden" name="action" value="delete_poll">
            <input type="hidden" name="poll_id" value="<?= (int)$poll['id'] ?>">
            <button type="submit" class="btn btn-sm poll-detail-delete">Delete poll</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
    <?php
}
?>
    <?php if ($role === 'student'): ?>
    <div class="overlay" id="regOverlay">
      <div class="modal">
        <h3>Join this event</h3>
        <p class="msub" id="reg-event-name"></p>
        <form method="post" action="actions.php">
          <input type="hidden" name="action" value="register">
          <input type="hidden" name="event_id" id="reg-event-id">
          <div class="field">
            <label>Joining as</label>
            <input type="text" value="<?= h($me['full_name']) ?> · <?= h($me['student_id']) ?>" disabled>
          </div>
          <div class="field">
            <label>Year level &amp; course</label>
            <input type="text" value="<?= h($me['year_level']) ?> · <?= h($me['course']) ?>" disabled>
          </div>
          <div class="modal-actions">
            <button type="button" class="btn" onclick="closeReg()">Cancel</button>
            <button type="submit" class="btn btn-primary">Confirm I'm Going</button>
          </div>
        </form>
      </div>
    </div>
    <script>
      function openReg(id, title, date){
        document.getElementById('reg-event-id').value = id;
        document.getElementById('reg-event-name').textContent = title + ' · ' + date;
        document.getElementById('regOverlay').classList.add('show');
      }
      function closeReg(){ document.getElementById('regOverlay').classList.remove('show'); }
    </script>
    <?php endif; ?>
  </main>
</div>
<script>
(function(){
  const trigger=document.getElementById('panelTrigger');
  const menu=document.getElementById('panelMenu');
  if(!trigger||!menu)return;
  function closeMenu(){trigger.setAttribute('aria-expanded','false');menu.setAttribute('aria-hidden','true');menu.classList.remove('open');}
  trigger.addEventListener('click',function(e){e.stopPropagation();const open=menu.classList.toggle('open');trigger.setAttribute('aria-expanded',open?'true':'false');menu.setAttribute('aria-hidden',open?'false':'true');});
  document.addEventListener('click',function(e){if(!e.target.closest('.panel-dropdown-wrap'))closeMenu();});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')closeMenu();});
})();
</script>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('service-worker.js').catch(function (error) {
      console.warn('SBO Nexus service worker registration failed:', error);
    });
  });
}
</script>
<script>
/* SBO Nexus weather + practical outfit guide. Uses Open-Meteo public APIs; no API key required. */
(function(){
  const card=document.getElementById('weatherOutfitCard');
  if(!card) return;
  const $=id=>document.getElementById(id);
  let currentPlace={name:'Manolo Fortich, Bukidnon',lat:8.369,lon:124.865};
  const weatherText={0:'Clear sky',1:'Mainly clear',2:'Partly cloudy',3:'Overcast',45:'Foggy',48:'Foggy',51:'Light drizzle',53:'Drizzle',55:'Heavy drizzle',56:'Freezing drizzle',57:'Freezing drizzle',61:'Light rain',63:'Rain',65:'Heavy rain',66:'Freezing rain',67:'Freezing rain',71:'Light snow',73:'Snow',75:'Heavy snow',77:'Snow grains',80:'Rain showers',81:'Rain showers',82:'Heavy rain showers',85:'Snow showers',86:'Heavy snow showers',95:'Thunderstorm',96:'Thunderstorm with hail',99:'Severe thunderstorm'};
  const weatherEmoji=code=>({0:'☀️',1:'🌤️',2:'⛅',3:'☁️',45:'🌫️',48:'🌫️',51:'🌦️',53:'🌦️',55:'🌧️',56:'🌧️',57:'🌧️',61:'🌧️',63:'🌧️',65:'🌧️',66:'🌧️',67:'🌧️',71:'❄️',73:'❄️',75:'❄️',77:'❄️',80:'🌦️',81:'🌧️',82:'⛈️',85:'🌨️',86:'🌨️',95:'⛈️',96:'⛈️',99:'⛈️'}[code]||'🌤️');
  function setStatus(msg,isError){$('weatherStatus').textContent=msg;$('weatherStatus').classList.toggle('is-error',!!isError);}
  function outfit(temp,code,rain,wind){
    const wet=rain>=45||[51,53,55,56,57,61,63,65,66,67,80,81,82,95,96,99].includes(code);
    let title,advice,icon,extra;
    if([95,96,99].includes(code)){title='Light layers + rain protection';advice='Wear a breathable top, comfortable long pants, and a waterproof jacket. Bring an umbrella and avoid exposed areas during thunderstorms.';icon='⛈️';extra='If thunder or lightning starts, stay indoors and wait for conditions to improve.';}
    else if(wet){title='Rain-ready campus outfit';advice='Choose a quick-drying shirt, comfortable pants, and shoes with good grip. Bring an umbrella or light raincoat to help keep your bag and belongings dry.';icon='🌧️';extra='A small waterproof pouch can help protect your phone and school papers.';}
    else if(temp>=31){title='Light and breathable';advice='Wear a lightweight, breathable shirt and comfortable trousers or a skirt that follows your school dress code. Choose comfortable shoes and bring water for the warm day.';icon='☀️';extra='A cap or umbrella can provide shade outdoors; follow campus rules.';}
    else if(temp>=25){title='Comfortable everyday layers';advice='A breathable top, comfortable bottoms, and walking shoes should work well. A thin overshirt can help in air-conditioned classrooms.';icon='👕';extra='Consider a compact umbrella if the sky looks unsettled.';}
    else {title='Bring a light layer';advice='Wear your usual comfortable campus outfit and add a light jacket or cardigan for cooler conditions, especially in the morning or air-conditioned rooms.';icon='🧥';extra='If you feel warm later, you can easily remove the extra layer.';}
    if(wind>=30&&!wet) extra+=' It may feel breezy, so choose a layer that stays comfortable in the wind.';
    $('outfitTitle').textContent=title;$('outfitAdvice').textContent=advice;$('outfitIcon').textContent=icon;$('outfitExtra').textContent=extra;
  }
  async function loadWeather(place){
    currentPlace=place;$('weatherLocation').textContent=place.name;$('weatherTemperature').textContent='--°';$('weatherCondition').textContent='Loading forecast…';setStatus('Getting the latest available weather…');
    try{
      const url='https://api.open-meteo.com/v1/forecast?latitude='+encodeURIComponent(place.lat)+'&longitude='+encodeURIComponent(place.lon)+'&current=temperature_2m,relative_humidity_2m,apparent_temperature,precipitation,weather_code,wind_speed_10m&hourly=precipitation_probability&forecast_days=1&timezone=auto';
      const response=await fetch(url);if(!response.ok)throw new Error('Weather service is temporarily unavailable.');const data=await response.json();if(!data.current)throw new Error('No current weather data was returned.');
      const c=data.current,code=Number(c.weather_code),temp=Math.round(c.temperature_2m),rainHours=data.hourly&&data.hourly.precipitation_probability||[];let rain=0;
      if(rainHours.length){const now=new Date(c.time).getTime();let nearest=0,delta=Infinity;for(let i=0;i<data.hourly.time.length;i++){const d=Math.abs(new Date(data.hourly.time[i]).getTime()-now);if(d<delta){delta=d;nearest=i;}}rain=Number(rainHours[nearest]||0);}
      $('weatherSymbol').textContent=weatherEmoji(code);$('weatherTemperature').textContent=temp+'°C';$('weatherCondition').textContent=weatherText[code]||'Current conditions';$('weatherFeels').textContent='Feels like '+Math.round(c.apparent_temperature)+'°C';$('weatherHumidity').textContent=Math.round(c.relative_humidity_2m)+'%';$('weatherRain').textContent=rain+'%';$('weatherWind').textContent=Math.round(c.wind_speed_10m)+' km/h';outfit(temp,code,rain,Number(c.wind_speed_10m)||0);setStatus('Updated '+new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})+'. Forecast data may change throughout the day.');
    }catch(err){$('weatherCondition').textContent='Weather unavailable';$('weatherFeels').textContent='Please try again in a moment.';setStatus(err.message||'Could not load weather. Check your internet connection.',true);}
  }
  async function searchPlace(){const q=$('weatherCityInput').value.trim();if(!q){setStatus('Enter a city or town name first.',true);return;}$('weatherSearchButton').disabled=true;setStatus('Searching for '+q+'…');try{const r=await fetch('https://geocoding-api.open-meteo.com/v1/search?name='+encodeURIComponent(q)+'&count=1&language=en&format=json');if(!r.ok)throw new Error('Place search is temporarily unavailable.');const d=await r.json();if(!d.results||!d.results.length)throw new Error('No matching place found. Try a nearby city or town.');const p=d.results[0];await loadWeather({name:[p.name,p.admin1,p.country].filter(Boolean).filter((v,i,a)=>a.indexOf(v)===i).join(', '),lat:p.latitude,lon:p.longitude});}catch(e){setStatus(e.message,true);}finally{$('weatherSearchButton').disabled=false;}}
  $('weatherSearchButton').addEventListener('click',searchPlace);$('weatherCityInput').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();searchPlace();}});
  $('weatherUseLocation').addEventListener('click',function(){if(!navigator.geolocation){setStatus('Your browser does not support location access. Search for your town instead.',true);return;}setStatus('Waiting for location permission…');navigator.geolocation.getCurrentPosition(async pos=>{let place={name:'Your current location',lat:pos.coords.latitude,lon:pos.coords.longitude};try{const r=await fetch('https://geocoding-api.open-meteo.com/v1/reverse?latitude='+place.lat+'&longitude='+place.lon+'&language=en&format=json');if(r.ok){const d=await r.json();if(d.name)place.name=[d.name,d.admin1].filter(Boolean).join(', ');}}catch(_){}loadWeather(place);},()=>setStatus('Location permission was unavailable. You can search for your town instead.',true),{enableHighAccuracy:false,timeout:10000,maximumAge:600000});});
  loadWeather(currentPlace);
})();
</script>

</body>
</html>
<?php echo inject_csrf_fields(ob_get_clean()); ?>