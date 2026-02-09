<?php
$file = __DIR__ . '/inschrijvingen.json';
$items = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];

// Status updaten of verwijderen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = (int) $_POST['id'];

  if (isset($items[$id])) {
    if (isset($_POST['status'])) {
      // Status updaten
      $items[$id]['status'] = $_POST['status'];
    } elseif (isset($_POST['delete'])) {
      // Verwijderen
      array_splice($items, $id, 1);
    }
    file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
  }
}

// Tellers
$accepted = count(array_filter($items, fn($row) => ($row['status'] ?? 'pending') === 'accepted'));
$pending = count(array_filter($items, fn($row) => ($row['status'] ?? 'pending') === 'pending'));
?>
<!DOCTYPE html>
<html lang="nl">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Overzicht inschrijvingen</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f5f5f5;
      padding: 20px;
    }

    .container {
      margin: 0 auto;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      min-width: fit-content;
    }

    h1 {
      color: #333;
      margin-bottom: 20px;
      text-align: center;
    }

    .stats {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .stat-box {
      flex: 1;
      min-width: 150px;
      padding: 15px;
      border-radius: 6px;
      text-align: center;
      color: white;
    }

    .stat-accepted {
      background: #4CAF50;
    }

    .stat-pending {
      background: #ff9800;
    }

    .stat-box strong {
      font-size: 24px;
      display: block;
    }

    .stat-box span {
      font-size: 12px;
      display: block;
      margin-top: 5px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      overflow-x: auto;
      margin: 20px auto auto auto;
    }

    th {
      background: #2196F3;
      color: white;
      padding: 12px;
      text-align: left;
      font-weight: bold;
    }

    td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }

    tr:hover {
      background-color: #f9f9f9;
    }

    tr:nth-child(even) {
      background-color: #fafafa;
    }

    .status-accepted {
      color: #4CAF50;
      font-weight: bold;
    }

    .status-pending {
      color: #ff9800;
      font-weight: bold;
    }

    .btn-group {
      display: flex;
      gap: 5px;
    }

    button {
      padding: 6px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 12px;
    }

    .btn-accept {
      background: #4CAF50;
      color: white;
    }

    .btn-accept:hover {
      background: #45a049;
    }

    .btn-delete {
      background: #f44336;
      color: white;
    }

    .btn-delete:hover {
      background: #da190b;
    }
  </style>
</head>

<body>
  <div class="container">
    <h1>📋 Overzicht inschrijvingen Kerkkamp 2026</h1>

    <div class="stats">
      <div class="stat-box stat-accepted">
        <strong><?= $accepted ?></strong>
        <span>Geaccepteerd</span>
      </div>
      <div class="stat-box stat-pending">
        <strong><?= $pending ?></strong>
        <span>In afwachting</span>
      </div>
    </div>

    <?php if (empty($items)): ?>
      <p style="text-align: center; color: #999; padding: 40px;">Nog geen inschrijvingen ontvangen.</p>
    <?php else: ?>
      <table>
        <tr>
          <th>Datum/tijd</th>
          <th style="text-align: center;">Jaar</th>
          <th>Naam deelnemer</th>
          <th>Tel deelnemer</th>
          <th>Email deelnemer</th>
          <th>Naam ouder</th>
          <th>Status</th>
          <th>Acties</th>
        </tr>
        <?php foreach ($items as $idx => $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['timestamp']) ?></td>
            <td style="text-align: center;">
              <?php
              // Toon jaarAnders als jaar = 'anders', anders toon jaar
              $jaarDisplay = ($row['jaar'] === 'anders' || empty($row['jaar'])) 
                ? ($row['jaarAnders'] ?? '') 
                : $row['jaar'];
              echo htmlspecialchars($jaarDisplay);
            ?></td>
            <td>
              <?php
              $naamDeelnemer = trim(($row['voornaamDeelnemer'] ?? '') . ' ' . ($row['tussenvoegselsDeelnemer'] ?? '') . ' ' . ($row['achternaamDeelnemer'] ?? ''));
              echo htmlspecialchars($naamDeelnemer);
            ?></td>
            <td><?= htmlspecialchars($row['telefoonnummerDeelnemer'] ?? '') ?></td>
            <td><?= htmlspecialchars($row['emailDeelnemer'] ?? '') ?></td>
            <td>
              <?php
              $naamOuder = trim(($row['voornaamOuder'] ?? '') . ' ' . ($row['tussenvoegselsOuder'] ?? '') . ' ' . ($row['achternaamOuder'] ?? ''));
              echo htmlspecialchars($naamOuder);
            ?></td>
            <td>
              <?php
              $status = $row['status'] ?? 'pending';
              $statusTxt = match ($status) {
                'accepted' => '✓ Geaccepteerd',
                default => '⏳ In afwachting'
              };
              $statusClass = 'status-' . $status;
              ?>
              <span class="<?= $statusClass ?>"><?= $statusTxt ?></span>
            </td>
            <td>
              <div class="btn-group">
                <?php if ($status !== 'accepted'): ?>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="id" value="<?= $idx ?>">
                  <input type="hidden" name="status" value="accepted">
                  <button type="submit" class="btn-accept">Accepteer</button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Weet je zeker dat je deze inschrijving wilt verwijderen?');">
                  <input type="hidden" name="id" value="<?= $idx ?>">
                  <input type="hidden" name="delete" value="1">
                  <button type="submit" class="btn-delete">🗑️ Verwijderen</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</body>

</html>