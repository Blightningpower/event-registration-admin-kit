<?php
ini_set('display_errors', 0);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// Formdata
$groep         = $_POST['groep'] ?? '';
$jaarAnders    = $_POST['jaarAnders'] ?? '';
$voornaamDeelnemer = $_POST['voornaamDeelnemer'] ?? '';
$tussenvoegselsDeelnemer = $_POST['tussenvoegselsDeelnemer'] ?? '';
$achternaamDeelnemer = $_POST['achternaamDeelnemer'] ?? '';
$telDeelnemer  = $_POST['telefoonnummerDeelnemer'] ?? '';
$emailDeelnemer= $_POST['emailDeelnemer'] ?? '';
$voornaamOuder = $_POST['voornaamOuder'] ?? '';
$tussenvoegselsOuder = $_POST['tussenvoegselsOuder'] ?? '';
$achternaamOuder = $_POST['achternaamOuder'] ?? '';
$telOuder      = $_POST['telefoonnummerOuder'] ?? '';
$emailOuder    = $_POST['emailOuder'] ?? '';
$iban          = $_POST['iBAN'] ?? '';

// Bepaal het correct jaar te tonen
$jaarShow   = ($groep === 'anders' || empty($groep)) ? $jaarAnders : $groep;

// Mail (pas $from en $mailto aan naar je eigen domein/mailbox)
$from    = 'no-reply@chalgado.com';
$mailto  = 'koptischekerkeindhoven@chalgado.com';
$subject = 'Inschrijving kerkkamp 2026';

$headers  = "From: Kerkkamp <{$from}>\r\n";
if (!empty($emailDeelnemer)) {
  $headers .= "Reply-To: {$emailDeelnemer}\r\n";
}
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$txt = "Jaar: {$jaarShow}\n"
     . "Voornaam deelnemer: {$voornaamDeelnemer}\n"
     . "Tussenvoegsel deelnemer: {$tussenvoegselsDeelnemer}\n"
     . "Achternaam deelnemer: {$achternaamDeelnemer}\n"
     . "Tel deelnemer: {$telDeelnemer}\n"
     . "Email deelnemer: {$emailDeelnemer}\n"
     . "Voornaam ouder: {$voornaamOuder}\n"
     . "Tussenvoegsel ouder: {$tussenvoegselsOuder}\n"
     . "Achternaam ouder: {$achternaamOuder}\n"
     . "Tel ouder: {$telOuder}\n"
     . "Email ouder: {$emailOuder}\n"
     . "IBAN: {$iban}\n";

$ok = mail($mailto, $subject, $txt, $headers);
if (!$ok) {
  error_log('Mail verzenden mislukt via mail()');
  http_response_code(500);
  exit('Mail verzenden mislukt. Probeer later opnieuw.');
}

// Opslaan in JSON
$file  = __DIR__ . '/inschrijvingen.json';
$items = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
$items[] = [
  'timestamp'              => date('c'),
  'jaar'                   => $groep,
  'jaarAnders'             => $jaarAnders,
  'voornaamDeelnemer'      => $voornaamDeelnemer,
  'tussenvoegselsDeelnemer'=> $tussenvoegselsDeelnemer,
  'achternaamDeelnemer'    => $achternaamDeelnemer,
  'telefoonnummerDeelnemer'=> $telDeelnemer,
  'emailDeelnemer'         => $emailDeelnemer,
  'voornaamOuder'          => $voornaamOuder,
  'tussenvoegselsOuder'    => $tussenvoegselsOuder,
  'achternaamOuder'        => $achternaamOuder,
  'telefoonnummerOuder'    => $telOuder,
  'emailOuder'             => $emailOuder,
  'iban'                   => $iban,
  'status'                 => 'pending'
];
file_put_contents($file, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

header('Location: ../html/payment.html');
exit;
?>