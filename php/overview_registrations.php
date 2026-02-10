<?php
declare(strict_types=1);

ini_set('display_errors', '0');

require_once __DIR__ . '/config.php';

/**
 * Very simple admin gate (optional but recommended).
 * Add ?key=YOUR_ADMIN_KEY to the URL, or set APP_ADMIN_KEY='' to disable.
 */
if (defined('APP_ADMIN_KEY') && APP_ADMIN_KEY !== '') {
  $key = $_GET['key'] ?? '';
  if (!hash_equals(APP_ADMIN_KEY, (string) $key)) {
    http_response_code(403);
    exit('Forbidden');
  }
}

$dataFile = __DIR__ . '/registrations.json';
$items = [];

if (file_exists($dataFile)) {
  $raw = file_get_contents($dataFile);
  $items = $raw ? (json_decode($raw, true) ?: []) : [];
}

/**
 * Find index by id
 */
function find_index_by_id(array $items, string $id): int
{
  foreach ($items as $i => $row) {
    if (($row['id'] ?? '') === $id)
      return $i;
  }
  return -1;
}

/**
 * Update / delete actions
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (string) ($_POST['id'] ?? '');
  $action = (string) ($_POST['action'] ?? '');

  if ($id === '' || $action === '') {
    http_response_code(400);
    exit('Bad Request');
  }

  $idx = find_index_by_id($items, $id);
  if ($idx === -1) {
    http_response_code(404);
    exit('Registration not found.');
  }

  if ($action === 'set_status') {
    $status = (string) ($_POST['status'] ?? 'pending');
    $allowed = ['pending', 'accepted', 'rejected'];
    if (!in_array($status, $allowed, true)) {
      http_response_code(422);
      exit('Invalid status.');
    }
    $items[$idx]['status'] = $status;
  }

  if ($action === 'set_payment') {
    $payment = (string) ($_POST['payment_status'] ?? 'unpaid');
    $allowed = ['unpaid', 'paid', 'refunded'];
    if (!in_array($payment, $allowed, true)) {
      http_response_code(422);
      exit('Invalid payment status.');
    }
    $items[$idx]['payment_status'] = $payment;
  }

  if ($action === 'delete') {
    array_splice($items, $idx, 1);
  }

  file_put_contents(
    $dataFile,
    json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
    LOCK_EX
  );

  // Redirect back (preserve admin key if used)
  $redirect = $_SERVER['PHP_SELF'];
  if (defined('APP_ADMIN_KEY') && APP_ADMIN_KEY !== '' && isset($_GET['key'])) {
    $redirect .= '?key=' . urlencode((string) $_GET['key']);
  }
  header('Location: ' . $redirect);
  exit;
}

/**
 * Stats
 */
$accepted = 0;
$pending = 0;
$paid = 0;

foreach ($items as $row) {
  $status = $row['status'] ?? 'pending';
  if ($status === 'accepted')
    $accepted++;
  if ($status === 'pending')
    $pending++;

  $payment = $row['payment_status'] ?? 'unpaid';
  if ($payment === 'paid')
    $paid++;
}

