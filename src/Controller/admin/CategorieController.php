<?php

namespace App\Controller\admin;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categorie', name: 'admin_categorie_')]
class CategorieController extends AbstractController
{
    /**
     * Vérifie que l'utilisateur a les droits d'admin
     */
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    /**
     * Liste paginée des catégories avec recherche et tri
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {
        $this->checkAdminAccess();

        $search = trim((string) $request->query->get('search', ''));
        $sort = trim((string) $request->query->get('sort', ''));
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $qb = $entityManager->getRepository(Categorie::class)->createQueryBuilder('c');

        if ($search !== '') {
            $qb->andWhere('c.nom LIKE :search OR c.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        switch ($sort) {
            case 'nom_asc':
                $qb->orderBy('c.nom', 'ASC');
                break;
            case 'nom_desc':
                $qb->orderBy('c.nom', 'DESC');
                break;
            default:
                $qb->orderBy('c.id', 'DESC');
                break;
        }

        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        try {
            $categories = $qb->getQuery()->getResult();

            $countQb = $entityManager->getRepository(Categorie::class)->createQueryBuilder('c')
                ->select('COUNT(c.id)');

            if ($search !== '') {
                $countQb->andWhere('c.nom LIKE :search OR c.description LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
            }

            $total = (int) $countQb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du chargement des catégories.');
            $categories = [];
            $total = 0;
        }

        $totalCategories = $entityManager->getRepository(Categorie::class)->count([]);

        return $this->render('admin/categorie/index.html.twig', [
            'categories' => $categories,
            'total' => $total,
            'totalCategories' => $totalCategories,
            'currentPage' => $page,
            'search' => $search,
            'sort' => $sort,
            'limit' => $limit,
        ]);
    }

    /**
     * Créer une nouvelle catégorie
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 👇 AJOUTER CETTE LIGNE pour assigner l'admin connecté
            $categorie->setCreatedBy($this->getUser());
            
            $entityManager->persist($categorie);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('admin_categorie_index');
        }

        return $this->render('admin/categorie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Afficher les détails d'une catégorie
     */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        $categorie = $entityManager->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        return $this->render('admin/categorie/show.html.twig', [
            'categorie' => $categorie,
        ]);
    }

    /**
     * Modifier une catégorie existante
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();

        $categorie = $entityManager->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');
            return $this->redirectToRoute('admin_categorie_index');
        }

        return $this->render('admin/categorie/edit.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }

    /**
     * Supprimer une catégorie
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $this->checkAdminAccess();

        $categorie = $entityManager->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            $voyagesCount = $entityManager->createQueryBuilder()
                ->select('COUNT(v.id)')
                ->from('App\Entity\Voyage', 'v')
                ->where('v.categorie = :categorie')
                ->setParameter('categorie', $categorie)
                ->getQuery()
                ->getSingleScalarResult();

            if ($voyagesCount > 0) {
                $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient ' . $voyagesCount . ' voyage(s).');
            } else {
                $entityManager->remove($categorie);
                $entityManager->flush();
                $this->addFlash('success', 'Catégorie supprimée avec succès.');
            }
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('admin_categorie_index');
    }
}