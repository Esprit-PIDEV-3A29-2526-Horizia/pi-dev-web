<?php

namespace App\Controller\Api;

use App\Service\AiImageGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/api/generate-image', name: 'api_generate_image', methods: ['POST'])]
    public function generate(Request $request, AiImageGenerator $imageGenerator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $prompt = trim($data['prompt'] ?? '');

        if (empty($prompt)) {
            return $this->json(['error' => 'La description est obligatoire.'], 400);
        }

        $imagePath = $imageGenerator->generateImage($prompt);
        if (!$imagePath) {
            return $this->json(['error' => 'Échec de la génération.'], 500);
        }

        return $this->json(['imageUrl' => $imagePath]);
    }
}