<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Alleen POST accepteren
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode niet toegestaan']);
    exit;
}

// Ontvanger
$to = 'info@borismultiservice.nl';

// Invoer ophalen en opschonen
function clean($val) {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$voornaam  = clean($_POST['voornaam']  ?? '');
$achternaam = clean($_POST['achternaam'] ?? '');
$email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telefoon  = clean($_POST['telefoon']  ?? '');
$dienst    = clean($_POST['dienst']    ?? '');
$bericht   = clean($_POST['bericht']  ?? '');

// Validatie
if (!$voornaam || !$email || !$bericht) {
    echo json_encode(['success' => false, 'error' => 'Verplichte velden ontbreken']);
    exit;
}

$naam = $voornaam . ' ' . $achternaam;

// Onderwerp
$subject = "Nieuw contactverzoek via borismultiservice.nl – $naam";

// E-mail inhoud (HTML)
$html_body = "
<!DOCTYPE html>
<html lang='nl'>
<head>
<meta charset='UTF-8'>
<style>
  body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
  .wrap { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 4px; overflow: hidden; }
  .header { background: #2f2e2e; padding: 32px 36px; }
  .header h1 { color: #e8b84b; font-size: 22px; margin: 0; letter-spacing: 2px; }
  .header p { color: #a09890; font-size: 12px; margin: 6px 0 0; }
  .body { padding: 32px 36px; }
  .row { margin-bottom: 20px; border-bottom: 1px solid #f0f0f0; padding-bottom: 16px; }
  .row:last-child { border-bottom: none; }
  .label { font-size: 11px; letter-spacing: 2px; text-transform: uppercase; color: #a09890; margin-bottom: 5px; }
  .value { font-size: 15px; color: #2f2e2e; line-height: 1.6; }
  .bericht-box { background: #fafafa; border-left: 3px solid #e8b84b; padding: 16px; border-radius: 0 4px 4px 0; }
  .footer { background: #2f2e2e; padding: 18px 36px; text-align: center; }
  .footer p { color: #a09890; font-size: 11px; margin: 0; }
  .footer span { color: #e8b84b; }
</style>
</head>
<body>
<div class='wrap'>
  <div class='header'>
    <h1>Boris MultiService</h1>
    <p>Nieuw contactverzoek via de website</p>
  </div>
  <div class='body'>
    <div class='row'>
      <div class='label'>Naam</div>
      <div class='value'>$naam</div>
    </div>
    <div class='row'>
      <div class='label'>E-mailadres</div>
      <div class='value'><a href='mailto:$email' style='color:#e8b84b;'>$email</a></div>
    </div>
    " . ($telefoon ? "
    <div class='row'>
      <div class='label'>Telefoonnummer</div>
      <div class='value'>$telefoon</div>
    </div>" : "") . "
    " . ($dienst ? "
    <div class='row'>
      <div class='label'>Gevraagde dienst</div>
      <div class='value'>$dienst</div>
    </div>" : "") . "
    <div class='row'>
      <div class='label'>Bericht</div>
      <div class='value'>
        <div class='bericht-box'>$bericht</div>
      </div>
    </div>
  </div>
  <div class='footer'>
    <p>Verstuurd via <span>borismultiservice.nl</span></p>
  </div>
</div>
</body>
</html>
";

// Headers
$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Boris MultiService Website <noreply@borismultiservice.nl>\r\n";
$headers .= "Reply-To: $naam <$email>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Versturen
$sent = mail($to, $subject, $html_body, $headers);

if ($sent) {
    // Stuur bevestigingsmail naar de klant
    $bevestiging_subject = "Bedankt voor uw bericht – Boris MultiService";
    $bevestiging_html = "
    <!DOCTYPE html>
    <html lang='nl'>
    <head><meta charset='UTF-8'>
    <style>
      body { font-family: Arial, sans-serif; background: #f5f5f5; margin:0; padding:0; }
      .wrap { max-width:600px; margin:30px auto; background:#fff; border-radius:4px; overflow:hidden; }
      .header { background:#2f2e2e; padding:32px 36px; }
      .header h1 { color:#e8b84b; font-size:22px; margin:0; letter-spacing:2px; }
      .body { padding:32px 36px; color:#333; font-size:15px; line-height:1.7; }
      .body strong { color:#2f2e2e; }
      .gold { color:#e8b84b; }
      .footer { background:#2f2e2e; padding:18px 36px; text-align:center; }
      .footer p { color:#a09890; font-size:11px; margin:0; }
    </style>
    </head>
    <body>
    <div class='wrap'>
      <div class='header'><h1>Boris MultiService</h1></div>
      <div class='body'>
        <p>Beste <strong>$voornaam</strong>,</p>
        <p>Hartelijk dank voor uw bericht! Wij hebben uw contactverzoek goed ontvangen en nemen zo spoedig mogelijk contact met u op.</p>
        <p>Heeft u een dringende vraag? Neem dan direct contact op via <a href='mailto:info@borismultiservice.nl' class='gold'>info@borismultiservice.nl</a>.</p>
        <p>Met vriendelijke groet,<br><strong>Het team van Boris MultiService</strong><br><span class='gold'>borismultiservice.nl</span></p>
      </div>
      <div class='footer'><p>© 2026 Boris MultiService · Leeuwarden</p></div>
    </div>
    </body></html>
    ";
    $bevestiging_headers  = "MIME-Version: 1.0\r\n";
    $bevestiging_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $bevestiging_headers .= "From: Boris MultiService <info@borismultiservice.nl>\r\n";
    mail($email, $bevestiging_subject, $bevestiging_html, $bevestiging_headers);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Mail kon niet worden verstuurd']);
}
?>
