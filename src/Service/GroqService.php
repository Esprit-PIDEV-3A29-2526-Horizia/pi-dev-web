<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function getRecommendations(string $prompt): ?string
    {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        
        $maxRetries = 2;
        $retryCount = 0;

        while ($retryCount <= $maxRetries) {
            try {
                $response = $this->httpClient->request('POST', $url, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'model' => 'llama-3.1-8b-instant',
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.3,
                        'max_tokens' => 300,
                    ],
                    'timeout' => 25,
                    'connect_timeout' => 10,
                ]);

                if ($response->getStatusCode() === 200) {
                    $data = json_decode($response->getContent(false), true);
                    return trim($data['choices'][0]['message']['content'] ?? '');
                }
                
                throw new \Exception("HTTP " . $response->getStatusCode());
                
            } catch (\Exception $e) {
                $retryCount++;
                if ($retryCount > $maxRetries) {
                    return null; // Graceful fallback
                }
                sleep(1);
            }
        }
        return null;
    }
}