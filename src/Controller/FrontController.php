<?php
namespace App\Controller;

use App\Repository\LogementRepository;
use App\Repository\VoyageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/front/', name: 'app_front_home')]
    public function home(VoyageRepository $voyageRepository): Response
    {
        return $this->render('front/index.html.twig');
    }
    // Ajoutez cette méthode dans FrontController
#[Route('/logements', name: 'app_front_logement_index')]
public function logements(LogementRepository $logementRepository): Response
{
    $logements = $logementRepository->findBy(['disponibilite' => true]); // ou findAll()
    return $this->render('front/logement/index.html.twig', [
        'logements' => $logements,
    ]);
}
}