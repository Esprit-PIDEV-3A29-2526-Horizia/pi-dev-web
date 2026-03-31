<?php

namespace App\Controller;

use App\Entity\Voyage;
use App\Form\VoyageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin/voyage')]
class VoyageController extends AbstractController
{
    #[Route('/', name: 'app_voyage_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $voyages = $entityManager->getRepository(Voyage::class)->findAll();

        return $this->render('admin/voyage/index.html.twig', [
            'voyages' => $voyages,
        ]);
    }

    #[Route('/new', name: 'app_voyage_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = new Voyage();
        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($voyage);
            $entityManager->flush();

            $this->addFlash('success', 'Voyage ajouté avec succès.');

            return $this->redirectToRoute('app_voyage_index');
        }

        return $this->render('admin/voyage/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'app_voyage_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $voyage = $entityManager->getRepository(Voyage::class)->find($id);

        if (!$voyage) {
            throw $this->createNotFoundException('Voyage introuvable.');
        }

        $form = $this->createForm(VoyageType::class, $voyage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Voyage modifié avec succès.');

            return $this->redirectToRoute('app_voyage_index');
        }

        return $this->render('admin/voyage/edit.html.twig', [
            'form' => $form->createView(),
            'voyage' => $voyage,
        ]);
    }

    #[Route('/delete/{id}', name: 'app_voyage_delete')]
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