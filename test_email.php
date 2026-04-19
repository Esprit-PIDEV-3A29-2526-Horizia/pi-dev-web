<?php
/**
 * ══════════════════════════════════════════
 *  TEST EMAIL HORIZIA — à placer à la racine
 *  du projet Symfony, puis exécuter :
 *  php test_email.php
 * ══════════════════════════════════════════
 */

// ── Configuration ──────────────────────────
$smtpHost     = 'smtp.gmail.com';
$smtpPort     = 587;
$smtpUser     = 'arouayadam@gmail.com';
$smtpPassword = 'brtflqxmyfjxujly';   // ← App Password sans espaces
$emailDest    = 'arouayadam@gmail.com'; // ← email où recevoir le test
// ───────────────────────────────────────────

echo "\n";
echo "╔══════════════════════════════════════════╗\n";
echo "║       TEST EMAIL HORIZIA                 ║\n";
echo "╚══════════════════════════════════════════╝\n\n";

// ── ÉTAPE 1 : Résolution DNS ───────────────
echo "[ 1/4 ] Résolution DNS de smtp.gmail.com... ";
$ip = gethostbyname($smtpHost);
if ($ip === $smtpHost) {
    echo "❌ ÉCHEC — pas de réseau ou DNS bloqué\n";
    exit(1);
}
echo "✅ OK ($ip)\n";

// ── ÉTAPE 2 : Connexion TCP port 587 ──────
echo "[ 2/4 ] Connexion TCP $smtpHost:$smtpPort... ";
$errno = 0; $errstr = '';
$sock = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
if (!$sock) {
    echo "❌ ÉCHEC — $errstr ($errno)\n";
    echo "\n      → Le port 587 est bloqué par votre réseau/firewall.\n";
    echo "      → Essayez le port 465 avec SSL.\n\n";
    exit(1);
}
echo "✅ OK\n";
fclose($sock);

// ── ÉTAPE 3 : Handshake SMTP + AUTH ───────
echo "[ 3/4 ] Authentification SMTP (STARTTLS)... ";
$context = stream_context_create([
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ]
]);

$smtp = @stream_socket_client(
    "tcp://$smtpHost:$smtpPort",
    $errno, $errstr, 15,
    STREAM_CLIENT_CONNECT,
    $context
);

if (!$smtp) {
    echo "❌ Impossible d'ouvrir le socket : $errstr\n";
    exit(1);
}

function smtp_read($smtp): string {
    $out = '';
    while ($line = fgets($smtp, 515)) {
        $out .= $line;
        if ($line[3] === ' ') break; // fin de réponse multi-ligne
    }
    return trim($out);
}

function smtp_cmd($smtp, string $cmd): string {
    fwrite($smtp, $cmd . "\r\n");
    return smtp_read($smtp);
}

smtp_read($smtp); // bannière
smtp_cmd($smtp, "EHLO localhost");
$starttls = smtp_cmd($smtp, "STARTTLS");

if (!str_starts_with($starttls, '220')) {
    echo "❌ STARTTLS refusé : $starttls\n";
    fclose($smtp);
    exit(1);
}

// Upgrade vers TLS
stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
smtp_cmd($smtp, "EHLO localhost");

// AUTH LOGIN
smtp_cmd($smtp, "AUTH LOGIN");
smtp_cmd($smtp, base64_encode($smtpUser));
$authResp = smtp_cmd($smtp, base64_encode($smtpPassword));

if (!str_starts_with($authResp, '235')) {
    echo "❌ AUTHENTIFICATION ÉCHOUÉE\n";
    echo "\n   Réponse serveur : $authResp\n";
    echo "\n   Causes possibles :\n";
    echo "   • App Password incorrect ou révoqué\n";
    echo "   • La validation en 2 étapes n'est pas activée sur le compte Google\n";
    echo "   • Compte Google bloqué temporairement\n";
    echo "\n   → Allez sur https://myaccount.google.com/apppasswords\n";
    echo "   → Régénérez un App Password et mettez à jour .env\n\n";
    fclose($smtp);
    exit(1);
}

echo "✅ OK — Authentifié avec succès\n";

// ── ÉTAPE 4 : Envoi d'un vrai email test ──
echo "[ 4/4 ] Envoi d'un email de test à $emailDest... ";

$date    = date('r');
$subject = '=?UTF-8?B?' . base64_encode('✅ Test SMTP Horizia — ' . date('H:i:s')) . '?=';
$body    = "Ceci est un email de test automatique.\r\nEnvoyé le : $date\r\nServeur : $smtpHost:$smtpPort\r\nCompte : $smtpUser";

smtp_cmd($smtp, "MAIL FROM:<$smtpUser>");
smtp_cmd($smtp, "RCPT TO:<$emailDest>");
smtp_cmd($smtp, "DATA");
fwrite($smtp, "From: Horizia Test <$smtpUser>\r\n");
fwrite($smtp, "To: $emailDest\r\n");
fwrite($smtp, "Subject: $subject\r\n");
fwrite($smtp, "Date: $date\r\n");
fwrite($smtp, "MIME-Version: 1.0\r\n");
fwrite($smtp, "Content-Type: text/plain; charset=UTF-8\r\n");
fwrite($smtp, "\r\n");
fwrite($smtp, $body . "\r\n");
$sendResp = smtp_cmd($smtp, ".");

smtp_cmd($smtp, "QUIT");
fclose($smtp);

if (str_starts_with($sendResp, '250')) {
    echo "✅ EMAIL ENVOYÉ\n";
    echo "\n╔══════════════════════════════════════════╗\n";
    echo "║  ✅ TOUT FONCTIONNE CORRECTEMENT         ║\n";
    echo "║  Vérifiez la boîte : $emailDest\n";
    echo "╚══════════════════════════════════════════╝\n\n";
} else {
    echo "❌ Envoi refusé : $sendResp\n\n";
    exit(1);
}