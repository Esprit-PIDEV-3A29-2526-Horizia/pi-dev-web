<?php

namespace App\Controller\Admin;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/publication', name: 'admin_publication_')]
class PublicationController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(PublicationRepository $publicationRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $category = $request->query->get('categorie');
        $publications = $publicationRepository->findBySearchAndCategory($search, $category);
        return $this->render('admin/publication/index.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
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
            $publication->setAuteur($this->getUser()?->getUserIdentifier() ?? 'Admin');
            $publication->setDateCreation(new \DateTimeImmutable());
            $publication->setLikes(0);
            $publication->setCommentaires(0);
            $entityManager->persist($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication créée.');
            return $this->redirectToRoute('admin_publication_index');
        }
        return $this->render('admin/publication/new.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Publication $publication, CommentaireRepository $commentaireRepository): Response
    {
        $commentaires = $commentaireRepository->findBy(
            ['publication' => $publication],
            ['date_creation' => 'DESC']
        );

        return $this->render('admin/publication/show.html.twig', [
            'publication' => $publication,
            'commentaires' => $commentaires,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                if ($publication->getImage()) {
                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $publication->getImage();
                    $oldPath = str_replace('/', DIRECTORY_SEPARATOR, $oldPath);
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images';
                @mkdir($uploadDir, 0777, true);
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($uploadDir, $newFilename);
                $publication->setImage('/images/' . $newFilename);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Publication modifiée.');
            return $this->redirectToRoute('admin_publication_index');
        }
        return $this->render('admin/publication/edit.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager): Response
    {
        $publication = $entityManager->getRepository(Publication::class)->find($id);
        if (!$publication) {
            $this->addFlash('error', 'Publication introuvable.');
            return $this->redirectToRoute('admin_publication_index');
        }
        if ($this->isCsrfTokenValid('delete' . $publication->getId(), $request->request->get('_token'))) {
            $entityManager->createQuery('DELETE FROM App\Entity\Commentaire c WHERE c.publication = :pub')
                ->setParameter('pub', $publication)
                ->execute();
            if ($publication->getImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $publication->getImage();
                $imagePath = str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
                if (file_exists($imagePath)) unlink($imagePath);
            }
            $entityManager->remove($publication);
            $entityManager->flush();
            $this->addFlash('success', 'Publication supprimée.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }
        return $this->redirectToRoute('admin_publication_index');
    }
}