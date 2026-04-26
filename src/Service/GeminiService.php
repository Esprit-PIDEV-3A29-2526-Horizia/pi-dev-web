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
    private string  $geminiModel;

    public function __construct(HttpClientInterface $httpClient, string $apiKey, LoggerInterface $logger,string  $geminiModel)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        $this->geminiModel = $geminiModel;
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
    public function generateVoyageContent(string $destination): array
    {
        try {
            $url = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                $this->geminiModel,
                $this->apiKey
            );

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [[
                        'parts' => [[
                            'text' => <<<PROMPT
                    Tu es l’assistant voyage intelligent de Horozia.
                    Réponds toujours en français.

                    Retourne un JSON avec cette structure :
                    {
                    "pays": "string",
                    "description": "string",
                    "image_prompt": "string"
                    }

                    Règles :
                    - "pays" = le pays correspondant à la destination
                    - "description" = une description touristique très détaillée, immersive et attractive, entre 120 et 180 mots
                    - "image_prompt" = une courte description visuelle réaliste de la destination
                    - ne mets aucun texte hors JSON

                    Destination : {$destination}
                    PROMPT
                                            ]]
                                        ]],
                                        'generationConfig' => [
                                            'temperature' => 0.7,
                                            'responseMimeType' => 'application/json'
                                        ]
                                    ],
                                    'timeout' => 30,
                                ]);

                                $statusCode = $response->getStatusCode();
                                $data = $response->toArray(false);

                                if ($statusCode >= 400) {
                                    $this->logger->error('Gemini generateVoyageContent HTTP error', [
                                        'status' => $statusCode,
                                        'response' => $data,
                                        'destination' => $destination,
                                    ]);

                                    return [
                                        'titre' => 'Voyage ' . ucfirst($destination),
                                        'description' => '',
                                        'image_prompt' => 'Belle vue touristique de ' . $destination,
                                        'pays' => $destination,
                                    ];
                                }

                                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                                if ($text === '') {
                                    return [
                                        'titre' => 'Voyage ' . ucfirst($destination),
                                        'description' => '',
                                        'image_prompt' => 'Belle vue touristique de ' . $destination,
                                        'pays' => $destination,
                                    ];
                                }

                                $cleanText = trim($text);
                                $cleanText = preg_replace('/^```json\s*/i', '', $cleanText);
                                $cleanText = preg_replace('/^```\s*/', '', $cleanText);
                                $cleanText = preg_replace('/\s*```$/', '', $cleanText);
                                $cleanText = trim($cleanText);

                                $decoded = json_decode($cleanText, true);

                                if (!is_array($decoded)) {
                                    return [
                                        'titre' => 'Voyage ' . ucfirst($destination),
                                        'description' => $cleanText,
                                        'image_prompt' => 'Belle vue touristique de ' . $destination,
                                        'pays' => $destination,
                                    ];
                                }

                                $pays = trim((string) ($decoded['pays'] ?? ''));
                                $description = trim((string) ($decoded['description'] ?? ''));
                                $imagePrompt = trim((string) ($decoded['image_prompt'] ?? ''));

                                if ($pays === '') {
                                    $pays = $destination;
                                }

                                if ($description === '') {
                                    $description = $cleanText;
                                }

                                return [
                                    'titre' => 'Voyage ' . ucfirst($pays),
                                    'description' => $description,
                                    'image_prompt' => $imagePrompt !== '' ? $imagePrompt : ('Belle vue touristique de ' . $destination),
                                    'pays' => $pays,
                                ];
                            } catch (\Throwable $e) {
                                $this->logger->error('Gemini generate voyage content exception', [
                                    'message' => $e->getMessage(),
                                    'destination' => $destination,
                                ]);

                                return [
                                    'titre' => 'Voyage ' . ucfirst($destination),
                                    'description' => '',
                                    'image_prompt' => 'Belle vue touristique de ' . $destination,
                                    'pays' => $destination,
                                ];
        }
    }
}