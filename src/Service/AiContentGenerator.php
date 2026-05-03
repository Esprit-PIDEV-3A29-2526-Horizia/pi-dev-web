<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiContentGenerator
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return array<string, mixed>
     */
    public function generateAll(string $sujet): array
    {
        $prompt = "Tu es un rédacteur spécialisé en voyages. À partir du sujet suivant : '$sujet', génère :
- un titre accrocheur (max 60 caractères)
- une catégorie parmi : Plage, Montagne, Ville, Désert, Campagne, Historique
- une description détaillée (3 à 5 phrases)
- trois tags (séparés par des virgules)
- un prompt pour générer une image (description courte)

Réponds uniquement au format JSON, sans texte autour :
{
    \"titre\": \"...\",
    \"categorie\": \"...\",
    \"description\": \"...\",
    \"tags\": \"...\",
    \"imagePrompt\": \"...\"
}";

        try {
            $url = 'https://text.pollinations.ai/prompt?text=' . urlencode($prompt);
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 30,
            ]);
            $text = $response->getContent();

            preg_match('/\{.*\}/s', $text, $matches);
            if (isset($matches[0])) {
                $data = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // fallback
        }

        return [
            'titre' => ucfirst($sujet),
            'categorie' => 'Ville',
            'description' => "Découvrez $sujet, une destination exceptionnelle. Profitez de paysages magnifiques et d'activités variées.",
            'tags' => 'voyage, découverte, aventure',
            'imagePrompt' => $sujet . ' paysage magnifique'
        ];
    }
}