<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\CommentaireType;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PublicationController extends AbstractController
{
    #[Route('/publications', name: 'front_publication_index')]
    public function index(PublicationRepository $repo): Response
    {
        $publications = $repo->findAll();
        return $this->render('front/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    // ⚠️ La route statique /publication/new doit être AVANT la route dynamique /publication/{id}
    #[Route('/publication/new', name: 'front_publication_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l’image
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }

            $user = $this->getUser();
            $publication->setAuteur($user ? $user->getUserIdentifier() : 'Anonyme');
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
            $commentaire->setModifie(false);

            $em->persist($commentaire);
            $publication->setCommentaires($publication->getCommentaires() + 1);
            $em->flush();

            $this->addFlash('success', 'Commentaire ajouté.');
            return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
        }

        $commentaires = $comRepo->findBy(['publication' => $publication], ['date_creation' => 'ASC']);

        return $this->render('front/publication/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
            'commentForm' => $form->createView(),
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
            $commentaire->setModifie(true);
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
            $publication->setCommentaires(max(0, $publication->getCommentaires() - 1));
            $em->remove($commentaire);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('front_publication_show', ['id' => $commentaire->getPublication()->getId()]);
    }

    #[Route('/publication/{id}/like', name: 'front_publication_like', methods: ['POST'])]
    public function like(Publication $publication, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('like' . $publication->getId(), $request->request->get('_token'))) {
            $publication->setLikes($publication->getLikes() + 1);
            $em->flush();
        }
        return $this->redirectToRoute('front_publication_show', ['id' => $publication->getId()]);
    }
}