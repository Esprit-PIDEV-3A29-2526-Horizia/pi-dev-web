<?php

namespace App\Controller\Api;

// Supprimer cette ligne
// use App\Service\SpeechToTextService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SpeechController extends AbstractController
{
    #[Route('/api/transcribe', name: 'api_transcribe', methods: ['POST'])]
    public function transcribe(Request $request): JsonResponse
    {
        $audioFile = $request->files->get('audio');
        if (!$audioFile) {
            return $this->json(['error' => 'Fichier audio manquant'], 400);
        }

        // TODO: Implémenter la transcription plus tard
        // $tempPath = $audioFile->getPathname();
        // $text = $stt->transcribe($tempPath);

        return $this->json(['text' => 'Fonctionnalité à venir']);
    }
}