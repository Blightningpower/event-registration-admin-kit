# Event Registration Admin Kit

Lightweight PHP event registration kit with email notifications and an admin dashboard to manage signups (accept/reject/delete) and track payment status.

## Requirements

- PHP 8.0+ recommended
- Web hosting that can send email via `mail()` (shared hosting often works)

> If `mail()` is not available on your host, you can replace it later with SMTP (PHPMailer) or an email API provider.

## Quick Start

### 1) Configure

Copy the example config file:

```bash
cp php/config.example.php php/config.php
```

Edit `php/config.php`:

- `APP_FROM_EMAIL` — sender address (use a domain email, e.g. `noreply@yourdomain.com`)
- `APP_FROM_NAME` — sender name
- `APP_NOTIFY_EMAIL` — inbox that receives new registrations
- `APP_EVENT_SUBJECT` — email subject
- `APP_PAYMENT_PAGE` — redirect location after submit (e.g. `html/payment.html`)
- `APP_ADMIN_KEY` — admin key for dashboard protection (set to `''` to disable)

**Important:** keep `php/config.php` out of version control (it may contain sensitive values).
This repository includes a `.gitignore` that already excludes it.

### 2) Add Your Payment Link (payment.html)

After a user submits the form, they are redirected to the payment page.

Open:

- `html/payment.html`

Find the payment button/link and replace the URL with your own payment request link (e.g. Tikkie, ING Pay Request, Mollie, etc.):

```html
<a
  href="https://example.com/your-payment-link"
  id="payment-button"
  target="_blank"
  rel="noopener noreferrer"
>
  Pay Now
</a>
```

That's it — the app does not process payments itself; it simply redirects users to your external payment link.

### 3) File Permissions (hosting)

The app stores registrations in `registrations.json` (in the project root).
Your hosting must allow PHP to write to this file.

#### How to recognize permission issues

- You get a `500` error
- You see `file_put_contents(): Failed to open stream: Permission denied`
- The form submits but no data is saved

### Typical fix (Linux hosting via SSH)**

```bash
chmod 664 registrations.json
```

If your host requires folder permissions too, you can also ensure the project folder is writable for the web user.

### 4) Upload to Your Server

Upload the entire project to your web hosting (FTP / cPanel File Manager).

- Place `index.html` in your public web root
- Keep the folder structure intact (`css/`, `html/`, `php/`, `assets/`)

### 5) Test the Registration Form

1. Open your website (e.g. `https://yourdomain.com/`)
2. Fill out the form and submit
3. Expected:
   - An email arrives at `APP_NOTIFY_EMAIL`
   - A new entry is appended to `registrations.json`
   - The user is redirected to the payment page (`html/payment.html`)

### 6) Admin Dashboard

Open:

```text
https://yourdomain.com/php/overview_registrations.php
```

If you set `APP_ADMIN_KEY`, add it:

```text
https://yourdomain.com/php/overview_registrations.php?key=YOUR_KEY
```

Dashboard features:

- Accept / reject registrations
- Mark payment as `paid` / `unpaid` / `refunded`
- Delete registrations
- View basic statistics

## File Structure

```text
├── index.html
├── submit_registration.php          # form handler (sends email, saves data)
├── registrations.json               # JSON storage (created/updated by the app)
├── css/
│   ├── style.css
│   └── admin.css
├── html/
│   └── payment.html                 # payment instructions + payment link
├── php/
│   ├── config.example.php
│   ├── config.php                   # created by you (do not commit)
│   └── overview_registrations.php   # admin dashboard
└── assets/
    └── branding/
        └── favicon_io/
```

## Security Notes

- **Do not commit** `php/config.php` or `registrations.json`.
- Use a strong `APP_ADMIN_KEY` (or implement real authentication if you want to harden it).
- `registrations.json` contains personal data. Recommended:
  - Place the file outside the web root (best), **or**
  - Block direct access to it (see below).

### Block direct access to registrations.json (Apache)

Create a `.htaccess` in the same folder as `registrations.json` (project root):

```apache
<Files "registrations.json">
  Require all denied
</Files>
```

> Note: This blocks only the JSON file, not the PHP dashboard.

## Troubleshooting

### Emails not arriving

- Check spam folder
- Make sure `APP_FROM_EMAIL` is a real domain email
- Ask your host if `mail()` is supported/enabled
- Consider switching to SMTP (PHPMailer) if needed

### Data not saving

- Check write permissions on `registrations.json`
- Check server error logs
- Temporarily enable errors for debugging (then turn off again)

## Customization

- Payment page: edit `html/payment.html` and replace the payment link
- Form fields: update `index.html` and keep POST keys aligned with `submit_registration.php`
- Styling:
  - `css/style.css` for public pages
  - `css/admin.css` for the dashboard
- Email template: edit the message in `submit_registration.php`

## License

Starter kit for personal/educational use. Modify freely.
