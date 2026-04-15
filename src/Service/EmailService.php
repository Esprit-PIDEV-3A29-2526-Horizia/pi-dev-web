<?php
// src/Service/EmailService.php
namespace App\Service;

use App\Entity\Reservationlog;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Environment $twig;
    private PdfService $pdfService;
    private QrCodeService $qrCodeService;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, Environment $twig, PdfService $pdfService, QrCodeService $qrCodeService)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->pdfService = $pdfService;
        $this->qrCodeService = $qrCodeService;
    }

    public function sendReservationEmail(string $to, $reservation, string $customMessage): bool
    {
        try {
            // Contenu du QR code
            $qrContent = "Réservation #" . $reservation->getIdreslog() . "\n";
            $qrContent .= "Logement: " . $reservation->getLogement()->getNom() . "\n";
            $qrContent .= "Arrivée: " . $reservation->getDateDebut()->format('d/m/Y') . "\n";
            $qrContent .= "Départ: " . $reservation->getDateFin()->format('d/m/Y') . "\n";
            $qrContent .= "Adultes: " . $reservation->getAdultes() . "\n";
            $qrContent .= "Enfants: " . $reservation->getEnfants() . "\n";
            $qrContent .= "Chambres: " . $reservation->getNombreChambres() . "\n";
            $qrContent .= "Pension: " . ($reservation->getModeReservation() ? str_replace('_', ' ', $reservation->getModeReservation()) : '-') . "\n";
            $qrContent .= "Montant: " . number_format($reservation->getMontant(), 2, ',', ' ') . " DT\n";
            $qrContent .= "Modalité: " . $reservation->getModalites() . "\n";
            $qrContent .= "Statut: " . $reservation->getStatus();

            $qrCodeBase64 = $this->qrCodeService->generateQrCodeBase64($qrContent);

            $html = $this->twig->render('front/email/reservation_email.html.twig', [
                'reservation' => $reservation,
                'customMessage' => $customMessage,
                'qrCodeBase64' => $qrCodeBase64,
            ]);

            $pdfContent = $this->pdfService->generateReservationPdf($reservation);

            $email = (new Email())
                ->from('khadijaderbel123@gmail.com')
                ->to($to)
                ->subject('Horozia - Confirmation de réservation')
                ->html($html)
                ->attach($pdfContent, 'reservation_' . $reservation->getIdreslog() . '.pdf', 'application/pdf');

            $this->mailer->send($email);
            $this->logger->info('Email confirmation envoyé à ' . $to);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur email: ' . $e->getMessage());
            return false;
        }
    }

    public function sendCancellationEmail(string $to, $reservation, string $reason): bool
    {
        try {
            $html = $this->twig->render('front/email/reservation_email.html.twig', [
                'reservation' => $reservation,
                'customMessage' => "<strong>❌ Votre réservation a été annulée.</strong><br>Raison : {$reason}",
            ]);

            $email = (new Email())
                ->from('khadijaderbel123@gmail.com')
                ->to($to)
                ->subject('Horozia - Annulation de réservation')
                ->html($html);

            $this->mailer->send($email);
            $this->logger->info('Email annulation envoyé à ' . $to);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur email annulation: ' . $e->getMessage());
            return false;
        }
    }

    public function sendPaymentReminderEmail(Reservationlog $reservation, int $remainingSeconds): bool
    {
        try {
            $hours = floor($remainingSeconds / 3600);
            $minutes = floor(($remainingSeconds % 3600) / 60);
            $seconds = $remainingSeconds % 60;
            $timeLeft = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);

            $html = $this->twig->render('front/email/payment_reminder.html.twig', [
                'reservation' => $reservation,
                'timeLeft' => $timeLeft,
            ]);

            $email = (new Email())
                ->from('khadijaderbel123@gmail.com')
                ->to($reservation->getUser()->getEmail())
                ->subject('⏰ Paiement en ligne : votre réservation expire dans moins d\'1 heure !')
                ->html($html);

            $this->mailer->send($email);
            $this->logger->info('Email rappel paiement envoyé à ' . $reservation->getUser()->getEmail());
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur email rappel: ' . $e->getMessage());
            return false;
        }
    }
}