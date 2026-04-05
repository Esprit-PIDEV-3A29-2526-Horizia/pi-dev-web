<?php

namespace App\Controller\Admin;

use App\Entity\Modele;
use App\Form\ModeleType;
use App\Repository\ModeleRepository;
use App\Repository\MarqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/modele')]
class ModeleController extends AbstractController
{
    // 📋 LISTE des modèles
    #[Route('/', name: 'admin_modele_index', methods: ['GET'])]
    public function index(ModeleRepository $repository): Response
    {
        $modeles = $repository->findAllWithMarque();
        
        return $this->render('admin/modele/index.html.twig', [
            'modeles' => $modeles,
        ]);
    }

    // ➕ AJOUTER un modèle
    #[Route('/new', name: 'admin_modele_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, MarqueRepository $marqueRepository): Response
    {
        $modele = new Modele();
        $form = $this->createForm(ModeleType::class, $modele);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($modele);
            $em->flush();
            
            $this->addFlash('success', 'Modèle ajouté avec succès !');
            return $this->redirectToRoute('admin_modele_index');
        }

        return $this->render('admin/modele/new.html.twig', [
            'form' => $form->createView(),
            'marques' => $marqueRepository->findAllAlphabetique(),
        ]);
    }

    // 👁️ VOIR un modèle
    #[Route('/{id}', name: 'admin_modele_show', methods: ['GET'])]
    public function show(Modele $modele): Response
    {
        return $this->render('admin/modele/show.html.twig', [
            'modele' => $modele,
        ]);
    }

    // ✏️ MODIFIER un modèle
    #[Route('/{id}/edit', name: 'admin_modele_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Modele $modele, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ModeleType::class, $modele);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Modèle modifié avec succès !');
            return $this->redirectToRoute('admin_modele_index');
        }

        return $this->render('admin/modele/edit.html.twig', [
            'form' => $form->createView(),
            'modele' => $modele,
        ]);
    }

    // 🔍 API pour récupérer les modèles par marque (AJAX)
    #[Route('/by-marque/{id}', name: 'admin_modele_by_marque', methods: ['GET'])]
    public function getByMarque(int $id, ModeleRepository $repository): Response
    {
        $modeles = $repository->findByMarque($id);
        
        $data = [];
        foreach ($modeles as $modele) {
            $data[] = [
                'id' => $modele->getIdModele(),
                'nom' => $modele->getNomModele(),
            ];
        }
        
        return $this->json($data);
    }
}