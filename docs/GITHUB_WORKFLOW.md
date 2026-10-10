# GitHub workflow

- `main`: integrated project branch.
- `feature/database`: database schema and connection configuration.
- `feature/authentication`: login, registration, session, and Google sign-in files.
- `feature/dashboard`: main application, request handlers, shared functions, styling, activity log, and scheduled reminders.
- `feature/reports`: accomplishment reports, report editor, and activity export.
- `feature/pwa`: password setup and development notes.

Use feature branches for future changes and merge them into `main` after testing.
Keep `.env`, `vendor/`, and uploaded runtime content out of version control. Install Composer dependencies with `composer install` after cloning.
