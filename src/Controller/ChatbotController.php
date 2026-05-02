<?php

namespace App\Controller;

use App\Service\AiChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/chatbot/message', name: 'app_chatbot_message', methods: ['POST'])]
    public function message(Request $request, AiChatbotService $aiChatbotService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json([
                'success' => false,
                'reply' => 'Requête invalide.',
                'reply_html' => null,
            ], 400);
        }

        $message = trim((string) ($data['message'] ?? ''));

        if ($message === '') {
            return $this->json([
                'success' => false,
                'reply' => 'Veuillez écrire un message.',
                'reply_html' => null,
            ], 400);
        }

        try {
            $result = $aiChatbotService->ask($message);

            return $this->json([
                'success' => true,
                'reply' => $result['reply'],
                'reply_html' => $result['reply_html'],
            ], 200);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'reply' => 'Erreur serveur : ' . $e->getMessage(),
                'reply_html' => null,
            ], 500);
        }
    }
}