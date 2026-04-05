<?php

namespace App\Controller\Admin;

use App\Entity\Marque;
use App\Form\MarqueType;
use App\Repository\MarqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/marque')]
class MarqueController extends AbstractController
{
    // 📋 LISTE des marques
    #[Route('/', name: 'admin_marque_index', methods: ['GET'])]
    public function index(MarqueRepository $repository): Response
    {
        $marques = $repository->findAllAlphabetique();
        
        return $this->render('admin/marque/index.html.twig', [
            'marques' => $marques,
        ]);
    }

    // ➕ AJOUTER une marque
    #[Route('/new', name: 'admin_marque_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $marque = new Marque();
        $form = $this->createForm(MarqueType::class, $marque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($marque);
            $em->flush();
            
            $this->addFlash('success', 'Marque ajoutée avec succès !');
            return $this->redirectToRoute('admin_marque_index');
        }

        return $this->render('admin/marque/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // 👁️ VOIR une marque
    #[Route('/{id}', name: 'admin_marque_show', methods: ['GET'])]
    public function show(Marque $marque): Response
    {
        return $this->render('admin/marque/show.html.twig', [
            'marque' => $marque,
        ]);
    }

    // ✏️ MODIFIER une marque
    #[Route('/{id}/edit', name: 'admin_marque_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Marque $marque, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MarqueType::class, $marque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Marque modifiée avec succès !');
            return $this->redirectToRoute('admin_marque_index');
        }

        return $this->render('admin/marque/edit.html.twig', [
            'form' => $form->createView(),
            'marque' => $marque,
        ]);
    }
}