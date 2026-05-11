<?php
namespace App\Service;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;

class QrCodeService
{
    public function generateQrCodeBase64(string $text): string
    {
        $qrCode = new QrCode(
            data: $text,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 10
        );
        
        $writer = new SvgWriter();
        $result = $writer->write($qrCode);
        
        // Retourne une URI de données SVG (base64)
        return $result->getDataUri();
    }
    public function genererQRCodeLocation(int $id, string $nom, string $immatriculation, string $dateDebut, string $dateFin): string
    {
        $texte = sprintf(
            "Location #%d\nClient: %s\nVéhicule: %s\nDu: %s\nAu: %s",
            $id,
            $nom,
            $immatriculation,
            $dateDebut,
            $dateFin
        );
        
        return $this->generateQrCodeBase64($texte);
    }
}