<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openAiApiKey,
        private string $openAiModel,
        private LoggerInterface $logger
    ) {
    }

    public function askForPlacesToVisit(string $userMessage): string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => <<<PROMPT
Tu es l’assistant voyage intelligent de Horozia.
Tu réponds toujours en français.

Quand l’utilisateur demande des endroits à visiter ou des choses à faire dans un pays ou une ville :
- propose 5 à 7 recommandations maximum
- mélange lieux à visiter et activités à faire
- donne une courte explication pour chaque proposition
- organise la réponse de façon claire et agréable à lire
- reste naturel, utile, professionnel et concret
- évite les réponses vagues
- si l’utilisateur demande "que faire", suggère aussi des expériences, promenades, visites, spécialités locales ou activités culturelles
PROMPT
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage
                        ]
                    ]
                ],
                'timeout' => 25,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $this->logger->error('OpenAI askForPlacesToVisit HTTP error', [
                    'status' => $statusCode,
                    'response' => $data,
                ]);

                return "Je n’ai pas pu générer de recommandation pour le moment.";
            }

            if (!empty($data['output_text'])) {
                return trim($data['output_text']);
            }

            if (!empty($data['output'][0]['content'][0]['text'])) {
                return trim($data['output'][0]['content'][0]['text']);
            }

            $this->logger->warning('OpenAI askForPlacesToVisit empty response', [
                'response' => $data,
            ]);

            return "Je n’ai pas pu générer de recommandation pour le moment.";
        } catch (\Throwable $e) {
            $this->logger->error('OpenAI askForPlacesToVisit exception', [
                'message' => $e->getMessage(),
            ]);

            return "Je n’ai pas pu générer de recommandation pour le moment.";
        }
    }

    public function rewriteRecommendation(string $userMessage, string $localReply): string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant voyage pour Horozia. Reformule la réponse en français, de manière naturelle, courte, claire, utile et professionnelle.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Message utilisateur : {$userMessage}\n\nRéponse métier : {$localReply}"
                        ]
                    ]
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $this->logger->warning('OpenAI rewriteRecommendation HTTP error', [
                    'status' => $statusCode,
                    'response' => $data,
                ]);

                return $localReply;
            }

            if (!empty($data['output_text'])) {
                return trim($data['output_text']);
            }

            if (!empty($data['output'][0]['content'][0]['text'])) {
                return trim($data['output'][0]['content'][0]['text']);
            }

            return $localReply;
        } catch (\Throwable $e) {
            $this->logger->warning('OpenAI rewriteRecommendation exception', [
                'message' => $e->getMessage(),
            ]);

            return $localReply;
        }
    }
    public function generateDescription(string $titre, string $categorie, ?string $ville, ?string $pays): string
    {
        $prompt = "Génère une description attrayante pour un article de voyage sur le thème '$titre' (catégorie: $categorie). Localisation: $ville, $pays. La description doit faire environ 100-150 mots, en français, et être engageante pour des voyageurs.";

        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openAiApiKey,
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
                'Authorization' => 'Bearer ' . $this->openAiApiKey,
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