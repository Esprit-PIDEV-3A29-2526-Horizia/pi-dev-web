<?php
namespace App\Service;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendReservationEmail(string $to, $reservation, string $pdfContent): bool
    {
        try {
            $email = (new Email())
                ->from('noreply@horozia.com')
                ->to($to)
                ->subject('Votre réservation Horozia')
                ->html('<h1>Réservation confirmée</h1><p>Veuillez trouver en pièce jointe votre QR code et les détails.</p>')
                ->attach($pdfContent, 'reservation_' . $reservation->getIdreslog() . '.pdf', 'application/pdf');
            
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}