<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageGeneratorService
{
    private $httpClient;
    private $apiKey;

    // Si tu utilises Hugging Face, récupère un token gratuit sur huggingface.co/settings/tokens
    public function __construct(HttpClientInterface $httpClient, string $huggingFaceToken)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $huggingFaceToken;
    }

    public function generateImage(string $prompt): ?string
    {
        // Utilisation du modèle Stable Diffusion sur Hugging Face
        $response = $this->httpClient->request('POST', 'https://api-inference.huggingface.co/models/stabilityai/stable-diffusion-2-1', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'inputs' => $prompt,
                'options' => ['wait_for_model' => true],
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception('Erreur API Hugging Face : ' . $response->getContent(false));
        }

        // L’API retourne l’image en binaire
        $imageBinary = $response->getContent();
        // Générer un nom unique
        $filename = uniqid() . '.png';
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        file_put_contents($uploadDir . '/' . $filename, $imageBinary);

        return '/images/' . $filename;
    }
}