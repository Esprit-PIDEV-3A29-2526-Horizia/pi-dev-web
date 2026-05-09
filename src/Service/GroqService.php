<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqService
{
    private string $apiKey;
    private string $model;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?string $apiKey,
        ?string $model,
        private LoggerInterface $logger
    ) {
        $this->apiKey = (string) ($apiKey ?? '');
        $this->model = (string) ($model ?? '');
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
                'user_message' => "Clé Groq absente. Ajoute GROQ_API_KEY dans le fichier .env.",
            ];
        }

        $models = array_values(array_unique(array_filter([
            trim($this->model),
            'llama-3.1-8b-instant',
            'llama-3.3-70b-versatile',
        ])));

        $hadQuotaError = false;

        foreach ($models as $model) {
            try {
                $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
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
                    $this->logger->warning('Groq quota exceeded for model.', ['model' => $model]);
                    continue;
                }

                if ($statusCode >= 400) {
                    $this->logger->error('Groq API error', [
                        'model' => $model,
                        'status' => $statusCode,
                        'response' => $content,
                    ]);
                    continue;
                }

                $data = json_decode($content, true);
                $text = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

                if ($text === '') {
                    $this->logger->warning('Groq empty response for model.', ['model' => $model]);
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
                $this->logger->error('Groq network error', [
                    'model' => $model,
                    'message' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'text' => null,
                    'status_code' => 503,
                    'error_type' => 'network_error',
                    'user_message' => "Impossible de contacter le service IA Groq (problème réseau).",
                ];
            } catch (\Throwable $e) {
                $this->logger->error('Groq exception', [
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
                'user_message' => "Le quota Groq est temporairement dépassé. Merci de réessayer dans quelques instants.",
            ];
        }

        return [
            'success' => false,
            'text' => null,
            'status_code' => 503,
            'error_type' => 'api_error',
            'user_message' => "Le service IA Groq est temporairement indisponible.",
        ];
    }
}