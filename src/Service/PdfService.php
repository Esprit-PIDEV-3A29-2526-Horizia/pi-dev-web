<?php
namespace App\Service;

use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Environment $twig;
    private ChambreTypeService $chambreTypeService;

    public function __construct(Environment $twig, ChambreTypeService $chambreTypeService)
    {
        $this->twig = $twig;
        $this->chambreTypeService = $chambreTypeService;
    }

    public function generateReservationPdf($reservation): string
{
    $chambreType = $this->chambreTypeService->getChambreType(
        $reservation->getAdultes(),
        $reservation->getEnfants(),
        $reservation->getNombreChambres()
    );

    $html = $this->twig->render('front/reservationlog/reservation_pdf.html.twig', [
        'reservation' => $reservation,
        'chambreType' => $chambreType,
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