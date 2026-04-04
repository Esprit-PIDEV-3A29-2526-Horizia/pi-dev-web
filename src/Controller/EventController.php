<?php

namespace App\Controller;

use App\Entity\Events;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/event')]
class EventController extends AbstractController
{
    #[Route('/dashboard', name: 'app_event_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $entityManager): Response
    {
        $repo = $entityManager->getRepository(Events::class);

        $totalEvents     = count($repo->findAll());
        $eventsAVenir    = count($repo->findBy(['statut' => 'à venir']));
        $eventsEnCours   = count($repo->findBy(['statut' => 'en cours']));
        $eventsTermines  = count($repo->findBy(['statut' => 'terminé']));

        $recentEvents = $repo->createQueryBuilder('e')
            ->orderBy('e.created_at', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/event/dashboard.html.twig', [
            'totalEvents'    => $totalEvents,
            'eventsAVenir'   => $eventsAVenir,
            'eventsEnCours'  => $eventsEnCours,
            'eventsTermines' => $eventsTermines,
            'recentEvents'   => $recentEvents,
        ]);
    }

    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('search');
        $sort   = $request->query->get('sort');

        $qb = $entityManager->getRepository(Events::class)
            ->createQueryBuilder('e');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.categorie LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        switch ($sort) {
            case 'prix_asc':  $qb->orderBy('e.prix', 'ASC');       break;
            case 'prix_desc': $qb->orderBy('e.prix', 'DESC');      break;
            case 'date_asc':  $qb->orderBy('e.date_debut', 'ASC'); break;
            default:          $qb->orderBy('e.id_event', 'DESC');  break;
        }

        $events = $qb->getQuery()->getResult();

        return $this->render('admin/event/index.html.twig', [
            'events' => $events,
            'search' => $search,
            'sort'   => $sort,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Events();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setPlaces_restantes($event->getCapacite_max());
            $event->setCreated_at(new \DateTime());
            $event->setId_createur(1); // replace with real user id later
            $entityManager->persist($event);
            $entityManager->flush();
            $this->addFlash('success', 'Événement ajouté avec succès.');
            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Events::class)->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('app_event_index');
        }

        return $this->render('admin/event/edit.html.twig', [
            'form'  => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Events::class)->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId_event(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Événement supprimé avec succès.');
        }

        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Events::class)->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        return $this->render('admin/event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/events', name: 'app_front_events', methods: ['GET'])]
    public function publicEvents(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search     = $request->query->get('search');
        $priceLimit = $request->query->get('price_limit');

        $qb = $entityManager->getRepository(Events::class)
            ->createQueryBuilder('e')
            ->orderBy('e.date_debut', 'ASC');

        if ($search) {
            $qb->andWhere('e.titre LIKE :search OR e.location LIKE :search OR e.categorie LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($priceLimit) {
            $qb->andWhere('e.prix <= :priceLimit')
               ->setParameter('priceLimit', $priceLimit);
        }

        $events = $qb->getQuery()->getResult();

        return $this->render('front/events.html.twig', [
            'events'      => $events,
            'search'      => $search,
            'price_limit' => $priceLimit,
        ]);
    }

    #[Route('/event/{id}', name: 'app_front_event_show', methods: ['GET'])]
    public function publicShow(int $id, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Events::class)->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Event not found.');
        }

        return $this->render('front/event_show.html.twig', [
            'event' => $event,
        ]);
    }
}