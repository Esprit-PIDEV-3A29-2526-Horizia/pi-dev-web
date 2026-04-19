<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiService
{
    private $httpClient;
    private $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function generateDescription(string $titre, string $categorie, ?string $ville, ?string $pays): string
    {
        $prompt = "Génère une description attrayante pour un article de voyage sur le thème '$titre' (catégorie: $categorie). Localisation: $ville, $pays. La description doit faire environ 100-150 mots, en français, et être engageante pour des voyageurs.";

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 300,
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    public function translateDescription(string $description, string $targetLanguage = 'en'): string
    {
        $prompt = "Traduis le texte suivant en $targetLanguage :\n\n$description";

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? $description;
    }
}