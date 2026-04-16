<?php

namespace App\Controller;

use App\Entity\Events;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EventsCalendarController extends AbstractController
{
    #[Route('/events/calendar', name: 'app_events_calendar_front')]
    public function calendarFront(): Response
    {
        return $this->render('front/event/calendar.html.twig', [
            'is_admin' => false,
        ]);
    }

    #[Route('/admin/events/calendar', name: 'app_events_calendar_admin')]
    public function calendarAdmin(): Response
    {
        return $this->render('admin/event/calendar.html.twig', [
            'is_admin' => true,
        ]);
    }

    #[Route('/api/events/calendar-data', name: 'api_events_calendar_data')]
    public function apiEventsData(EntityManagerInterface $entityManager): JsonResponse
    {
        $events = $entityManager->getRepository(Events::class)->findAll();
        
        $formattedEvents = [];
        foreach ($events as $event) {
            // Determine color based on status
            $color = match($event->getStatut()) {
                'à venir' => '#10b981',    // Green
                'en cours' => '#f59e0b',   // Orange
                'terminé' => '#6b7280',    // Gray
                default => '#3b82f6',       // Blue
            };
            
            $formattedEvents[] = [
                'id' => $event->getId_event(),
                'title' => $event->getTitre(),
                'start' => $event->getDate_debut()->format('Y-m-d H:i:s'),
                'end' => $event->getDate_fin()->format('Y-m-d H:i:s'),
                'color' => $color,
                'status' => $event->getStatut(),
                'category' => $event->getCategorie(),
                'location' => $event->getLocation(),
                'prix' => $event->getPrix(),
                'places_restantes' => $event->getPlaces_restantes(),
            ];
        }
        
        return $this->json($formattedEvents);
    }
}