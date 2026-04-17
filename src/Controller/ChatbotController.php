<?php

namespace App\Controller;

use App\Service\AiChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    /**
     * @Route("/chatbot/message", name="app_chatbot_message", methods={"POST"})
     */
    public function message(Request $request, AiChatbotService $aiChatbotService): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $this->json([
                'reply' => 'Requête invalide.',
                'reply_html' => null,
            ], 400);
        }

        $message = trim($data['message'] ?? '');

        if ($message === '') {
            return $this->json([
                'reply' => 'Veuillez écrire un message.',
                'reply_html' => null,
            ], 400);
        }

        try {
            $result = $aiChatbotService->ask($message);

            return $this->json([
                'reply' => $result['reply'] ?? '',
                'reply_html' => $result['reply_html'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'reply' => 'Erreur serveur : ' . $e->getMessage(),
                'reply_html' => null,
            ], 500);
        }
    }
}