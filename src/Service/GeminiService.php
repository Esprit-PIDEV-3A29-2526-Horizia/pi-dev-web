<?php
// src/Service/GeminiService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

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

    public function generateRecommendations(string $prompt): string
    {
        // Construction correcte du payload Gemini
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Erreur encodage JSON: ' . json_last_error_msg());
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $this->apiKey;

        $response = $this->httpClient->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $jsonPayload,
        ]);

        $statusCode = $response->getStatusCode();
        $content = $response->getContent(false);

        if ($statusCode !== 200) {
            $this->logger->error('Gemini API error', ['status' => $statusCode, 'response' => $content]);
            throw new \Exception("Gemini API error: $statusCode - $content");
        }

        $data = json_decode($content, true);
        if (isset($data['error'])) {
            throw new \Exception($data['error']['message']);
        }

        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }
}