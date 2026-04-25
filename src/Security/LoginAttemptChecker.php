<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class LoginAttemptChecker
{
    private const MAX_ATTEMPTS = 3;
    private SessionInterface $session;
    private MailerInterface $mailer;

    public function __construct(SessionInterface $session, MailerInterface $mailer)
    {
        $this->session = $session;
        $this->mailer = $mailer;
    }

    public function registerFailedAttempt(string $email): bool
    {
        $attempts = $this->session->get('login_attempts_' . $email, 0);
        $attempts++;
        $this->session->set('login_attempts_' . $email, $attempts);

        if ($attempts >= self::MAX_ATTEMPTS && $email === 'admin@admin.com') {
            $this->sendAlertEmail($email, $attempts);
            return true; // alerte envoyée
        }
        return false;
    }

    public function resetAttempts(string $email): void
    {
        $this->session->remove('login_attempts_' . $email);
    }

    private function sendAlertEmail(string $email, int $attempts): void
    {
        $message = (new Email())
            ->from('no-reply@horozia.com')
            ->to('khalilbenlahmer@gmail.com')
            ->subject('Alerte sécurité : tentatives de connexion suspectes')
            ->html("<p>Bonjour,</p>
                    <p>{$attempts} tentatives de connexion échouées ont été détectées avec l'email administrateur (<strong>{$email}</strong>).</p>
                    <p>Si ce n'est pas vous, vérifiez la sécurité de votre compte.</p>");
        $this->mailer->send($message);
    }
}