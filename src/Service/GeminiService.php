<?php
// src/Service/GeminiService.php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }

    public function askForPlacesToVisit(string $prompt): string
    {
        return $this->generateRecommendations($prompt);
    }

    public function generateRecommendations(string $prompt): string
    {
        try {
            $payload = [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
            ];

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;
            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => 30,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->getContent(false);

            if ($statusCode >= 400) {
                $this->logger->error('Gemini API error', [
                    'status' => $statusCode,
                    'response' => $content,
                ]);

                return "Erreur Gemini HTTP $statusCode : " . $content;
            }

            $data = json_decode($content, true);

            if (!is_array($data)) {
                return 'Réponse Gemini invalide.';
            }

            $text = '';

            if (isset($data['candidates'][0]['content']['parts'])) {
                foreach ($data['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['text']) && is_string($part['text'])) {
                        $text .= $part['text'] . "\n";
                    }
                }
            }

            if (trim($text) === '') {
                $this->logger->warning('Gemini empty response', [
                    'response' => $data,
                ]);

                return "Je n'ai pas pu générer une réponse IA pour le moment.";
            }

            return trim($text);

        } catch (\Throwable $e) {
            $this->logger->error('Gemini exception', [
                'message' => $e->getMessage(),
            ]);

            return 'Erreur Gemini : ' . $e->getMessage();
        }
    }
}