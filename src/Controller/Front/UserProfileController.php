<?php

namespace App\Controller\Front;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserProfileController extends AbstractController
{
    #[Route('/mon-profil', name: 'app_front_profile')]
    public function profile(): Response
    {
        // Vérifie si l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à votre profil.');
            return $this->redirectToRoute('app_login');
        }

        // Affiche le template profile.html.twig
        return $this->render('front/user/profile.html.twig', [
            'user' => $user
        ]);
    }
}