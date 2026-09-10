<?php

date_default_timezone_set('Asia/Manila');

// SBO Nexus — database and environment configuration
// IMPORTANT: Do not commit this file to GitHub.

function load_sbo_env($file)
{
    if (!is_file($file) || !is_readable($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || $line[0] === '#') {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);

        $name = trim($name);
        $value = trim($value);

        // Remove optional surrounding quotes.
        if (
            strlen($value) >= 2 &&
            (
                ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                ($value[0] === "'" && $value[strlen($value) - 1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        if ($name !== '') {
            putenv($name . '=' . $value);
        }
    }
}

// Load the local .env file if it exists.
load_sbo_env(__DIR__ . DIRECTORY_SEPARATOR . '.env');

define('DB_HOST', getenv('SBO_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('SBO_DB_USER') ?: 'root');
define('DB_PASS', getenv('SBO_DB_PASS') ?: '');
define('DB_NAME', getenv('SBO_DB_NAME') ?: 'sbo_nexus');

// How close (in meters) a student must be to an event's pinned location
// to be allowed to check themselves in for attendance. GPS on phones is
// commonly accurate to ~10-50m outdoors and less indoors, so this is set
// generously; tighten it if you need stricter proof-of-presence.
define('ATTENDANCE_GEOFENCE_RADIUS_METERS', 100);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('Database connection failed.');
}

$conn->set_charset('utf8mb4');