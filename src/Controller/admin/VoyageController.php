<?php

namespace App\Controller\admin;

use App\Entity\Voyage;
use App\Form\VoyageType;
use App\Service\GeminiService;
use App\Service\OpenWeatherService;
use App\Service\PexelsService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/voyage')]
class VoyageController extends AbstractController
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        $session = $requestStack->getSession();
        if ($session && !$session->isStarted()) {
            $session->start();
        }
    }

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
            10
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
        $this->checkAdminAccess();

        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $voyage->setPlacesRestantes($voyage->getPlacesTotal());
            
            // 👇 AJOUTER CETTE LIGNE pour assigner l'admin connecté
            $voyage->setCreatedBy($this->getUser());

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
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();

        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
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

        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $voyage->getId(), $request->request->get('_token'))) {
            $entityManager->remove($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage supprimé avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. Suppression impossible.');
        }

        return $this->redirectToRoute('app_voyage_index');
    }

#[Route('/generate-content', name: 'app_voyage_generate_content', methods: ['POST'])]
    public function generateContent(
        Request $request,
        GeminiService $geminiService,
        PexelsService $pexelsService
        ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Requête invalide.',
            ], 400);
        }

        $destination = trim((string) ($data['destination'] ?? ''));

        if ($destination === '') {
            return $this->json([
                'success' => false,
                'message' => 'Destination obligatoire.',
            ], 400);
        }

        $generated = $geminiService->generateVoyageContent($destination);

        $titre = trim((string) ($generated['titre'] ?? ''));
        $description = trim((string) ($generated['description'] ?? ''));
        $pays = trim((string) ($generated['pays'] ?? ''));
        $imagePrompt = trim((string) ($generated['image_prompt'] ?? ''));

        if ($titre === '') {
            $titre = 'Voyage ' . ucfirst($destination);
        }

        if ($description === '') {
            $description = 'Découvrez ' . ucfirst($destination) . ', une destination fascinante qui séduit par son atmosphère unique, son patrimoine culturel et la richesse de son histoire. Entre sites emblématiques, quartiers animés, traditions locales et plaisirs gastronomiques, ce voyage promet une expérience complète mêlant découverte, détente et immersion.';
        }

        $queries = array_filter([
            $destination,
            $destination . ' city',
            $destination . ' tourism',
            $pays !== '' ? $destination . ' ' . $pays : null,
            $pays !== '' ? $pays . ' travel' : null,
            $imagePrompt !== '' ? $imagePrompt : null,
        ]);

        $imageUrl = null;
        foreach ($queries as $query) {
            $imageUrl = $pexelsService->searchImage($query);
            if ($imageUrl) {
                break;
            }
        }

        return $this->json([
            'success' => true,
            'titre' => $titre,
            'description' => $description,
            'image_prompt' => $imagePrompt,
            'pays' => $pays,
            'image_url' => $imageUrl,
            'debug_query' => $queries,
        ]);
    }
}