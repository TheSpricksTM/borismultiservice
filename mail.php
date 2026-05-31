<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Methode niet toegestaan']);
    exit;
}

function clean($val) {
    return htmlspecialchars(strip_tags(trim($val ?? '')), ENT_QUOTES, 'UTF-8');
}

$voornaam   = clean($_POST['voornaam']);
$achternaam = clean($_POST['achternaam']);
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$telefoon   = clean($_POST['telefoon']);
$dienst     = clean($_POST['dienst']);
$bericht    = clean($_POST['bericht']);

if (!$voornaam || !$email || !$bericht) {
    echo json_encode(['success' => false, 'error' => 'Verplichte velden ontbreken']);
    exit;
}

$naam = trim("$voornaam $achternaam");
$to   = 'info@borismultiservice.nl';

// ── Plain-text fallback (werkt altijd op Strato) ──
$subject_plain = "=?UTF-8?B?" . base64_encode("Nieuw contactverzoek – $naam") . "?=";

$telefoon_regel = $telefoon ? "Telefoon    : $telefoon\n" : "";
$dienst_regel   = $dienst   ? "Dienst      : $dienst\n"   : "";

$body_plain = "Nieuw contactverzoek via borismultiservice.nl\n"
            . str_repeat("-", 44) . "\n"
            . "Naam        : $naam\n"
            . "E-mail      : $email\n"
            . $telefoon_regel
            . $dienst_regel
            . str_repeat("-", 44) . "\n"
            . "Bericht:\n$bericht\n"
            . str_repeat("-", 44) . "\n";

// ── HTML versie ──
$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px">'
      . '<div style="max-width:580px;margin:auto;background:#fff;border-radius:4px;overflow:hidden">'
      . '<div style="background:#2f2e2e;padding:28px 32px"><h2 style="color:#e8b84b;margin:0;letter-spacing:2px;font-size:20px">BORIS MULTISERVICE</h2>'
      . '<p style="color:#a09890;margin:6px 0 0;font-size:12px">Nieuw contactverzoek via de website</p></div>'
      . '<div style="padding:28px 32px">'
      . '<table style="width:100%;border-collapse:collapse">'
      . '<tr><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#a09890;font-size:11px;text-transform:uppercase;letter-spacing:1px;width:120px">Naam</td>'
      . '<td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#2f2e2e;font-size:14px">' . $naam . '</td></tr>'
      . '<tr><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#a09890;font-size:11px;text-transform:uppercase;letter-spacing:1px">E-mail</td>'
      . '<td style="padding:10px 0;border-bottom:1px solid #f0f0f0"><a href="mailto:' . $email . '" style="color:#e8b84b">' . $email . '</a></td></tr>'
      . ($telefoon ? '<tr><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#a09890;font-size:11px;text-transform:uppercase;letter-spacing:1px">Telefoon</td><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#2f2e2e;font-size:14px">' . $telefoon . '</td></tr>' : '')
      . ($dienst   ? '<tr><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#a09890;font-size:11px;text-transform:uppercase;letter-spacing:1px">Dienst</td><td style="padding:10px 0;border-bottom:1px solid #f0f0f0;color:#2f2e2e;font-size:14px">' . $dienst . '</td></tr>' : '')
      . '</table>'
      . '<div style="margin-top:20px"><p style="color:#a09890;font-size:11px;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px">Bericht</p>'
      . '<div style="background:#fafafa;border-left:3px solid #e8b84b;padding:14px;font-size:14px;color:#2f2e2e;line-height:1.6">' . nl2br($bericht) . '</div></div>'
      . '</div>'
      . '<div style="background:#2f2e2e;padding:16px 32px;text-align:center"><p style="color:#a09890;font-size:11px;margin:0">Verstuurd via <span style="color:#e8b84b">borismultiservice.nl</span></p></div>'
      . '</div></body></html>';

// ── Multipart MIME (HTML + plain-text fallback) ──
$boundary = md5(uniqid(rand(), true));

$headers  = "From: =?UTF-8?B?" . base64_encode("Boris MultiService Website") . "?= <noreply@borismultiservice.nl>\r\n";
$headers .= "Reply-To: =?UTF-8?B?" . base64_encode($naam) . "?= <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

$message  = "--$boundary\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
$message .= chunk_split(base64_encode($body_plain)) . "\r\n";
$message .= "--$boundary\r\n";
$message .= "Content-Type: text/html; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: base64\r\n\r\n";
$message .= chunk_split(base64_encode($html)) . "\r\n";
$message .= "--$boundary--";

$sent = @mail($to, $subject_plain, $message, $headers);

// ── Bevestigingsmail naar klant ──
if ($sent) {
    $bev_subject = "=?UTF-8?B?" . base64_encode("Bedankt voor uw bericht – Boris MultiService") . "?=";
    $bev_plain   = "Beste $voornaam,\n\nBedankt voor uw bericht! Wij nemen zo spoedig mogelijk contact met u op.\n\nMet vriendelijke groet,\nBoris MultiService\ninfo@borismultiservice.nl\nborismultiservice.nl\n";
    $bev_html    = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f5f5f5;padding:20px">'
                 . '<div style="max-width:580px;margin:auto;background:#fff;border-radius:4px;overflow:hidden">'
                 . '<div style="background:#2f2e2e;padding:28px 32px"><h2 style="color:#e8b84b;margin:0;letter-spacing:2px;font-size:20px">BORIS MULTISERVICE</h2></div>'
                 . '<div style="padding:28px 32px;font-size:15px;color:#333;line-height:1.7">'
                 . '<p>Beste <strong>' . $voornaam . '</strong>,</p>'
                 . '<p>Hartelijk dank voor uw bericht! Wij hebben uw contactverzoek ontvangen en nemen zo spoedig mogelijk contact met u op.</p>'
                 . '<p>Heeft u een dringende vraag?<br>Mail ons op <a href="mailto:info@borismultiservice.nl" style="color:#e8b84b">info@borismultiservice.nl</a></p>'
                 . '<p>Met vriendelijke groet,<br><strong>Boris MultiService</strong></p></div>'
                 . '<div style="background:#2f2e2e;padding:16px 32px;text-align:center"><p style="color:#a09890;font-size:11px;margin:0">© 2026 <span style="color:#e8b84b">borismultiservice.nl</span></p></div>'
                 . '</div></body></html>';

    $bev_boundary = md5(uniqid(rand(), true));
    $bev_headers  = "From: =?UTF-8?B?" . base64_encode("Boris MultiService") . "?= <info@borismultiservice.nl>\r\n";
    $bev_headers .= "MIME-Version: 1.0\r\n";
    $bev_headers .= "Content-Type: multipart/alternative; boundary=\"$bev_boundary\"\r\n";

    $bev_message  = "--$bev_boundary\r\n";
    $bev_message .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $bev_message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $bev_message .= chunk_split(base64_encode($bev_plain)) . "\r\n";
    $bev_message .= "--$bev_boundary\r\n";
    $bev_message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $bev_message .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $bev_message .= chunk_split(base64_encode($bev_html)) . "\r\n";
    $bev_message .= "--$bev_boundary--";

    @mail($email, $bev_subject, $bev_message, $bev_headers);

    echo json_encode(['success' => true]);
} else {
    // mail() mislukt — log de fout voor debugging
    $err = error_get_last();
    echo json_encode(['success' => false, 'error' => 'Mail niet verstuurd', 'debug' => $err['message'] ?? 'onbekend']);
}
?>
