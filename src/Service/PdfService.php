<?php
namespace App\Service;
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

    public function generateReservationPdf($reservation): string
    {
        $html = $this->twig->render('front/email/reservation_pdf.html.twig', [
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