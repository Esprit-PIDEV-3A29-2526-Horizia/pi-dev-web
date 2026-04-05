<?php
namespace App\Service;
use App\Entity\Reservationlog;
use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function generateReservationPdf(Reservationlog $reservation): string
    {
        $html = $this->twig->render('front/reservationlog/reservation_pdf.html.twig', [
            'reservation' => $reservation,
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