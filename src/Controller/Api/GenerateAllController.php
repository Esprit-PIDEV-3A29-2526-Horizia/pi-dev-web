<?php

namespace App\Controller\Api;

use App\Service\AiContentGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GenerateAllController extends AbstractController
{
    #[Route('/api/generate-all', name: 'api_generate_all', methods: ['POST'])]
    public function generate(Request $request, AiContentGenerator $generator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $sujet = trim($data['sujet'] ?? '');
        if (empty($sujet)) {
            return $this->json(['error' => 'Veuillez entrer un sujet.'], 400);
        }
        $result = $generator->generateAll($sujet);
        return $this->json($result);
    }
}