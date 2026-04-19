<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Component\HttpFoundation\Response;

class QRCodeService
{
    private const QR_SIZE = 300;

    /**
     * Génère un QR Code simple à partir d'un texte
     */
    public function genererQRCode(string $texte): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($texte)
            ->encoding(new Encoding('UTF-8'))
            ->size(self::QR_SIZE)
            ->margin(10)
            ->build();

        return $result->getDataUri();
    }

    /**
     * Génère un QR Code pour une location
     */
    public function genererQRCodeLocation(int $locationId, string $nomClient, string $vehicule, string $dateDebut, string $dateFin): string
    {
        $contenu = "HORIZIA-LOCATION\nID:{$locationId}\nCLIENT:{$nomClient}\nVEHICULE:{$vehicule}\nDEBUT:{$dateDebut}\nFIN:{$dateFin}";
        return $this->genererQRCode($contenu);
    }

    /**
     * Sauvegarde un QR Code dans un fichier
     */
    public function sauvegarderQRCode(int $locationId, string $nomClient, string $vehicule, string $dateDebut, string $dateFin, string $dossier): ?string
    {
        $contenu = "HORIZIA-LOCATION\nID:{$locationId}\nCLIENT:{$nomClient}\nVEHICULE:{$vehicule}\nDEBUT:{$dateDebut}\nFIN:{$dateFin}";

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($contenu)
            ->encoding(new Encoding('UTF-8'))
            ->size(self::QR_SIZE)
            ->margin(10)
            ->build();

        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }

        $nomFichier = 'QR_Location_' . $locationId . '.png';
        $chemin = $dossier . DIRECTORY_SEPARATOR . $nomFichier;

        file_put_contents($chemin, $result->getString());

        return $chemin;
    }

    /**
     * Génère un QR Code et retourne une réponse HTTP
     */
    public function genererQRCodeResponse(string $texte): Response
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($texte)
            ->encoding(new Encoding('UTF-8'))
            ->size(self::QR_SIZE)
            ->margin(10)
            ->build();

        $response = new Response($result->getString());
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Disposition', 'inline; filename="qrcode.png"');

        return $response;
    }
}