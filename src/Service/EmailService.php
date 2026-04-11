<?php
namespace App\Service;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class EmailService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Environment $twig;
    private PdfService $pdfService;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger, Environment $twig, PdfService $pdfService)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->pdfService = $pdfService;
    }

    /**
     * Envoi d'email de confirmation (avec PDF joint)
     */
    public function sendReservationEmail(string $to, $reservation, string $customMessage): bool
    {
        try {
            $html = $this->twig->render('front/email/reservation_email.html.twig', [
                'reservation' => $reservation,
                'customMessage' => $customMessage,
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

    /**
     * Envoi d'email simple (sans PDF) pour annulation
     */
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
}