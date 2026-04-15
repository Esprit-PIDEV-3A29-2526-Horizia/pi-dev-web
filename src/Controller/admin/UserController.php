<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Profil;
use App\Form\UserFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $users = $em->getRepository(User::class)->findAll();
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $user->getPassword();
            $hashedPassword = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form->createView(),  // ← ICI : passe le formulaire
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $user->getPassword();
            if ($plainPassword) {
                $hashedPassword = $hasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur modifié avec succès');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form->createView(),  // ← ICI : passe le formulaire
            'user' => $user,
        ]);
    }
    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
public function delete($id, Request $request, EntityManagerInterface $em): Response
{
    // Si l'ID est la chaîne 'me', on utilise l'ID de l'utilisateur connecté
    if ($id === 'me') {
        $id = $this->getUser()->getId();
    } else {
        $id = (int) $id;
    }

    $user = $em->getRepository(User::class)->find($id);
    if ($user) {
        // Empêcher un utilisateur de supprimer son propre compte
        if ($user->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_user_index');
        }
        $em->remove($user);
        $em->flush();
        $this->addFlash('success', 'Utilisateur supprimé avec succès');
    } else {
        $this->addFlash('error', 'Utilisateur non trouvé');
    }
    return $this->redirectToRoute('app_user_index');
}
}