function e(string $v): string
{
  return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function full_name(array $person): string
{
  $first = trim((string) ($person['first_name'] ?? ''));
  $middle = trim((string) ($person['middle_name'] ?? ''));
  $last = trim((string) ($person['last_name'] ?? ''));
  return trim($first . ' ' . $middle . ' ' . $last);
}

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <title>Admin Dashboard - Registrations</title>

  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/admin.css" />

</head>

<body>
  <main>
    <div class="admin-header">
      <div>
        <h1>Registrations</h1>
        <p class="muted">Manage signups, acceptance, and payment status.</p>
      </div>
    </div>

    <div class="stats">
      <div class="stat">
        <strong><?= (int) $accepted ?></strong>
        <span class="muted">Accepted</span>
      </div>
      <div class="stat">
        <strong><?= (int) $pending ?></strong>
        <span class="muted">Pending</span>
      </div>
      <div class="stat">
        <strong><?= (int) $paid ?></strong>
        <span class="muted">Paid</span>
      </div>
    </div>

    <?php if (empty($items)): ?>
      <p class="muted" style="padding: 18px 0;">No registrations yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th class="nowrap">Created</th>
            <th>Group/Grade</th>
            <th>Participant</th>
            <th>Participant Contact</th>
            <th>Parent/Guardian</th>
            <th>Status</th>
            <th>Payment</th>
            <th class="nowrap">Actions</th>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($items as $row): ?>
            <?php
            $id = (string) ($row['id'] ?? '');
            $created = (string) ($row['created_at'] ?? '');
            $groupDisplay = (string) ($row['group_display'] ?? ($row['group'] ?? ''));

            $participant = (array) ($row['participant'] ?? []);
            $guardian = (array) ($row['guardian'] ?? []);

            $status = (string) ($row['status'] ?? 'pending');
            $payment = (string) ($row['payment_status'] ?? 'unpaid');

            $participantName = full_name($participant);
            $guardianName = full_name($guardian);

            $participantPhone = (string) ($participant['phone'] ?? '');
            $participantEmail = (string) ($participant['email'] ?? '');
            ?>

            <tr>
              <td class="nowrap"><?= e($created) ?></td>
              <td><?= e($groupDisplay) ?></td>

              <td><?= e($participantName) ?></td>

              <td>
                <div><?= e($participantPhone) ?></div>
                <div class="muted"><?= e($participantEmail) ?></div>
              </td>

              <td><?= e($guardianName) ?></td>

              <td>
                <span class="pill <?= e($status) ?>">
                  <?= e(ucfirst($status)) ?>
                </span>
              </td>

              <td>
                <span class="pill <?= e($payment) ?>">
                  <?= e(strtoupper($payment)) ?>
                </span>
              </td>

              <td class="nowrap">
                <div class="actions">
                  <?php if ($status !== 'accepted'): ?>
                    <form method="post">
                      <input type="hidden" name="id" value="<?= e($id) ?>" />
                      <input type="hidden" name="action" value="set_status" />
                      <input type="hidden" name="status" value="accepted" />
                      <button class="btn green" type="submit">Accept</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($status !== 'rejected'): ?>
                    <form method="post">
                      <input type="hidden" name="id" value="<?= e($id) ?>" />
                      <input type="hidden" name="action" value="set_status" />
                      <input type="hidden" name="status" value="rejected" />
                      <button class="btn gray" type="submit">Reject</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($payment !== 'paid'): ?>
                    <form method="post">
                      <input type="hidden" name="id" value="<?= e($id) ?>" />
                      <input type="hidden" name="action" value="set_payment" />
                      <input type="hidden" name="payment_status" value="paid" />
                      <button class="btn primary" type="submit">Mark Paid</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($payment !== 'unpaid'): ?>
                    <form method="post">
                      <input type="hidden" name="id" value="<?= e($id) ?>" />
                      <input type="hidden" name="action" value="set_payment" />
                      <input type="hidden" name="payment_status" value="unpaid" />
                      <button class="btn" type="submit">Mark Unpaid</button>
                    </form>
                  <?php endif; ?>

                  <?php if ($payment !== 'refunded'): ?>
                    <form method="post">
                      <input type="hidden" name="id" value="<?= e($id) ?>" />
                      <input type="hidden" name="action" value="set_payment" />
                      <input type="hidden" name="payment_status" value="refunded" />
                      <button class="btn" type="submit">Mark Refunded</button>
                    </form>
                  <?php endif; ?>

                  <form method="post" onsubmit="return confirm('Delete this registration permanently?');">
                    <input type="hidden" name="id" value="<?= e($id) ?>" />
                    <input type="hidden" name="action" value="delete" />
                    <button class="btn danger" type="submit">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </main>
</body>

</html>