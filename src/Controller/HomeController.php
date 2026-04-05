<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/voyages', name: 'app_home_voyages')]
    public function index(): Response
    {
        return $this->render('front/index.html.twig');
    }
}