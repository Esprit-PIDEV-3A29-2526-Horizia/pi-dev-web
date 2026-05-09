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
     * @return array{titre: string, categorie: string, description: string, tags: string, imagePrompt: string}
     */
    public function generateAll(string $sujet): array
    {
        // Construction du prompt
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
            // Appel à l'API texte de Pollinations.ai (gratuit, sans clé)
            $url = 'https://text.pollinations.ai/prompt?text=' . urlencode($prompt);
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 30,
            ]);
            $text = $response->getContent();
            
            // Extraire le JSON (le modèle peut retourner du texte supplémentaire)
            preg_match('/\{.*\}/s', $text, $matches);
            if (isset($matches[0])) {
                $data = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
            // fallback
        }

        // Valeurs par défaut en cas d’échec
        return [
            'titre' => ucfirst($sujet),
            'categorie' => 'Ville',
            'description' => "Découvrez $sujet, une destination exceptionnelle. Profitez de paysages magnifiques et d'activités variées.",
            'tags' => 'voyage, découverte, aventure',
            'imagePrompt' => $sujet . ' paysage magnifique'
        ];
    }
}