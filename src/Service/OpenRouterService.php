<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenRouterService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?string $apiKey,
        private LoggerInterface $logger
    ) {
        $this->apiKey = (string) ($apiKey ?? '');
    }

    /**
     * @return array{
     *   success: bool,
     *   text: string|null,
     *   status_code: int,
     *   error_type: string|null,
     *   user_message: string|null
     * }
     */
    public function askTravelAssistant(string $prompt): array
    {
        if (trim($this->apiKey) === '') {
            return [
                'success' => false,
                'text' => null,
                'status_code' => 500,
                'error_type' => 'missing_api_key',
                'user_message' => "Clé OpenRouter absente. Ajoute OPENROUTER_API_KEY dans le fichier .env.",
            ];
        }

        $models = [
            'meta-llama/llama-3.1-8b-instruct:free',
            'mistralai/mistral-7b-instruct:free',
            'google/gemma-2-9b-it:free',
        ];

        $hadQuotaError = false;

        foreach ($models as $model) {
            try {
                $response = $this->httpClient->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => $model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => "You are Horozia's travel assistant. Return clean HTML only.",
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => 0.7,
                        'max_tokens' => 900,
                    ],
                    'timeout' => 30,
                ]);

                $statusCode = $response->getStatusCode();
                $content = $response->getContent(false);

                if ($statusCode === 429) {
                    $hadQuotaError = true;
                    $this->logger->warning('OpenRouter quota exceeded for model.', ['model' => $model]);
                    continue;
                }

                if ($statusCode >= 400) {
                    $this->logger->error('OpenRouter API error', [
                        'model' => $model,
                        'status' => $statusCode,
                        'response' => $content,
                    ]);
                    continue;
                }

                $data = json_decode($content, true);
                $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

                if ($text === '') {
                    $this->logger->warning('OpenRouter empty response for model.', ['model' => $model]);
                    continue;
                }

                return [
                    'success' => true,
                    'text' => $text,
                    'status_code' => 200,
                    'error_type' => null,
                    'user_message' => null,
                ];
            } catch (TransportExceptionInterface $e) {
                $this->logger->error('OpenRouter network error', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'text' => null,
                    'status_code' => 503,
                    'error_type' => 'network_error',
                    'user_message' => "Impossible de contacter le service IA de secours (problème réseau).",
                ];
            } catch (\Throwable $e) {
                $this->logger->error('OpenRouter exception', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);
                continue;
            }
        }

        if ($hadQuotaError) {
            return [
                'success' => false,
                'text' => null,
                'status_code' => 429,
                'error_type' => 'quota_exceeded',
                'user_message' => "Les quotas API sont temporairement saturés. Merci de réessayer dans quelques instants.",
            ];
        }

        return [
            'success' => false,
            'text' => null,
            'status_code' => 503,
            'error_type' => 'api_error',
            'user_message' => "Le service IA de secours est temporairement indisponible.",
        ];
    }
}