<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\CommentaireType;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
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
    public function index(PublicationRepository $repo, PaginatorInterface $paginator, Request $request): Response
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
        $favoritesIds = $request->getSession()->get('favorite_publications', []);

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
            // Image téléchargée manuellement
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            // Génération d'image par IA (si aucun fichier téléchargé ET un prompt est fourni)
            $imagePrompt = $form->get('imagePrompt')->getData();
            if ($imagePrompt && !$publication->getImage()) {
                $generatedImage = $aiImageGenerator->generateImage($imagePrompt);
                if ($generatedImage) {
                    $publication->setImage($generatedImage);
                }
            }

            // Pseudo et session
            $pseudo = $form->get('pseudo')->getData();
            if (!$pseudo) $pseudo = 'Anonyme';
            $request->getSession()->set('mon_pseudo', $pseudo);
            $publication->setAuteur($pseudo);

            // Conversion des tags (string -> array)
            $tagsString = $form->get('tags')->getData();
            if ($tagsString) {
                $tagsArray = array_map('trim', explode(',', $tagsString));
                $publication->setTags($tagsArray);
            } else {
                $publication->setTags(null);
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
        // Vérifier les droits : utilisateur connecté propriétaire OU pseudo session correspondant à l'auteur
        $user = $this->getUser();
        $sessionPseudo = $request->getSession()->get('mon_pseudo');
        $isAuthorized = false;

        if ($user && $publication->getUtilisateur() && $user->getId() === $publication->getUtilisateur()->getId()) {
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
        
        // Pré-remplir le champ tags (non mappé) avec les tags existants sous forme de chaîne
        $existingTags = $publication->getTags();
        if ($existingTags) {
            $form->get('tags')->setData(implode(', ', $existingTags));
        }
        $form->get('pseudo')->setData($publication->getAuteur());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            // Conversion des tags (string -> array)
            $tagsString = $form->get('tags')->getData();
            if ($tagsString) {
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

        if ($user && $publication->getUtilisateur() && $user->getId() === $publication->getUtilisateur()->getId()) {
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

        if ($this->isCsrfTokenValid('delete_publication_' . $publication->getId(), $request->request->get('_token'))) {
            $em->remove($publication);
            $em->flush();
            $this->addFlash('success', 'Publication supprimée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('front_publication_index');
    }

    #[Route('/publication/{id}', name: 'front_publication_show')]
    public function show(Publication $publication, Request $request, EntityManagerInterface $em, CommentaireRepository $comRepo): Response
    {
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setPublication($publication);
            $commentaire->setAuteur($form->get('auteur')->getData());
            $commentaire->setDateCreation(new \DateTimeImmutable());
            $em->persist($commentaire);
            $publication->setCommentaires($publication->getCommentaires() + 1);
            $em->flush();
            $this->addFlash('success', 'Commentaire ajouté.');
            return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
        }

        $likedIds = $request->getSession()->get('liked_publications', []);
        $favoritesIds = $request->getSession()->get('favorite_publications', []);
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
        $isAuthor = ($user && $user->getUserIdentifier() === $commentaire->getAuteur());
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isAuthor && !$isAdmin) {
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
        $isAuthor = ($user && $user->getUserIdentifier() === $commentaire->getAuteur());
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if (!$isAuthor && !$isAdmin) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce commentaire.');
            return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $publication = $commentaire->getPublication();
            $publication->setCommentaires($publication->getCommentaires() - 1);
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
        $pseudo = $request->getSession()->get('mon_pseudo');
        if (!$pseudo) {
            $this->addFlash('info', 'Créez une publication pour définir votre pseudo.');
            return $this->redirectToRoute('front_publication_new');
        }

        $publications = $repo->findBy(['auteur' => $pseudo], ['date_creation' => 'DESC']);
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
    public function toggleFavorite(Publication $publication, Request $request): JsonResponse
    {
        $session = $request->getSession();
        $favorites = $session->get('favorite_publications', []);
        $id = $publication->getId();

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
    public function myFavorites(PublicationRepository $repo, Request $request): Response
    {
        $favorites = $request->getSession()->get('favorite_publications', []);
        $publications = [];
        if (!empty($favorites)) {
            $publications = $repo->findBy(['id' => $favorites], ['date_creation' => 'DESC']);
        }
        $likedIds = $request->getSession()->get('liked_publications', []);

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