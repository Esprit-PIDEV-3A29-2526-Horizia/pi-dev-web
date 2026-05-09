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

    /**
     * Utilisé pour : lieux à visiter, programmes, itinéraires, activités.
     */
    public function askForPlacesToVisit(string $userMessage): string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => $this->openAiModel,
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => <<<PROMPT
Tu es l'assistant voyage intelligent de Horozia.
Tu réponds toujours en français, de façon claire, naturelle et professionnelle.

Quand l'utilisateur demande des endroits à visiter, des choses à faire, un programme ou un itinéraire dans un pays ou une ville :
- Propose 5 à 7 recommandations (lieux ET activités mélangés)
- Donne une courte explication pour chaque proposition (1-2 phrases)
- Si une durée est mentionnée (ex: 3 jours), structure la réponse par journée
- Inclus des conseils pratiques si pertinents (meilleur moment, transport, budget)
- Reste concret, utile et engageant, évite les généralités vagues
- Si la destination est en Tunisie, mets en avant les spécificités locales
PROMPT
                        ],
                        [
                            'role'    => 'user',
                            'content' => $userMessage,
                        ],
                    ],
                    'max_tokens'  => 1500,
                    'temperature' => 0.7,
                ],
                'timeout' => 25,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = $response->toArray(false);

            if ($statusCode >= 400) {
                $this->logger->error('OpenAI askForPlacesToVisit HTTP error', [
                    'status'   => $statusCode,
                    'response' => $data,
                ]);

                return "Je n'ai pas pu générer de recommandation pour le moment. Veuillez réessayer.";
            }

            // Format standard : /v1/chat/completions
            if (!empty($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
            }

            // Fallback : ancienne Responses API
            if (!empty($data['output_text'])) {
                return trim($data['output_text']);
            }

            if (!empty($data['output'][0]['content'][0]['text'])) {
                return trim($data['output'][0]['content'][0]['text']);
            }

            $this->logger->warning('OpenAI askForPlacesToVisit : réponse vide ou format inattendu', [
                'response' => $data,
            ]);

            return "Je n'ai pas pu générer de recommandation pour le moment.";

        } catch (\Throwable $e) {
            $this->logger->error('OpenAI askForPlacesToVisit exception', [
                'message' => $e->getMessage(),
            ]);

            return "Je n'ai pas pu générer de recommandation pour le moment. Vérifiez votre connexion ou réessayez.";
        }
    }

    /**
     * Reformule une réponse métier locale avec un ton plus naturel.
     */
    public function rewriteRecommendation(string $userMessage, string $localReply): string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'    => $this->openAiModel,
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un assistant voyage pour Horozia. Reformule la réponse en français, de manière naturelle, courte, claire et professionnelle. Ne change pas les données (prix, dates, destinations).',
                        ],
                        [
                            'role'    => 'user',
                            'content' => "Message utilisateur : {$userMessage}\n\nRéponse métier : {$localReply}",
                        ],
                    ],
                    'max_tokens'  => 500,
                    'temperature' => 0.5,
                ],
                'timeout' => 20,
            ]);

            $statusCode = $response->getStatusCode();
            $data       = $response->toArray(false);

            if ($statusCode >= 400) {
                $this->logger->warning('OpenAI rewriteRecommendation HTTP error', [
                    'status'   => $statusCode,
                    'response' => $data,
                ]);

                return $localReply;
            }

            if (!empty($data['choices'][0]['message']['content'])) {
                return trim($data['choices'][0]['message']['content']);
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
}