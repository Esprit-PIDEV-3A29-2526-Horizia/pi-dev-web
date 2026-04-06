<?php
// src/Controller/LogementController.php

namespace App\Controller;

use App\Entity\Logement;
use App\Form\LogementType;
use App\Service\LogementSearchService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/logement', name: 'admin_logement_')]
class LogementController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        LogementSearchService $searchService,
        ManagerRegistry $doctrine  // Injection du ManagerRegistry
    ): Response {
        $search = $request->query->get('search');
        $disponibilite = $request->query->get('disponibilite', 'all');
        $sort = $request->query->get('sort', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9;

        // Nombre total de logements (avec filtres)
        $total = $searchService->countForAdmin($search, $disponibilite);

        // Récupération des logements paginés
        $logements = $searchService->searchAndSortForAdmin($search, $disponibilite, $sort, $page, $limit);

        // Nombre total de logements (sans aucun filtre, pour le badge)
        $totalLogements = $doctrine->getRepository(Logement::class)->count([]);

        return $this->render('admin/logement/index.html.twig', [
            'logements'      => $logements,
            'total'          => $total,
            'totalLogements' => $totalLogements,
            'currentPage'    => $page,
            'search'         => $search,
            'disponibilite'  => $disponibilite,
            'sort'           => $sort,
            'limit'          => $limit,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement, ['validation_groups' => ['Default', 'create']]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Logement ajouté avec succès.');
            return $this->redirectToRoute('admin_logement_index');
        }

        return $this->render('admin/logement/new.html.twig', [
            'logement' => $logement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Logement $logement): Response
    {
        return $this->render('admin/logement/show.html.twig', [
            'logement' => $logement,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Logement modifié avec succès.');
            return $this->redirectToRoute('admin_logement_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Erreur de validation. Vérifiez les champs.');
        }

        return $this->render('admin/logement/edit.html.twig', [
            'logement' => $logement,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Logement $logement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $logement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($logement);
            $entityManager->flush();
            $this->addFlash('success', 'Logement supprimé avec succès.');
        }

        return $this->redirectToRoute('admin_logement_index');
    }
}