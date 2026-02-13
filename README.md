# Event Registration Admin Kit

Lightweight PHP event registration kit with email notifications and an admin dashboard to manage signups (accept/reject/delete) and track payment status.

## Requirements

- PHP 8.0+ recommended
- A server that can send email via `mail()` (shared hosting usually works)

> If `mail()` is not available on your host, you can replace it later with SMTP (PHPMailer) or an API provider.

## Quick Start

### 1) Configure

Copy the example config file:

```bash
cp php/config.example.php php/config.php
```

Edit [php/config.php](php/config.php):

- `APP_FROM_EMAIL` — sender address (must be a valid domain email, e.g., `noreply@yourdomain.com`)
- `APP_FROM_NAME` — sender name
- `APP_NOTIFY_EMAIL` — where registrations should be sent
- `APP_EVENT_SUBJECT` — email subject
- `APP_PAYMENT_PAGE` — where to redirect after submit
- `APP_ADMIN_KEY` — admin key for dashboard protection (set to `''` to disable)

**Important:** Add `php/config.php` to [.gitignore](.gitignore) (it may contain sensitive data and should not be committed).

### 2) Set File Permissions

The `php/` folder must be writable so the app can create and update `registrations.json`.

#### How to recognize permission issues

- You get a `500` error
- You see errors like `file_put_contents(): Failed to open stream: Permission denied`
- The registration form submits but data is not saved

#### Fix (typical Linux hosting via SSH)

```bash
chmod 755 php/
touch php/registrations.json
chmod 664 php/registrations.json
```

#### Alternative (via FTP or cPanel File Manager)

1. Right-click on the `php/` folder
2. Select "Change Permissions" or "File Permissions"
3. Set folder permissions to `755` (rwxr-xr-x)
4. Create an empty `registrations.json` file inside `php/`
5. Set file permissions to `664` (rw-rw-r--)

### 3) Upload to Your Server

Upload all files to your web hosting via FTP or cPanel File Manager:

- Place [index.html](index.html) in your public web root
- Keep the folder structure intact (css/, html/, php/, assets/)

### 4) Test the Registration Form

1. Open your website in a browser (e.g., `https://yourdomain.com/`)
2. Fill out the registration form
3. Submit and verify:
   - You receive an email at `APP_NOTIFY_EMAIL`
   - A new entry appears in `php/registrations.json`
   - You are redirected to the payment page

### 5) Access the Admin Dashboard

Navigate to:

```text
https://yourdomain.com/php/overview_registrations.php
```

If you set an `APP_ADMIN_KEY`, add it to the URL:

```text
https://yourdomain.com/php/overview_registrations.php?key=YOUR_KEY
```

From here you can:

- Accept or reject registrations
- Mark payments as paid/unpaid/refunded
- Delete registrations
- View statistics

## File Structure

```text
├── index.html                      # Main registration form
├── submit_registration.php          # Form handler (sends email, saves data)
├── html/
│   └── payment.html                # Payment instructions page
├── php/
│   ├── config.example.php          # Example configuration
│   ├── config.php                  # Your actual config (not in git)
│   ├── overview_registrations.php  # Admin dashboard
│   └── registrations.json          # Stored registrations (auto-created)
├── css/
│   ├── style.css                   # Main styles
│   └── admin.css                   # Admin dashboard styles
└── assets/
    └── branding/
        └── favicon_io/             # Favicon files
```

## Security Notes

1. **Protect your config file:** Never commit [php/config.php](php/config.php) to version control
2. **Admin dashboard:** Use a strong `APP_ADMIN_KEY` or implement proper authentication
3. **File permissions:** Make sure `php/registrations.json` is not publicly downloadable (deny access via `.htaccess` if needed)
4. **Email validation:** The app performs basic validation, but always verify important data manually

## Troubleshooting

### Emails not arriving?

- Check your spam folder
- Verify `APP_FROM_EMAIL` is a valid email from your domain
- Test if your hosting supports `mail()` (ask your provider)
- Consider using SMTP (PHPMailer) if `mail()` doesn't work

### Data not saving?

- Check file permissions on `php/` folder and `registrations.json`
- Look for errors in your server's error log
- Enable error display temporarily: `ini_set('display_errors', '1');` in [submit_registration.php](submit_registration.php)

### 500 Internal Server Error?

- Check file permissions
- Check PHP version (must be 8.0+)
- Review server error logs for details

## Customization

- **Payment page:** Edit [html/payment.html](html/payment.html) to add your payment link (Tikkie, Mollie, etc.)
- **Form fields:** Modify [index.html](index.html) to add/remove fields
- **Styling:** Adjust colors and layout in [css/style.css](css/style.css)
- **Email template:** Edit the message format in [submit_registration.php](submit_registration.php)

## License

This is a simple starter kit for personal/educational use. Feel free to modify as needed.
