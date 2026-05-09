<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DescriptionGenerator
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Génère une description à partir du titre, catégorie, ville, pays.
     */
    public function generateDescription(string $titre, string $categorie, ?string $ville = null, ?string $pays = null): string
    {
        // Construire la localisation
        $location = '';
        if ($ville && $pays) {
            $location = " dans la région de $ville, $pays";
        } elseif ($ville) {
            $location = " à $ville";
        } elseif ($pays) {
            $location = " au $pays";
        }

        $templates = [
            "Découvrez %s%s, une expérience unique dans la catégorie « %s ». Laissez-vous séduire par des paysages magnifiques et des moments inoubliables.",
            "Partez à l'aventure avec %s%s. Idéal pour les amateurs de %s, ce voyage vous promet dépaysement et émerveillement.",
            "Explorez %s%s : une destination de choix pour les passionnés de %s. Vivez des instants magiques et créez des souvenirs impérissables.",
            "Ne manquez pas %s%s ! Parfait pour les voyageurs en quête de %s, cet endroit vous offrira des sensations fortes et une évasion totale.",
        ];

        $template = $templates[array_rand($templates)];
        return sprintf($template, $titre, $location, $categorie);
    }

    /**
     * Traduit une description via l'API MyMemory (gratuite, sans clé).
     * En cas d'échec, retourne la description originale.
     */
    public function translateDescription(string $description, string $targetLanguage = 'en'): string
    {
        try {
            $url = "https://api.mymemory.translated.net/get?q=" . urlencode($description) . "&langpair=fr|$targetLanguage";
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10, // 10 secondes max
            ]);
            $content = $response->getContent();
            $data = json_decode($content, true);

            if (isset($data['responseData']['translatedText'])) {
                $translated = $data['responseData']['translatedText'];
                // MyMemory peut retourner le texte original si la traduction échoue (score faible)
                // On accepte le résultat tel quel.
                return $translated;
            }
        } catch (\Exception $e) {
            $this->logger->error('Translation error: ' . $e->getMessage());
        }

        // Fallback : retourner la description originale
        return $description;
    }
}