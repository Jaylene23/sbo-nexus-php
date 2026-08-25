# SBO Nexus

Integrated Student Governance and Engagement System for the Institute of
Computer Studies, Northern Bukidnon State College.

## Folder structure

```
sbo-nexus-php/
├── index.php              Main app router/views (student, officer, admin)
├── actions.php             Handles all POST form actions (PRG pattern)
├── config.php              Loads .env and opens the database connection
├── .env                    Local secrets (DB + Google OAuth) — never commit
├── .env.example             Template for .env
│
├── auth/                    Login, registration, Google OAuth
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── google_login.php     Starts the Google OAuth redirect
│   └── google_callback.php  Handles Google's redirect back
│
├── includes/                Shared PHP: DB helpers, auth/session, styling
│   ├── functions.php
│   ├── auth.php
│   ├── google.php
│   └── style.php
│
├── assets/                   Logos (NBSC, ICS, SBO)
├── uploads/                  User-uploaded photos (profiles/, events/, polls/)
├── database/
│   └── schema.sql            Run this once to create the MySQL database
├── docs/
│   └── DEVELOPMENT.md        Development notes / milestone log
│
├── manifest.json, service-worker.js, offline.html, icon-*.png
│                             PWA files — must stay at project root
│
├── vendor/                    Composer dependencies (Google API client, etc.)
├── composer.json / composer.lock
└── google-diagnose.php        Temporary Google-login diagnostic — delete
                                once login is confirmed working
```

## Setup (XAMPP)

1. Place this folder in `htdocs` (the folder name must match whatever you
   put in `GOOGLE_REDIRECT_URI` below).
2. Import `database/schema.sql` into MySQL (e.g. via phpMyAdmin).
3. Copy `.env.example` to `.env` and fill in your DB credentials and
   Google OAuth client ID/secret.
4. **Requires PHP 8.1 or higher** — the bundled Google API client will not
   load on older PHP. Check your version with `php -v` or in XAMPP's
   control panel, and switch versions if needed.
5. In Google Cloud Console, set the OAuth client's "Authorized redirect
   URI" to exactly match `GOOGLE_REDIRECT_URI` in your `.env`.
6. If Google login is not working, check the PHP error log, OAuth redirect URI,
   enabled PHP extensions, and the values in your local `.env` file.

## Security note

The real `.env` file, Composer `vendor/` dependencies, and uploaded runtime
files under `uploads/` are excluded from Git. `config.php` contains application
configuration code and reads private values from `.env`; do not put credentials
directly in PHP source. If a Google Client Secret has ever been shared outside
your own machine, rotate it in Google Cloud Console.
