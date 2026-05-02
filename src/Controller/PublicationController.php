<?php
// src/Controller/PublicationController.php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Entity\Favori;
use App\Entity\User;
use App\Form\CommentaireType;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use App\Repository\FavoriRepository;
use App\Service\AiImageGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicationController extends AbstractController
{
    // ---------- PARTIE PUBLIQUE (FRONT) ----------

    #[Route('/publications', name: 'front_publication_index')]
    public function index(PublicationRepository $repo, PaginatorInterface $paginator, Request $request, FavoriRepository $favoriRepo): Response
    {
        $query = $repo->createQueryBuilder('p')
            ->orderBy('p.date_creation', 'DESC')
            ->getQuery();

        $publications = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        $likedIds = $request->getSession()->get('liked_publications', []);
        
        $favoritesIds = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            $favoris = $favoriRepo->findBy(['user' => $user]);
            $favoritesIds = array_map(fn($f) => $f->getPublication()->getId(), $favoris);
        } else {
            $favoritesIds = $request->getSession()->get('favorite_publications', []);
        }

        return $this->render('front/publication/index.html.twig', [
            'publications' => $publications,
            'likedIds' => $likedIds,
            'favoritesIds' => $favoritesIds,
        ]);
    }

    #[Route('/publication/new', name: 'front_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AiImageGenerator $aiImageGenerator): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $publication->setCreatedBy($user);
                $auteur = $user->getPrenom() . ' ' . $user->getNom();
                $publication->setAuteur($auteur);
                $pseudo = $auteur;
            } else {
                $pseudo = $form->get('pseudo')->getData();
                if (!$pseudo) {
                    $pseudo = 'Anonyme';
                }
                $request->getSession()->set('mon_pseudo', $pseudo);
                $publication->setAuteur($pseudo);
            }

            // Image manuelle
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            // Génération IA
            $imagePrompt = $form->get('imagePrompt')->getData();
            if ($imagePrompt && !$publication->getImage()) {
                $generatedImage = $aiImageGenerator->generateImage($imagePrompt);
                if ($generatedImage) {
                    $publication->setImage($generatedImage);
                }
            }

            // Tags
            $tagsString = $form->get('tags')->getData();
            if ($tagsString && is_string($tagsString)) {
                $tagsArray = array_map('trim', explode(',', $tagsString));
                $publication->setTags($tagsArray);
            }

            $publication->setDateCreation(new \DateTimeImmutable());
            $publication->setLikes(0);
            $publication->setCommentaires(0);

            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', 'Publication créée avec succès !');
            return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
        }

        return $this->render('front/publication/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publication/{id}/edit', name: 'front_publication_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $sessionPseudo = $request->getSession()->get('mon_pseudo');
        $isAuthorized = false;

        if ($user instanceof User && $publication->getCreatedBy() && $user->getId() === $publication->getCreatedBy()->getId()) {
            $isAuthorized = true;
        } elseif ($sessionPseudo && $sessionPseudo === $publication->getAuteur()) {
            $isAuthorized = true;
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à modifier cette publication.');
            return $this->redirectToRoute('front_publication_index');
        }

        $form = $this->createForm(PublicationType::class, $publication);
        
        $existingTags = $publication->getTags();
        if ($existingTags) {
            $form->get('tags')->setData(implode(', ', $existingTags));
        }
        if (!$user instanceof User) {
            $form->get('pseudo')->setData($publication->getAuteur());
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            if (!$user instanceof User) {
                $newPseudo = $form->get('pseudo')->getData();
                if ($newPseudo && is_string($newPseudo)) {
                    $publication->setAuteur($newPseudo);
                    $request->getSession()->set('mon_pseudo', $newPseudo);
                }
            }

            $tagsString = $form->get('tags')->getData();
            if ($tagsString && is_string($tagsString)) {
                $tagsArray = array_map('trim', explode(',', $tagsString));
                $publication->setTags($tagsArray);
            } else {
                $publication->setTags(null);
            }

            $em->flush();
            $this->addFlash('success', 'Publication modifiée.');
            return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
        }

        return $this->render('front/publication/edit.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/publication/{id}/delete', name: 'front_publication_delete', methods: ['POST'])]
    public function delete(Request $request, Publication $publication, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $sessionPseudo = $request->getSession()->get('mon_pseudo');
        $isAuthorized = false;

        if ($user instanceof User && $publication->getCreatedBy() && $user->getId() === $publication->getCreatedBy()->getId()) {
            $isAuthorized = true;
        } elseif ($sessionPseudo && $sessionPseudo === $publication->getAuteur()) {
            $isAuthorized = true;
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à supprimer cette publication.');
            return $this->redirectToRoute('front_publication_index');
        }

        $token = $request->request->get('_token');
        if ($token && is_string($token) && $this->isCsrfTokenValid('delete_publication_' . $publication->getId(), $token)) {
            $em->remove($publication);
            $em->flush();
            $this->addFlash('success', 'Publication supprimée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('front_publication_index');
    }

    #[Route('/publication/{id}', name: 'front_publication_show')]
    public function show(Publication $publication, Request $request, EntityManagerInterface $em, CommentaireRepository $comRepo, FavoriRepository $favoriRepo): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $commentaire->setCreatedBy($user);
                $commentaire->setAuteur($user->getPrenom() . ' ' . $user->getNom());
            } else {
                $auteur = $form->get('auteur')->getData();
                if (!$auteur || !is_string($auteur)) {
                    $auteur = 'Anonyme';
                }
                $commentaire->setAuteur($auteur);
            }
            $commentaire->setPublication($publication);
            $commentaire->setDateCreation(new \DateTimeImmutable());
            $em->persist($commentaire);
            $publication->setCommentaires($publication->getCommentaires() + 1);
            $em->flush();
            $this->addFlash('success', 'Commentaire ajouté.');
            return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
        }

        $likedIds = $request->getSession()->get('liked_publications', []);
        
        $favoritesIds = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            $favoris = $favoriRepo->findBy(['user' => $user]);
            $favoritesIds = array_map(fn($f) => $f->getPublication()->getId(), $favoris);
        } else {
            $favoritesIds = $request->getSession()->get('favorite_publications', []);
        }
        
        $commentaires = $comRepo->findBy(['publication' => $publication], ['date_creation' => 'ASC']);

        return $this->render('front/publication/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
            'commentForm' => $form->createView(),
            'likedIds' => $likedIds,
            'favoritesIds' => $favoritesIds,
        ]);
    }

    #[Route('/commentaire/{id}/edit', name: 'front_commentaire_edit', methods: ['GET', 'POST'])]
    public function editCommentaire(Commentaire $commentaire, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAuthor = false;
        
        if ($user instanceof User && $commentaire->getCreatedBy() && $user->getId() === $commentaire->getCreatedBy()->getId()) {
            $isAuthor = true;
        } elseif ($commentaire->getAuteur() === $request->getSession()->get('mon_pseudo')) {
            $isAuthor = true;
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $isAuthor = true;
        }

        if (!$isAuthor) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce commentaire.');
            return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
        }

        return $this->render('front/commentaire/edit.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaire/{id}/delete', name: 'front_commentaire_delete', methods: ['POST'])]
    public function deleteCommentaire(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $isAuthor = false;
        
        if ($user instanceof User && $commentaire->getCreatedBy() && $user->getId() === $commentaire->getCreatedBy()->getId()) {
            $isAuthor = true;
        } elseif ($commentaire->getAuteur() === $request->getSession()->get('mon_pseudo')) {
            $isAuthor = true;
        } elseif ($this->isGranted('ROLE_ADMIN')) {
            $isAuthor = true;
        }

        if (!$isAuthor) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
        }

        $token = $request->request->get('_token');
        if ($token && is_string($token) && $this->isCsrfTokenValid('delete' . $commentaire->getId(), $token)) {
            $publication = $commentaire->getPublication();
            if ($publication) {
                $publication->setCommentaires(max(0, $publication->getCommentaires() - 1));
            }
            $em->remove($commentaire);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
    }

    #[Route('/my-publications', name: 'front_my_publications')]
    public function myPublications(PublicationRepository $repo, Request $request): Response
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $publications = $repo->findBy(['createdBy' => $user], ['date_creation' => 'DESC']);
        } else {
            $pseudo = $request->getSession()->get('mon_pseudo');
            if (!$pseudo || !is_string($pseudo)) {
                $this->addFlash('info', 'Créez une publication pour définir votre pseudo.');
                return $this->redirectToRoute('front_publication_new');
            }
            $publications = $repo->findBy(['auteur' => $pseudo], ['date_creation' => 'DESC']);
        }

        $likedIds = $request->getSession()->get('liked_publications', []);
        $favoritesIds = $request->getSession()->get('favorite_publications', []);

        return $this->render('front/publication/my_publications.html.twig', [
            'publications' => $publications,
            'likedIds' => $likedIds,
            'favoritesIds' => $favoritesIds,
        ]);
    }

    #[Route('/publication/{id}/like', name: 'front_publication_like', methods: ['POST'])]
    public function like(Publication $publication, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $session = $request->getSession();
        $likedIds = $session->get('liked_publications', []);
        
        if (!is_array($likedIds)) {
            $likedIds = [];
        }
        
        $id = $publication->getId();

        if (in_array($id, $likedIds)) {
            $publication->decrementLikes();
            $likedIds = array_diff($likedIds, [$id]);
            $liked = false;
        } else {
            $publication->incrementLikes();
            $likedIds[] = $id;
            $liked = true;
        }

        $session->set('liked_publications', $likedIds);
        $em->flush();

        return $this->json([
            'likesCount' => $publication->getLikes(),
            'liked' => $liked,
        ]);
    }

    // ---------- FAVORIS ----------
    #[Route('/favorite/{id}/toggle', name: 'front_publication_favorite_toggle', methods: ['POST'])]
    public function toggleFavorite(Publication $publication, Request $request, EntityManagerInterface $em, FavoriRepository $favoriRepo): JsonResponse
    {
        $user = $this->getUser();
        $id = $publication->getId();

        if ($user instanceof User) {
            $favori = $favoriRepo->findOneBy(['user' => $user, 'publication' => $publication]);
            if ($favori) {
                $em->remove($favori);
                $isFavorite = false;
            } else {
                $favori = new Favori();
                $favori->setUser($user);
                $favori->setPublication($publication);
                $em->persist($favori);
                $isFavorite = true;
            }
            $em->flush();
            return $this->json(['isFavorite' => $isFavorite]);
        }
        
        // Anonyme : session
        $session = $request->getSession();
        $favorites = $session->get('favorite_publications', []);
        
        if (!is_array($favorites)) {
            $favorites = [];
        }
        
        if (in_array($id, $favorites)) {
            $favorites = array_diff($favorites, [$id]);
            $isFavorite = false;
        } else {
            $favorites[] = $id;
            $isFavorite = true;
        }
        $session->set('favorite_publications', $favorites);
        
        return $this->json(['isFavorite' => $isFavorite]);
    }

    #[Route('/mes-favoris', name: 'front_my_favorites')]
    public function myFavorites(PublicationRepository $repo, Request $request, FavoriRepository $favoriRepo): Response
    {
        $user = $this->getUser();
        $publications = [];
        $likedIds = $request->getSession()->get('liked_publications', []);
        
        if (!is_array($likedIds)) {
            $likedIds = [];
        }
        
        if ($user instanceof User) {
            $favoris = $favoriRepo->findBy(['user' => $user], ['dateAjout' => 'DESC']);
            $publications = array_map(fn($f) => $f->getPublication(), $favoris);
        } else {
            $favorites = $request->getSession()->get('favorite_publications', []);
            if (!is_array($favorites)) {
                $favorites = [];
            }
            if (!empty($favorites)) {
                $publications = $repo->findBy(['id' => $favorites], ['date_creation' => 'DESC']);
            }
        }
        
        return $this->render('front/publication/favorites.html.twig', [
            'publications' => $publications,
            'likedIds' => $likedIds,
        ]);
    }

    // ---------- PARTIE ADMIN ----------
    #[Route('/admin/publication/{id}', name: 'admin_publication_show', methods: ['GET'])]
    public function adminShow(Publication $publication, CommentaireRepository $comRepo): Response
    {
        $commentaires = $comRepo->findBy(['publication' => $publication], ['date_creation' => 'DESC']);
        return $this->render('admin/publication/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/admin/publication/{id}/commentaires', name: 'admin_publication_commentaires', methods: ['GET'])]
    public function adminCommentaires(Publication $publication, CommentaireRepository $comRepo): Response
    {
        $commentaires = $comRepo->findBy(['publication' => $publication], ['date_creation' => 'DESC']);
        return $this->render('admin/publication/commentaires.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
        ]);
    }
}