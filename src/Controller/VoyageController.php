<?php

namespace App\Controller;

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
    #[Route('/', name: 'app_voyage_index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        OpenWeatherService $openWeatherService,
        PaginatorInterface $paginator
    ): Response {
        $search = trim((string) $request->query->get('search', ''));
        $sort = trim((string) $request->query->get('sort', ''));

        $qb = $entityManager->getRepository(Voyage::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')
            ->addSelect('c');

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
            6
        );

        $weatherData = [];

        foreach ($voyages as $voyage) {
            $destination = trim((string) $voyage->getDestination());

            if ($destination !== '') {
                $weatherData[$voyage->getId()] = $openWeatherService->getWeatherByCity($destination);
            } else {
                $weatherData[$voyage->getId()] = null;
            }
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
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $voyage->setPlacesRestantes($voyage->getPlacesTotal());

            if ($form->isValid()) {
                $entityManager->persist($voyage);
                $entityManager->flush();

                $this->addFlash('success', 'Voyage ajouté avec succès.');
                return $this->redirectToRoute('app_voyage_index');
            }
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
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $weather = null;
        $destination = trim((string) $voyage->getDestination());

        if ($destination !== '') {
            $weather = $openWeatherService->getWeatherByCity($destination);
        }

        return $this->render('admin/voyage/show.html.twig', [
            'voyage' => $voyage,
            'weather' => $weather,
        ]);
    }

    #[Route('/edit/{id}', name: 'app_voyage_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($voyage->getPlacesRestantes() === null) {
                $voyage->setPlacesRestantes($voyage->getPlacesTotal());
            }

            if ($form->isValid()) {
                $entityManager->flush();

                $this->addFlash('success', 'Voyage modifié avec succès.');
                return $this->redirectToRoute('app_voyage_index');
            }
        }

        return $this->render('admin/voyage/edit.html.twig', [
            'form' => $form->createView(),
            'voyage' => $voyage,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_voyage_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function delete(int $id, EntityManagerInterface $entityManager): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $entityManager->remove($voyage);
        $entityManager->flush();

        $this->addFlash('success', 'Voyage supprimé avec succès.');
        return $this->redirectToRoute('app_voyage_index');
    }
}