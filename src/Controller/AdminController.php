<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $nbVoyages = $entityManager->getRepository(Voyage::class)->count([]);
        $nbReservations = $entityManager->getRepository(Reservation::class)->count([]);
        $nbUsers = $entityManager->getRepository(User::class)->count([]);
        $nbCategories = $entityManager->getRepository(Categorie::class)->count([]);

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
        ]);
    }
}