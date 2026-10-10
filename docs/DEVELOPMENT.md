# SBO Nexus Development Notes

## Current milestone — Authentication security

- Registration is restricted to official `@nbsc.edu.ph` email addresses.
- Login is restricted to `@nbsc.edu.ph` email addresses.
- Public registration creates student accounts only.
- Officer and adviser roles must be assigned by an authorized administrator.
- All state-changing POST requests use a server-side CSRF token.
- Server-side role checks protect event, attendance, poll, feedback, and profile actions.
- Passwords continue to use PHP `password_hash()` / `password_verify()`.
- Database credentials are loaded from environment variables when available.
- `config.php`, `.env`, and uploaded user content are excluded from Git tracking.
- Public schema no longer contains sample user accounts or sample personal records.

## Next milestones

1. Add secure officer/adviser role management.
2. Add CSRF protection to state-changing forms.
3. Improve session and authentication controls.
4. Finish responsive mobile UI.
5. Add PWA manifest and service worker.
6. Prepare production database and hosting.
7. Deploy and test on desktop and mobile.

## Step 6 — Professional Responsive UI
- Improved desktop spacing and content proportions.
- Added tablet and mobile responsive layouts.
- Added touch-friendly controls and safer mobile forms.
- Improved mobile dashboard cards, event lists, statistics, schedules, tables, and modal sizing.
- Prevented common horizontal-overflow issues on small screens.
- Prepared the existing interface for the next PWA/installable mobile stage.
