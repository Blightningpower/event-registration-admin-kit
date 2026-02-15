<?php
declare(strict_types=1);

ini_set('display_errors', '0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

require_once __DIR__ . '/php/config.php';

/**
 * Helpers
 */
function clean_string(string $value, int $maxLen = 200): string {
  $value = trim($value);
  $value = str_replace(["\r", "\n"], ' ', $value); // prevent header injection / formatting issues
  if (mb_strlen($value) > $maxLen) {
    $value = mb_substr($value, 0, $maxLen);
  }
  return $value;
}

function clean_email(string $value): string {
  $value = clean_string($value, 254);
  return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
}

function clean_phone(string $value): string {
  $value = clean_string($value, 50);
  return preg_replace('/[^0-9+\s\-().]/', '', $value) ?? '';
}

function clean_iban(string $value): string {
  $value = strtoupper(clean_string($value, 34));
  $value = preg_replace('/\s+/', '', $value) ?? '';
  return $value;
}

function clean_numeric_string(string $value, int $maxLen = 2): string {
  $value = trim($value);
  $value = preg_replace('/[^\d]/', '', $value) ?? '';
  if (strlen($value) > $maxLen) {
    $value = substr($value, 0, $maxLen);
  }
  return $value;
}

function post_value(string $key): string {
  $v = $_POST[$key] ?? '';
  return is_string($v) ? $v : '';
}

/**
 * Fields
 */
$group        = clean_string(post_value('group'));
$customGroup  = clean_numeric_string(post_value('custom_group'));

$participantFirstName  = clean_string(post_value('participant_first_name'));
$participantMiddleName = clean_string(post_value('participant_middle_name'));
$participantLastName   = clean_string(post_value('participant_last_name'));
$participantPhone      = clean_phone(post_value('participant_phone'));
$participantEmail      = clean_email(post_value('participant_email'));

$guardianFirstName  = clean_string(post_value('guardian_first_name'));
$guardianMiddleName = clean_string(post_value('guardian_middle_name'));
$guardianLastName   = clean_string(post_value('guardian_last_name'));
$guardianPhone      = clean_phone(post_value('guardian_phone'));
$guardianEmail      = clean_email(post_value('guardian_email'));

$iban = clean_iban(post_value('iban'));

/**
 * Derived values
 */
$isOtherGroup = ($group === 'other');
$groupDisplay = ($isOtherGroup || $group === '') ? $customGroup : $group;

/**
 * Basic validation
 */
$errors = [];

if ($group === '') $errors[] = 'Group/grade is required.';
if ($isOtherGroup && $customGroup === '') $errors[] = 'Custom group/grade is required.';
if ($isOtherGroup && !preg_match('/^\d{1,2}$/', $customGroup)) {
  $errors[] = 'Custom group/grade must be 1-2 digits.';
}
if ($groupDisplay === '') $errors[] = 'Group/grade display value is invalid.';

if ($participantFirstName === '') $errors[] = 'Participant first name is required.';
if ($participantLastName === '') $errors[] = 'Participant last name is required.';
if ($participantPhone === '') $errors[] = 'Participant phone is required.';
if ($participantEmail === '') $errors[] = 'Participant email is required.';

if ($guardianFirstName === '') $errors[] = 'Parent/guardian first name is required.';
if ($guardianLastName === '') $errors[] = 'Parent/guardian last name is required.';
if ($guardianEmail === '') $errors[] = 'Parent/guardian email is required.';

if ($iban === '') $errors[] = 'IBAN is required.';

if (!empty($errors)) {
  http_response_code(422);
  exit("Validation error:\n- " . implode("\n- ", $errors));
}

/**
 * Email
 */
$subject = APP_EVENT_SUBJECT;

$fromEmail = APP_FROM_EMAIL;
$fromName  = APP_FROM_NAME;
$toEmail   = APP_NOTIFY_EMAIL;

$headers = [];
$headers[] = 'From: ' . $fromName . " <{$fromEmail}>";

$replyTo = $participantEmail !== '' ? $participantEmail : $guardianEmail;
if ($replyTo !== '') $headers[] = "Reply-To: {$replyTo}";

$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'Content-Transfer-Encoding: 8bit';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$message =
  "New registration received\n"
  . "-------------------------\n"
  . "Group/grade: {$groupDisplay}\n\n"
  . "Participant\n"
  . "  First name: {$participantFirstName}\n"
  . "  Middle name: {$participantMiddleName}\n"
  . "  Last name: {$participantLastName}\n"
  . "  Phone: {$participantPhone}\n"
  . "  Email: {$participantEmail}\n\n"
  . "Parent/Guardian\n"
  . "  First name: {$guardianFirstName}\n"
  . "  Middle name: {$guardianMiddleName}\n"
  . "  Last name: {$guardianLastName}\n"
  . "  Phone: {$guardianPhone}\n"
  . "  Email: {$guardianEmail}\n\n"
  . "IBAN: {$iban}\n";

$sent = mail($toEmail, $subject, $message, implode("\r\n", $headers));
if (!$sent) {
  error_log('Registration email failed (mail()).');
  http_response_code(500);
  exit('Email could not be sent. Please try again later.');
}

/**
 * Persist registration (JSON)
 */
$dataFile = __DIR__ . '/registrations.json';

$items = [];
if (file_exists($dataFile)) {
  $raw = file_get_contents($dataFile);
  $items = $raw ? (json_decode($raw, true) ?: []) : [];
}

$registrationId = bin2hex(random_bytes(8));

$items[] = [
  'id' => $registrationId,
  'created_at' => date('c'),

  'group' => $group,
  'custom_group' => $customGroup,
  'group_display' => $groupDisplay,

  'participant' => [
    'first_name' => $participantFirstName,
    'middle_name' => $participantMiddleName,
    'last_name' => $participantLastName,
    'phone' => $participantPhone,
    'email' => $participantEmail,
  ],

  'guardian' => [
    'first_name' => $guardianFirstName,
    'middle_name' => $guardianMiddleName,
    'last_name' => $guardianLastName,
    'phone' => $guardianPhone,
    'email' => $guardianEmail,
  ],

  'iban' => $iban,

  'status' => 'pending',          // pending | accepted | rejected
  'payment_status' => 'unpaid',   // unpaid | paid | refunded
  'payment_note' => '',
];

file_put_contents(
  $dataFile,
  json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
  LOCK_EX
);

/**
 * Redirect
 */
header('Location: ' . APP_PAYMENT_PAGE);
exit;