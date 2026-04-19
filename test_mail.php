<?php
require 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$transport = Transport::fromDsn($_ENV['MAILER_DSN']);
$mailer = new Mailer($transport);

$email = (new Email())
    ->from('arouayadam@gmail.com')
    ->to('arouayadam@gmail.com')
    ->subject('Test Mailer Horizia')
    ->text('Mailer fonctionne correctement !');

try {
    $mailer->send($email);
    echo "✅ Email envoyé avec succès !\n";
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}