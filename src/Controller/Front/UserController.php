<?php

namespace App\Controller\Front;

use App\Entity\User;
use App\Entity\Profil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('front/user/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_front_home');
        }

        // Handle form submission
        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $email = $request->request->get('email');
            $plainPassword = $request->request->get('password');
            $telephone = $request->request->get('telephone');
            $addresse = $request->request->get('addresse');

            // Check if user already exists
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }

            // Get CLIENT profil (id = 5 from your database)
            $profil = $entityManager->getRepository(Profil::class)->find(5);
            if (!$profil) {
                // Create CLIENT profil if it doesn't exist
                $profil = new Profil();
                $profil->setType('CLIENT');
                $profil->setStatut('actif');
                $entityManager->persist($profil);
                $entityManager->flush();
            }

            // Create new user
            $user = new User();
            $user->setNom($nom);
            $user->setPrenom($prenom);
            $user->setEmail($email);
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setTelephone($telephone);
            $user->setAddresse($addresse);
            $user->setProfil($profil);
            $user->setFaceDescriptor('');

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('front/user/register.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode peut rester vide, Symfony gère le logout.');
    }

    #[Route('/after-login', name: 'app_after_login_redirect')]
    public function afterLoginRedirect(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }

        return $this->redirectToRoute('app_front_home');
    }
}