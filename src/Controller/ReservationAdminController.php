<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationAdminController extends AbstractController
{
    #[Route('/reservation/admin', name: 'app_reservation_admin')]
    public function index(): Response
    {
        return $this->render('reservation_admin/index.html.twig', [
            'controller_name' => 'ReservationAdminController',
        ]);
    }
}
