<?php
namespace App\Service;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function sendReservationEmail(string $to, $reservation, string $pdfContent): bool
    {
        try {
            $email = (new Email())
                ->from('khadijaderbel123@gmail.com')  // Utilisez votre adresse Gmail
                ->to($to)
                ->subject('Confirmation de réservation - Horozia')
                ->html($this->getEmailHtml($reservation))
                ->attach($pdfContent, 'reservation_' . $reservation->getIdreslog() . '.pdf', 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Email envoyé à ' . $to);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur email: ' . $e->getMessage());
            return false;
        }
    }

    private function getEmailHtml($reservation): string
    {
        return '
        <!DOCTYPE html>
        <html>
        <head><meta charset="UTF-8"></head>
        <body style="font-family: Arial, sans-serif;">
            <h2>Confirmation de réservation</h2>
            <p>Bonjour ' . htmlspecialchars($reservation->getUser()->getPrenom()) . ',</p>
            <p>Nous vous confirmons votre réservation chez <strong>Horozia</strong>.</p>
            <table style="border-collapse: collapse; width: 100%;">
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Logement</strong></td><td>' . htmlspecialchars($reservation->getLogement()->getNom()) . '</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Adresse</strong></td><td>' . htmlspecialchars($reservation->getLogement()->getAdresse()) . '</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Arrivée</strong></td><td>' . $reservation->getDateDebut()->format('d/m/Y') . '</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Départ</strong></td><td>' . $reservation->getDateFin()->format('d/m/Y') . '</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Montant total</strong></td><td>' . number_format($reservation->getMontant(), 2, ',', ' ') . ' DT</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Modalité</strong></td><td>' . htmlspecialchars($reservation->getModalites()) . '</td></tr>
                <tr><td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Statut</strong></td><td>' . htmlspecialchars($reservation->getStatus()) . '</td></tr>
            </table>
            <p>Merci de votre confiance !</p>
            <p>L\'équipe Horozia</p>
        </body>
        </html>';
    }
}