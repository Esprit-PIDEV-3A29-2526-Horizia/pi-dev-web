<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiContentGeneratorManager
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Génère du contenu complet à partir d'un sujet
     * 
     * @param string $sujet Le sujet du contenu à générer
     * @return array{titre: string, categorie: string, description: string, tags: string, imagePrompt: string}
     */
    public function generateAll(string $sujet): array
    {
        try {
            $url = 'https://text.pollinations.ai/prompt?text=' . urlencode($this->buildPrompt($sujet));
            $response = $this->httpClient->request('GET', $url, ['timeout' => 30]);
            $text = $response->getContent();
            
            $data = $this->extractJson($text);
            if ($data !== null) {
                return $data;
            }
        } catch (\Exception $e) {
            // Fallback
        }

        return $this->getDefaultResponse($sujet);
    }

    private function buildPrompt(string $sujet): string
    {
        return "Tu es un rédacteur spécialisé en voyages. À partir du sujet suivant : '$sujet', génère :
- un titre accrocheur (max 60 caractères)
- une catégorie parmi : Plage, Montagne, Ville, Désert, Campagne, Historique
- une description détaillée (3 à 5 phrases)
- trois tags (séparés par des virgules)
- un prompt pour générer une image (description courte)

Réponds uniquement au format JSON :
{
    \"titre\": \"...\",
    \"categorie\": \"...\",
    \"description\": \"...\",
    \"tags\": \"...\",
    \"imagePrompt\": \"...\"
}";
    }

    /**
     * @return array{titre: string, categorie: string, description: string, tags: string, imagePrompt: string}|null
     */
    private function extractJson(string $text): ?array
    {
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            /** @phpstan-ignore-next-line */
            $jsonString = $matches[0] ?? '';
            /** @phpstan-ignore-next-line */
            if ($jsonString !== '') {
                $data = json_decode($jsonString, true);
                if (is_array($data) && isset($data['titre'])) {
                    return [
                        'titre' => (string)$data['titre'],
                        'categorie' => (string)($data['categorie'] ?? 'Ville'),
                        'description' => (string)($data['description'] ?? ''),
                        'tags' => (string)($data['tags'] ?? ''),
                        'imagePrompt' => (string)($data['imagePrompt'] ?? '')
                    ];
                }
            }
        }
        return null;
    }

    /**
     * @return array{titre: string, categorie: string, description: string, tags: string, imagePrompt: string}
     */
    private function getDefaultResponse(string $sujet): array
    {
        return [
            'titre' => ucfirst($sujet),
            'categorie' => 'Ville',
            'description' => "Découvrez $sujet, une destination exceptionnelle. Profitez de paysages magnifiques et d'activités variées.",
            'tags' => 'voyage, découverte, aventure',
            'imagePrompt' => $sujet . ' paysage magnifique'
        ];
    }
}