<?php

namespace App\Controller;

use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestDoctrineController extends AbstractController
{
    #[Route('/test/doctrine', name: 'test_doctrine')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les véhicules
        $repository = $entityManager->getRepository(Vehicule::class);
        $vehicules = $repository->findAll();
        
        return $this->render('test_doctrine/index.html.twig', [
            'vehicules' => $vehicules,
            'count' => count($vehicules)
        ]);
    }
}