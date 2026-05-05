<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DescriptionGeneratorManager
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Génère une description à partir du titre, catégorie, ville, pays
     * 
     * @param string $titre Le titre
     * @param string $categorie La catégorie
     * @param string|null $ville La ville (optionnelle)
     * @param string|null $pays Le pays (optionnel)
     * @return string La description générée
     */
    public function generateDescription(string $titre, string $categorie, ?string $ville = null, ?string $pays = null): string
    {
        $location = '';
        if ($ville !== null && $ville !== '' && $pays !== null && $pays !== '') {
            $location = " dans la région de $ville, $pays";
        } elseif ($ville !== null && $ville !== '') {
            $location = " à $ville";
        } elseif ($pays !== null && $pays !== '') {
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
     * Traduit une description via l'API MyMemory
     * 
     * @param string $description La description à traduire
     * @param string $targetLanguage La langue cible (défaut: 'en')
     * @return string La description traduite (ou originale en cas d'erreur)
     */
    public function translateDescription(string $description, string $targetLanguage = 'en'): string
    {
        if ($description === '') {
            return '';
        }
        
        try {
            $url = "https://api.mymemory.translated.net/get?q=" . urlencode($description) . "&langpair=fr|$targetLanguage";
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 10,
            ]);
            $content = $response->getContent();
            $data = json_decode($content, true);

            if (is_array($data) && isset($data['responseData']['translatedText'])) {
                $translated = $data['responseData']['translatedText'];
                if (is_string($translated)) {
                    return $translated;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Translation error: ' . $e->getMessage());
        }

        return $description;
    }
}