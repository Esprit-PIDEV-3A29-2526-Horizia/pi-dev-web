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
            $data = json_decode($request->getContent(), true);

            $message = trim((string) ($data['message'] ?? ''));

            if ($message === '') {
                return $this->json([
                    'success' => false,
                    'reply' => 'Veuillez écrire un message.',
                    'reply_html' => null,
                ], 200);
            }

            $result = $aiChatbotService->ask($message);

            return $this->json([
                'success' => true,
                'reply' => (string) ($result['reply'] ?? 'Réponse vide.'),
                'reply_html' => $result['reply_html'] ?? null,
            ], 200);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'reply' => 'ERREUR EXACTE : ' . $e->getMessage(),
                'reply_html' => null,
            ], 200);
        }
    }
}