<?php
// src/Controller/Admin/UserController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Reservationlog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/user', name: 'admin_user_')]
class UserController extends AbstractController
{
    // src/Controller/Admin/UserController.php

#[Route('/{id}', name: 'show', methods: ['GET'])]
public function show(User $user, EntityManagerInterface $em): Response
{
    $reservations = $em->getRepository(Reservationlog::class)
        ->createQueryBuilder('r')
        ->where('r.user = :user')
        ->setParameter('user', $user)
        ->orderBy('r.date_debut', 'DESC')  // ← date_debut
        ->getQuery()
        ->getResult();

    return $this->render('admin/user/show.html.twig', [
        'user' => $user,
        'reservations' => $reservations,
    ]);
}
}