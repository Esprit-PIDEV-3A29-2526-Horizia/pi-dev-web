<?php

namespace App\Controller;

use App\Entity\Events;
use App\Service\AiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class AiController extends AbstractController
{
    #[Route('/ai/recommend', name: 'app_ai_recommend', methods: ['POST'])]
    public function recommend(Request $request, AiService $ai, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            $vibe = $data['vibe'] ?? 'fun';
            $budget = $data['budget'] ?? '100';
            $when = $data['when'] ?? 'ce mois';
            
            $events = $em->getRepository(Events::class)
                ->createQueryBuilder('e')
                ->where('e.date_debut >= :now')
                ->setParameter('now', new \DateTime())
                ->orderBy('e.date_debut', 'ASC')
                ->getQuery()
                ->getResult();
            
            $recommendations = $ai->getRecommendations($vibe, $budget, $when, $events);
            
            // Format events to ensure ID is integer
            $formattedEvents = [];
            foreach ($recommendations['events'] as $event) {
                $formattedEvents[] = [
                    'id_event' => (int) $event->getId_event(),  // Force integer
                    'titre' => $event->getTitre(),
                    'categorie' => $event->getCategorie(),
                    'prix' => $event->getPrix(),
                    'location' => $event->getLocation(),
                    'date_debut' => $event->getDate_debut()->format('Y-m-d H:i:s'),
                    'image_url' => $event->getImage_url(),
                ];
            }
            
            return $this->json([
                'success' => true,
                'text' => $recommendations['text'],
                'events' => $formattedEvents
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'text' => '🎉 Voici nos événements disponibles ! ✨',
                'events' => []
            ]);
        }
    }
    
    #[Route('/ai/chat', name: 'app_ai_chat', methods: ['POST'])]
    public function chat(Request $request, AiService $ai, EntityManagerInterface $em): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            
            if (empty($message)) {
                return $this->json(['success' => true, 'reply' => 'Dis-moi ce que tu cherches ! 🎯', 'events' => []]);
            }
            
            $events = $em->getRepository(Events::class)
                ->createQueryBuilder('e')
                ->where('e.date_debut >= :now')
                ->setParameter('now', new \DateTime())
                ->orderBy('e.date_debut', 'ASC')
                ->getQuery()
                ->getResult();
            
            $result = $ai->chat($message, $events);
            
            // Format events to ensure ID is integer
            $formattedEvents = [];
            foreach ($result['events'] as $event) {
                $formattedEvents[] = [
                    'id_event' => (int) $event->getId_event(),  // Force integer
                    'titre' => $event->getTitre(),
                    'categorie' => $event->getCategorie(),
                    'prix' => $event->getPrix(),
                    'location' => $event->getLocation(),
                    'date_debut' => $event->getDate_debut()->format('Y-m-d H:i:s'),
                ];
            }
            
            return $this->json([
                'success' => true,
                'reply' => $result['text'],
                'events' => $formattedEvents
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => true,
                'reply' => '🎉 Découvre nos événements ci-dessus ! 💬',
                'events' => []
            ]);
        }
    }
}