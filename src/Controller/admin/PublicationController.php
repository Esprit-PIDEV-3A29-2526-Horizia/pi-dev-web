<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Service\AiImageGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/publication')]
class PublicationController extends AbstractController
{
    #[Route('/', name: 'admin_publication_index', methods: ['GET'])]
    public function index(Request $request, PublicationRepository $repo, PaginatorInterface $paginator): Response
    {
        // ... (identique à votre version)
        $sort = $request->query->get('sort', 'date_creation');
        $direction = $request->query->get('direction', 'DESC');
        $allowedSorts = ['id', 'titre', 'categorie', 'date_creation', 'likes', 'commentaires'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date_creation';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $queryBuilder = $repo->createQueryBuilder('p')->orderBy('p.' . $sort, $direction);

        $search = $request->query->get('search');
        if ($search) {
            $queryBuilder->andWhere('p.titre LIKE :search OR p.description LIKE :search')
                         ->setParameter('search', '%' . $search . '%');
        }
        $categorie = $request->query->get('categorie');
        if ($categorie) {
            $queryBuilder->andWhere('p.categorie = :categorie')
                         ->setParameter('categorie', $categorie);
        }

        $publications = $paginator->paginate($queryBuilder->getQuery(), $request->query->getInt('page', 1), 6);

        return $this->render('admin/publication/index.html.twig', [
            'publications' => $publications,
            'current_sort' => $sort,
            'current_direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'admin_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AiImageGenerator $aiImageGenerator): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Image téléchargée manuellement
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            // 2. Génération d'image par IA (si aucune image téléchargée)
            if (!$publication->getImage()) {
                $imagePrompt = $form->get('imagePrompt')->getData();
                if ($imagePrompt) {
                    $generatedImage = $aiImageGenerator->generateImage($imagePrompt);
                    if ($generatedImage) {
                        $publication->setImage($generatedImage);
                    }
                }
            }

            // 3. Conversion des tags
            $tagsString = $form->get('tags')->getData();
            if ($tagsString) {
                $tagsArray = array_map('trim', explode(',', $tagsString));
                $publication->setTags($tagsArray);
            }

            $em->persist($publication);
            $em->flush();
            $this->addFlash('success', 'Publication créée avec succès.');
            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/publication/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'admin_publication_show', methods: ['GET'])]
    public function show(Publication $publication, CommentaireRepository $commentaireRepo): Response
    {
        $commentaires = $commentaireRepo->findBy(['publication' => $publication], ['date_creation' => 'DESC']);
        return $this->render('admin/publication/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_publication_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $em, AiImageGenerator $aiImageGenerator): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 1. Image téléchargée manuellement
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            } else {
                // 2. Génération d'image par IA (si aucun fichier téléchargé)
                $imagePrompt = $form->get('imagePrompt')->getData();
                if ($imagePrompt) {
                    $generatedImage = $aiImageGenerator->generateImage($imagePrompt);
                    if ($generatedImage) {
                        $publication->setImage($generatedImage);
                    }
                }
            }

            // 3. Conversion des tags
            $tagsString = $form->get('tags')->getData();
            if ($tagsString) {
                $tagsArray = array_map('trim', explode(',', $tagsString));
                $publication->setTags($tagsArray);
            } else {
                $publication->setTags(null);
            }

            $em->flush();
            $this->addFlash('success', 'Publication modifiée.');
            return $this->redirectToRoute('admin_publication_index');
        }

        return $this->render('admin/publication/edit.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_publication_delete', methods: ['POST'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $publication->getId(), $request->request->get('_token'))) {
            $em->remove($publication);
            $em->flush();
            $this->addFlash('success', 'Publication supprimée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('admin_publication_index');
    }
}