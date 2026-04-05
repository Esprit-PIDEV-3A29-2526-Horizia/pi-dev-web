<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use App\Entity\Publication;
use App\Form\CommentaireType;
use App\Repository\CommentaireRepository;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/commentaire', name: 'admin_commentaire_')]
class CommentaireController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, CommentaireRepository $commentaireRepository, PublicationRepository $publicationRepository, EntityManagerInterface $em): Response
    {
        $publicationId = $request->query->get('publication');
        $publication = null;
        if ($publicationId) {
            $publication = $publicationRepository->find($publicationId);
            if (!$publication) {
                throw $this->createNotFoundException('Publication non trouvée');
            }
        }

        // Création d'un nouveau commentaire (si formulaire soumis)
        $commentaire = new Commentaire();
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setPublication($publication);
            $commentaire->setDateCreation(new \DateTimeImmutable());
            $commentaire->setModifie(false);
            $commentaire->setUtilisateurId($this->getUser()?->getId() ?? 0);
            
            $em->persist($commentaire);
            $publication->setCommentaires($publication->getCommentaires() + 1);
            $em->flush();
            
            $this->addFlash('success', 'Commentaire ajouté.');
            return $this->redirectToRoute('admin_commentaire_index', ['publication' => $publicationId]);
        }

        // Récupérer les commentaires existants pour cette publication
        if ($publication) {
            $commentaires = $commentaireRepository->findBy(['publication' => $publication], ['date_creation' => 'DESC']);
        } else {
            $commentaires = $commentaireRepository->findBy([], ['date_creation' => 'DESC']);
        }

        return $this->render('admin/commentaire/index.html.twig', [
            'commentaires' => $commentaires,
            'filtre_publication' => $publicationId,
            'publication' => $publication,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Commentaire $commentaire): Response
    {
        return $this->render('admin/commentaire/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setModifie(true);
            $em->flush();
            $this->addFlash('success', 'Commentaire modifié.');
            return $this->redirectToRoute('admin_commentaire_index', ['publication' => $commentaire->getPublication()->getId()]);
        }

        return $this->render('admin/commentaire/edit.html.twig', [
            'form' => $form->createView(),
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Commentaire $commentaire, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $commentaire->getId(), $request->request->get('_token'))) {
            $publication = $commentaire->getPublication();
            $publication->setCommentaires(max(0, $publication->getCommentaires() - 1));
            $em->remove($commentaire);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprimé.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('admin_commentaire_index', ['publication' => $commentaire->getPublication()->getId()]);
    }
}