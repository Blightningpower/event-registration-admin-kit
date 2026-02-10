# Event Registration Admin Kit

Lightweight PHP event registration kit with email notifications and an admin dashboard to manage signups (accept/reject/delete) and track payment status.

## Requirements

- PHP 8.0+ recommended
- A server that can send email via `mail()` (shared hosting usually works)

> If `mail()` is not available on your host, you can replace it later with SMTP (PHPMailer) or an API provider.

## Quick Start

### 1) Configure

Copy the example config file:

- `php/config.example.php` → `php/config.php`

Edit `php/config.php`:

- `APP_FROM_EMAIL` — sender address (must be a valid domain email, e.g., `noreply@yourdomain.com`)
- `APP_FROM_NAME` — sender name
- `APP_NOTIFY_EMAIL` — where registrations should be sent
- `APP_EVENT_SUBJECT` — email subject
- `APP_PAYMENT_PAGE` — where to redirect after submit
- `APP_ADMIN_KEY` — admin key for dashboard protection (set to `''` to disable)

Important: add `php/config.php` to `.gitignore` (it may contain sensitive data and should not be committed).

### 2) Set File Permissions

The `php/` folder must be writable so the app can create and update `registrations.json`.

How to recognize permission issues:

- You get a `500` error
- You see errors like `file_put_contents(): Failed to open stream: Permission denied`

Fix (typical Linux hosting via SSH):

```bash
chmod 755 php/
touch php/registrations.json
chmod 664 php/registrations.json
