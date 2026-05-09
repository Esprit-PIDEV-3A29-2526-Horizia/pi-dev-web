<?php

namespace App\Controller\admin;

use App\Entity\User;
use App\Entity\Voyage;
use App\Form\VoyageType;
use App\Service\OpenWeatherService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/voyage')]
class VoyageController extends AbstractController
{
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    #[Route('/', name: 'app_voyage_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        OpenWeatherService $openWeatherService,
        PaginatorInterface $paginator
    ): Response {
        $this->checkAdminAccess();

        $search = trim((string) $request->query->get('search', ''));
        $sort = trim((string) $request->query->get('sort', ''));

        $qb = $entityManager->createQueryBuilder()
            ->select('v', 'c')
            ->from(Voyage::class, 'v')
            ->leftJoin('v.categorie', 'c');

        if ($search !== '') {
            $qb->andWhere('LOWER(v.titre) LIKE :search OR LOWER(v.destination) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        switch ($sort) {
            case 'prix_asc':
                $qb->orderBy('v.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('v.prix', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('v.dateDepart', 'ASC');
                break;
            case 'date_desc':
                $qb->orderBy('v.dateDepart', 'DESC');
                break;
            case 'titre_asc':
                $qb->orderBy('v.titre', 'ASC');
                break;
            case 'titre_desc':
                $qb->orderBy('v.titre', 'DESC');
                break;
            default:
                $qb->orderBy('v.id', 'DESC');
                break;
        }

        $voyages = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            10
        );

        $weatherData = [];

        foreach ($voyages as $voyage) {
            if (!$voyage instanceof Voyage) {
                continue;
            }

            $destination = trim((string) $voyage->getDestination());

            $weatherData[$voyage->getId()] = $destination !== ''
                ? $openWeatherService->getWeatherByCity($destination)
                : null;
        }

        return $this->render('admin/voyage/index.html.twig', [
            'voyages' => $voyages,
            'weatherData' => $weatherData,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_voyage_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $voyage->setPlacesRestantes($voyage->getPlacesTotal());

            $user = $this->getUser();

            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Utilisateur invalide.');
            }

            $voyage->setCreatedBy($user);

            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage ajouté avec succès.');

            return $this->redirectToRoute('app_voyage_index');
        }

        return $this->render('admin/voyage/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_voyage_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager,
        OpenWeatherService $openWeatherService
    ): Response {
        $this->checkAdminAccess();

        $voyage = $entityManager->find(Voyage::class, $id);

        if (!$voyage instanceof Voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $destination = trim((string) $voyage->getDestination());

        $weather = $destination !== ''
            ? $openWeatherService->getWeatherByCity($destination)
            : null;

        return $this->render('admin/voyage/show.html.twig', [
            'voyage' => $voyage,
            'weather' => $weather,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_voyage_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();

        $voyage = $entityManager->find(Voyage::class, $id);

        if (!$voyage instanceof Voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($voyage->getPlacesRestantes() === null) {
                $voyage->setPlacesRestantes($voyage->getPlacesTotal());
            }

            $entityManager->flush();

            $this->addFlash('success', 'Voyage modifié avec succès.');

            return $this->redirectToRoute('app_voyage_index');
        }

        return $this->render('admin/voyage/edit.html.twig', [
            'form' => $form->createView(),
            'voyage' => $voyage,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_voyage_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();

        $voyage = $entityManager->find(Voyage::class, $id);

        if (!$voyage instanceof Voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $voyage->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression impossible.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }
}