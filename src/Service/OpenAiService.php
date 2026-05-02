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
        return $this->askOpenAi(
            systemPrompt: <<<PROMPT
Tu es l’assistant voyage intelligent de Horozia.
Tu réponds toujours en français.

Quand l’utilisateur demande des endroits à visiter, un programme ou des choses à faire :
- propose des monuments, musées, quartiers, activités et expériences locales
- si l’utilisateur demande un nombre de jours, organise la réponse jour par jour
- donne une réponse claire, structurée et utile
- reste professionnel, naturel et concret
- ne dis jamais que tu es une IA
PROMPT,
            userMessage: $userMessage,
            fallback: "Je n’ai pas pu générer de recommandation pour le moment."
        );
    }

    public function rewriteRecommendation(string $userMessage, string $localReply): string
    {
        return $this->askOpenAi(
            systemPrompt: 'Tu es un assistant voyage pour Horozia. Reformule la réponse en français, de manière naturelle, courte, claire, utile et professionnelle.',
            userMessage: "Message utilisateur : {$userMessage}\n\nRéponse métier : {$localReply}",
            fallback: $localReply
        );
    }

    private function askOpenAi(string $systemPrompt, string $userMessage, string $fallback): string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $userMessage,
                        ],
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 700,
                ],
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode >= 400) {
                $this->logger->error('OpenAI HTTP error', [
                    'status' => $statusCode,
                    'response' => $data,
                ]);

                return 'Erreur OpenAI HTTP ' . $statusCode . ' : ' . json_encode($data);
            }

            $text = $data['choices'][0]['message']['content'] ?? null;

            if (!is_string($text) || trim($text) === '') {
                $this->logger->warning('OpenAI empty response', [
                    'response' => $data,
                ]);

                return $fallback;
            }

            return trim($text);

        } catch (\Throwable $e) {
            $this->logger->error('OpenAI exception', [
                'message' => $e->getMessage(),
            ]);

            return 'Erreur OpenAI : ' . $e->getMessage();
        }
    }
}