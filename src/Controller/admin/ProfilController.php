<?php

namespace App\Controller\Admin;

use App\Entity\Profil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/profil')]
class ProfilController extends AbstractController
{
    #[Route('/', name: 'app_profil_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les paramètres de recherche et tri
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');
        
        // Créer la requête
        $qb = $em->getRepository(Profil::class)->createQueryBuilder('p');
        
        // Recherche
        if ($search) {
            $qb->andWhere('p.type LIKE :search OR p.statut LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Tri
        if ($sort == 'type_asc') {
            $qb->orderBy('p.type', 'ASC');
        } elseif ($sort == 'type_desc') {
            $qb->orderBy('p.type', 'DESC');
        } elseif ($sort == 'statut_asc') {
            $qb->orderBy('p.statut', 'ASC');
        } else {
            $qb->orderBy('p.id', 'DESC');
        }
        
        $profils = $qb->getQuery()->getResult();
        
        return $this->render('admin/profil/index.html.twig', [
            'profils' => $profils,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/new', name: 'app_profil_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $profil = new Profil();
            $profil->setType($request->request->get('type'));
            $profil->setStatut($request->request->get('statut'));
            
            $em->persist($profil);
            $em->flush();
            
            $this->addFlash('success', 'Profil créé avec succès');
            return $this->redirectToRoute('app_profil_index');
        }
        
        return $this->render('admin/profil/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'app_profil_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $profil = $em->getRepository(Profil::class)->find($id);
        
        if (!$profil) {
            throw $this->createNotFoundException('Profil non trouvé');
        }
        
        if ($request->isMethod('POST')) {
            $profil->setType($request->request->get('type'));
            $profil->setStatut($request->request->get('statut'));
            
            $em->flush();
            
            $this->addFlash('success', 'Profil modifié avec succès');
            return $this->redirectToRoute('app_profil_index');
        }
        
        return $this->render('admin/profil/edit.html.twig', [
            'profil' => $profil,
        ]);
    }

    #[Route('/{id}', name: 'app_profil_delete', methods: ['POST'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $profil = $em->getRepository(Profil::class)->find($id);
        
        if ($profil) {
            $em->remove($profil);
            $em->flush();
            $this->addFlash('success', 'Profil supprimé avec succès');
        }
        
        return $this->redirectToRoute('app_profil_index');
    }
}