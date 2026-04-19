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
    private const API_KEY = 'K89712018688957';

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

        try {
            $this->logger->info('→ [RECTO] Scan Engine 2, langue=eng...');
            
            // Appel à l'API OCR.space
            $texte = $this->appelOCRSpace($imagePath, 'eng', '2');

            if ($texte && !empty(trim($texte))) {
                $this->logger->info('[RECTO] Texte brut : ' . substr($texte, 0, 500));
                $this->extraireCIN($texte, $resultats);
            }

            // Fallback Engine 1 si CIN non trouvé
            if (!isset($resultats['cin'])) {
                $this->logger->info('→ [RECTO] Fallback Engine 1, langue=eng...');
                $texte2 = $this->appelOCRSpace($imagePath, 'eng', '1');
                if ($texte2 && !empty(trim($texte2))) {
                    $this->logger->info('[RECTO-E1] Texte brut : ' . substr($texte2, 0, 500));
                    $this->extraireCIN($texte2, $resultats);
                }
            }

            if (!isset($resultats['cin'])) {
                $resultats['info'] = 'CIN non detectee – verifiez la qualite de l\'image.';
                $this->logger->warning('⚠ CIN non trouvée dans l\'image.');
            } else {
                $this->logger->info('✓ [RECTO] CIN extraite : ' . $resultats['cin']);
            }

        } catch (\Exception $e) {
            $this->logger->error('✗ Erreur recto : ' . $e->getMessage());
            $resultats['erreur'] = 'Erreur OCR : ' . $e->getMessage();
        }

        return $resultats;
    }

    /**
     * Scan verso de la CIN (gardé pour compatibilité)
     */
    public function scannerCINVerso(string $imagePath): array
    {
        $resultats = [];

        try {
            $this->logger->info('→ [VERSO] Scan Engine 2, langue=eng...');
            $texte = $this->appelOCRSpace($imagePath, 'eng', '2');

            if ($texte && !empty(trim($texte))) {
                $this->logger->info('[VERSO] Texte brut : ' . substr($texte, 0, 500));
                $this->extraireCIN($texte, $resultats);
            }

        } catch (\Exception $e) {
            $this->logger->error('✗ Erreur verso : ' . $e->getMessage());
            $resultats['erreur'] = 'Erreur OCR verso : ' . $e->getMessage();
        }

        return $resultats;
    }

    /**
     * Alias pour scannerCINRecto (compatibilité avec le code Java)
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
        if (isset($resultats['cin'])) return; // déjà trouvé

        $lignes = preg_split('/[\r\n]+/', $texte);

        // Priorité 1 : ligne contenant EXACTEMENT 8 chiffres (après nettoyage)
        foreach ($lignes as $ligne) {
            $l = preg_replace('/[^0-9]/', '', trim($ligne));
            if (strlen($l) === 8) {
                $resultats['cin'] = $l;
                $this->logger->info('✓ CIN (ligne exacte 8 chiffres) : ' . $l);
                return;
            }
        }

        // Priorité 2 : séquence de 8 chiffres avec boundaries
        if (preg_match('/\b(\d{8})\b/', $texte, $matches)) {
            $resultats['cin'] = $matches[1];
            $this->logger->info('✓ CIN (regex \\b) : ' . $matches[1]);
            return;
        }

        // Priorité 3 : n'importe quelle séquence de 8 chiffres
        $texteSansEspaces = preg_replace('/\s/', '', $texte);
        if (preg_match('/(\d{8})/', $texteSansEspaces, $matches)) {
            $resultats['cin'] = $matches[1];
            $this->logger->info('✓ CIN (regex simple) : ' . $matches[1]);
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

        // Préparer le fichier pour l'upload
        $fileContent = base64_encode(file_get_contents($imagePath));

        // Appel à l'API
        $response = $this->httpClient->request('POST', self::API_URL, [
            'headers' => [
                'apikey' => self::API_KEY,
            ],
            'body' => [
                'base64Image' => 'data:image/png;base64,' . $fileContent,
                'language' => $language,
                'OCREngine' => $ocrEngine,
                'isOverlayRequired' => 'false',
                'detectOrientation' => 'true',
                'scale' => 'true',
            ],
        ]);

        $data = $response->toArray();

        if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing'] === true) {
            $errorMessage = $data['ErrorMessage'] ?? 'Erreur inconnue';
            throw new \Exception('OCR error: ' . $errorMessage);
        }

        if (isset($data['ParsedResults'][0]['ParsedText'])) {
            return $data['ParsedResults'][0]['ParsedText'];
        }

        return null;
    }

    /**
     * Test de la clé API
     */
    public function testerCleAPI(): bool
    {
        try {
            // Créer une image de test temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'test_ocr');
            file_put_contents($tempFile, 'test');
            
            $this->appelOCRSpace($tempFile, 'eng', '1');
            unlink($tempFile);
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Test API OCR échoué : ' . $e->getMessage());
            return false;
        }
    }
}