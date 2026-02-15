<?php
declare(strict_types=1);

/**
 * Copy this file to: config.php
 * Then replace the placeholder values.
 */

define('APP_FROM_EMAIL', 'no-reply@example.com'); // From
define('APP_FROM_NAME', 'Event Registration');
define('APP_NOTIFY_EMAIL', 'organizer@example.com'); // To

define('APP_EVENT_SUBJECT', 'New Event Registration');
define('APP_PAYMENT_PAGE', '../html/payment.html');

/**
 * Optional: protect admin page with a simple key in the URL:
 * /php/overview_registrations.php?key=YOUR_KEY
 * Set to '' (empty) to disable.
 */
define('APP_ADMIN_KEY', 'CHANGE_ME_TO_A_SECRET_KEY');
