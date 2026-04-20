<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiImageGenerator
{
    public function __construct(private HttpClientInterface $httpClient) {}

    public function generateImage(string $prompt): ?string
    {
        $encodedPrompt = urlencode($prompt);
        $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512";

        try {
            $response = $this->httpClient->request('GET', $url);
            $imageContent = $response->getContent();

            // Créer le dossier d’upload s’il n’existe pas
            $uploadDir = __DIR__ . '/../../public/uploads/ai_images';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = uniqid('ai_image_') . '.png';
            file_put_contents($uploadDir . '/' . $filename, $imageContent);

            return '/uploads/ai_images/' . $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}