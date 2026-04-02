<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Form\LogementType;
use App\Repository\LogementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/logement', name: 'admin_logement_')]
class LogementController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(Request $request, LogementRepository $logementRepository): Response
    {
        // Paramètres GET avec valeurs par défaut
        $search = $request->query->get('search');
        $disponibilite = $request->query->get('disponibilite', 'all');
        $sort = $request->query->get('sort', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 9;

        // Construction de la requête
        $qb = $logementRepository->createQueryBuilder('l');

        // Recherche
        if ($search) {
            $qb->andWhere('l.nom LIKE :search OR l.adresse LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre disponibilité
        if ($disponibilite === 'available') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', true);
        } elseif ($disponibilite === 'unavailable') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', false);
        }

        // Comptage total pour pagination
        $total = (clone $qb)->select('COUNT(l.id)')->getQuery()->getSingleScalarResult();

        // Tri
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('l.tarif_nuit', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('l.tarif_nuit', 'DESC');
                break;
            case 'dispo_asc':
                $qb->orderBy('l.disponibilite', 'ASC');
                break;
            case 'dispo_desc':
                $qb->orderBy('l.disponibilite', 'DESC');
                break;
            default:
                $qb->orderBy('l.id', 'DESC');
        }

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $logements = $qb->getQuery()->getResult();

        // Nombre total de logements (sans filtre)
        $totalLogements = $logementRepository->count([]);

        return $this->render('admin/logement/index.html.twig', [
            'logements' => $logements,
            'total' => $total,
            'totalLogements' => $totalLogements,
            'currentPage' => $page,
            'search' => $search,
            'disponibilite' => $disponibilite,
            'sort' => $sort,
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

    // En cas d’erreur, afficher les messages
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