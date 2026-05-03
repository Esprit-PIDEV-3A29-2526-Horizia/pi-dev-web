<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiImageGeneratorManager
{
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Génère une image à partir d'un prompt
     * 
     * @param string $prompt La description de l'image à générer
     * @return string|null Le chemin de l'image générée ou null en cas d'erreur
     */
    public function generateImage(string $prompt): ?string
    {
        $encodedPrompt = urlencode($prompt);
        $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=512&height=512";

        try {
            $response = $this->httpClient->request('GET', $url);
            $imageContent = $response->getContent();

            $uploadDir = __DIR__ . '/../../public/uploads/ai_images';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
                    return null;
                }
            }

            $filename = uniqid('ai_image_', true) . '.png';
            $filepath = $uploadDir . '/' . $filename;
            
            if (file_put_contents($filepath, $imageContent) === false) {
                return null;
            }

            return '/uploads/ai_images/' . $filename;
        } catch (\Exception $e) {
            return null;
        }
    }
}