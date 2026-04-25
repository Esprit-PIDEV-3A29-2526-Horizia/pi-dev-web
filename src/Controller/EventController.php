<?php

namespace App\Controller;

use App\Entity\Events;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

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
        $page   = $request->query->getInt('page', 1);
        $limit  = 6; // Items per page

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

        // Get total count for pagination
        $totalEvents = $qb->select('COUNT(e.id_event)')
                          ->getQuery()
                          ->getSingleScalarResult();

        $totalPages = ceil($totalEvents / $limit);
        
        // Ensure page is valid
        if ($page < 1) $page = 1;
        if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
        
        $offset = ($page - 1) * $limit;
        
        // Get paginated results
        $events = $qb->select('e')
                     ->setFirstResult($offset)
                     ->setMaxResults($limit)
                     ->getQuery()
                     ->getResult();

        return $this->render('admin/event/index.html.twig', [
            'events'        => $events,
            'total_events'  => $totalEvents,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'limit'         => $limit,
            'search'        => $search,
            'sort'          => $sort,
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = new Events();
        $form  = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image_file')->getData();
            
            if ($imageFile) {
                // Get extension safely from original filename
                $originalExtension = $imageFile->getClientOriginalExtension();
                
                // Validate extension manually
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($originalExtension), $allowedExtensions)) {
                    $this->addFlash('error', 'Extension de fichier non autorisée. Utilisez JPG, PNG, GIF ou WEBP.');
                    return $this->redirectToRoute('app_event_new');
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $originalExtension;
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImage_url('/uploads/events/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('app_event_new');
                }
            } elseif ($form->get('image_url')->getData()) {
                $event->setImage_url($form->get('image_url')->getData());
            }
            
            $event->setPlaces_restantes($event->getCapacite_max());
            $event->setCreated_at(new \DateTime());
            $event->setId_createur(1);
            
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'Événement ajouté avec succès.');
            return $this->redirectToRoute('app_event_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs dans le formulaire.');
        }

        return $this->render('admin/event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $event = $entityManager->getRepository(Events::class)->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_file')->getData();
            
            if ($imageFile) {
                $oldImage = $event->getImage_url();
                if ($oldImage && strpos($oldImage, '/uploads/events/') === 0) {
                    $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $oldImage;
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
                
                // Get extension safely from original filename
                $originalExtension = $imageFile->getClientOriginalExtension();
                
                // Validate extension manually
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array(strtolower($originalExtension), $allowedExtensions)) {
                    $this->addFlash('error', 'Extension de fichier non autorisée. Utilisez JPG, PNG, GIF ou WEBP.');
                    return $this->redirectToRoute('app_event_edit', ['id' => $id]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $originalExtension;
                
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/events',
                        $newFilename
                    );
                    $event->setImage_url('/uploads/events/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->redirectToRoute('app_event_edit', ['id' => $id]);
                }
            } elseif ($form->get('image_url')->getData()) {
                $event->setImage_url($form->get('image_url')->getData());
            }
            
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
            $imageUrl = $event->getImage_url();
            if ($imageUrl && strpos($imageUrl, '/uploads/events/') === 0) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $imageUrl;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
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
}