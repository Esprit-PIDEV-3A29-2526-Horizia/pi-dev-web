<?php

namespace App\Controller\admin;

use App\Entity\Profil;
use App\Form\ProfilFormType;
use App\Repository\ProfilRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/profil', name: 'app_profil_')]
class ProfilController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(ProfilRepository $repo): Response
    {
        return $this->render('admin/profil/index.html.twig', [
            'profils' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $profil = new Profil();
        $form = $this->createForm(ProfilFormType::class, $profil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($profil);
            $em->flush();
            $this->addFlash('success', 'Profil créé avec succès !');
            return $this->redirectToRoute('app_profil_index');
        }

        return $this->render('admin/profil/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Profil $profil, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProfilFormType::class, $profil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Profil modifié avec succès !');
            return $this->redirectToRoute('app_profil_index');
        }

        return $this->render('admin/profil/edit.html.twig', [
            'form' => $form->createView(),
            'profil' => $profil,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Profil $profil, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $profil->getId(), $request->request->get('_token'))) {
            $em->remove($profil);
            $em->flush();
            $this->addFlash('success', 'Profil supprimé avec succès !');
        }
        return $this->redirectToRoute('app_profil_index');
    }
}