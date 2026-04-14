<?php
namespace App\Service;

use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Environment $twig;
    private ChambreTypeService $chambreTypeService;
    private QrCodeService $qrCodeService;

    public function __construct(Environment $twig, ChambreTypeService $chambreTypeService, QrCodeService $qrCodeService)
    {
        $this->twig = $twig;
        $this->chambreTypeService = $chambreTypeService;
        $this->qrCodeService = $qrCodeService;
    }

    public function generateReservationPdf($reservation): string
    {
        $chambreType = $this->chambreTypeService->getChambreType(
            $reservation->getAdultes(),
            $reservation->getEnfants(),
            $reservation->getNombreChambres()
        );

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

        $html = $this->twig->render('front/reservationlog/reservation_pdf.html.twig', [
            'reservation' => $reservation,
            'chambreType' => $chambreType,
            'qrCodeBase64' => $qrCodeBase64,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $dompdf->output();
    }
}