<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service OCR – CIN tunisienne
 * Utilise l'API OCR.space (Free Tier)
 */
class OCRService
{
    private const API_URL = 'https://api.ocr.space/parse/image';
    private const API_KEY = 'K89712018688957'; // Votre clé API

    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Scan recto de la CIN – extrait uniquement le numéro CIN (8 chiffres)
     * @return array ['cin' => string] ou ['erreur' => string]
     */
    public function scannerCINRecto(string $imagePath): array
    {
        $resultats = [];

        // Vérifier que le fichier existe
        if (!file_exists($imagePath)) {
            $this->logger->error('OCR: Fichier image introuvable', ['path' => $imagePath]);
            return ['erreur' => 'Fichier image introuvable'];
        }

        try {
            $this->logger->info('OCR: Scan CIN recto', ['path' => $imagePath, 'size' => filesize($imagePath)]);
            
            // Essai avec Engine 2 (plus précis pour la CIN)
            $texte = $this->appelOCRSpace($imagePath, 'eng', '2');
            if ($texte && !empty(trim($texte))) {
                $this->logger->info('OCR: Texte brut (Engine 2)', ['texte' => substr($texte, 0, 500)]);
                $this->extraireCIN($texte, $resultats);
            }

            // Fallback Engine 1 si CIN non trouvé
            if (!isset($resultats['cin'])) {
                $this->logger->info('OCR: Fallback Engine 1');
                $texte2 = $this->appelOCRSpace($imagePath, 'eng', '1');
                if ($texte2 && !empty(trim($texte2))) {
                    $this->logger->info('OCR: Texte brut (Engine 1)', ['texte' => substr($texte2, 0, 500)]);
                    $this->extraireCIN($texte2, $resultats);
                }
            }

            if (!isset($resultats['cin'])) {
                $resultats['info'] = 'CIN non detectee – verifiez la qualite de l\'image.';
                $this->logger->warning('OCR: CIN non trouvée dans l\'image');
            } else {
                $this->logger->info('OCR: CIN extraite avec succès', ['cin' => $resultats['cin']]);
            }

        } catch (\Exception $e) {
            $this->logger->error('OCR: Erreur recto', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $resultats['erreur'] = 'Erreur OCR : ' . $e->getMessage();
        }

        return $resultats;
    }

    /**
     * Alias pour scannerCINRecto
     */
    public function scannerCIN(string $imagePath): array
    {
        return $this->scannerCINRecto($imagePath);
    }

    /**
     * Extraction du CIN (8 chiffres consécutifs)
     */
    private function extraireCIN(string $texte, array &$resultats): void
    {
        if (isset($resultats['cin'])) return;

        // Nettoyer le texte
        $texte = preg_replace('/[^\d\s]/', ' ', $texte);
        
        // Priorité 1 : ligne contenant EXACTEMENT 8 chiffres (après nettoyage)
        $lignes = preg_split('/[\r\n]+/', $texte);
        foreach ($lignes as $ligne) {
            $l = preg_replace('/[^0-9]/', '', trim($ligne));
            if (strlen($l) === 8) {
                $resultats['cin'] = $l;
                $this->logger->info('OCR: CIN trouvée (ligne exacte)', ['cin' => $l]);
                return;
            }
        }

        // Priorité 2 : séquence de 8 chiffres avec boundaries
        if (preg_match('/\b(\d{8})\b/', $texte, $matches)) {
            $resultats['cin'] = $matches[1];
            $this->logger->info('OCR: CIN trouvée (regex boundary)', ['cin' => $matches[1]]);
            return;
        }

        // Priorité 3 : n'importe quelle séquence de 8 chiffres
        $texteSansEspaces = preg_replace('/\s/', '', $texte);
        if (preg_match('/(\d{8})/', $texteSansEspaces, $matches)) {
            $resultats['cin'] = $matches[1];
            $this->logger->info('OCR: CIN trouvée (regex simple)', ['cin' => $matches[1]]);
        }
    }

    /**
     * Appel à l'API OCR.space
     */
    private function appelOCRSpace(string $imagePath, string $language, string $ocrEngine): ?string
    {
        // Vérifier que le fichier existe
        if (!file_exists($imagePath)) {
            throw new \Exception('Fichier image introuvable : ' . $imagePath);
        }

        // Lire et encoder l'image en base64
        $imageData = file_get_contents($imagePath);
        $mimeType = mime_content_type($imagePath);
        $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);

        try {
            $response = $this->httpClient->request('POST', self::API_URL, [
                'headers' => [
                    'apikey' => self::API_KEY,
                ],
                'body' => [
                    'base64Image' => $base64Image,
                    'language' => $language,
                    'OCREngine' => $ocrEngine,
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray();
            $this->logger->info('OCR: Réponse API', ['status' => $response->getStatusCode()]);

            if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing'] === true) {
                $errorMessage = $data['ErrorMessage'] ?? 'Erreur inconnue';
                throw new \Exception('OCR error: ' . $errorMessage);
            }

            if (isset($data['ParsedResults'][0]['ParsedText'])) {
                return $data['ParsedResults'][0]['ParsedText'];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('OCR: Erreur appel API', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Test de la clé API
     */
    public function testerCleAPI(): array
    {
        try {
            // Créer une image de test simple (PNG blanc)
            $tempFile = tempnam(sys_get_temp_dir(), 'test_ocr_');
            $img = imagecreatetruecolor(200, 100);
            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefilledrectangle($img, 0, 0, 200, 100, $white);
            imagestring($img, 5, 10, 40, "12345678", $black);
            imagepng($img, $tempFile);
            imagedestroy($img);
            
            $result = $this->scannerCINRecto($tempFile);
            unlink($tempFile);
            
            if (isset($result['cin']) && $result['cin'] === '12345678') {
                return ['success' => true, 'message' => 'API OCR fonctionnelle'];
            }
            return ['success' => false, 'message' => 'Test échoué: ' . ($result['erreur'] ?? $result['info'] ?? 'Inconnu')];
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erreur test: ' . $e->getMessage()];
        }
    }
}