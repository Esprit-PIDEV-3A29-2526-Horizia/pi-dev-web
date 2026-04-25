<?php

namespace App\Controller\Admin;

use App\Service\DashboardService;
use App\Service\PlanningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    #[Route('/dashboard', name: 'admin_dashboard_index')]
    public function index(DashboardService $dashboardService, PlanningService $planningService): Response
    {
        // Récupérer le modèle le plus loué
        $topModele = $dashboardService->getModeleLePlusLoue();
        
        // Récupérer le top 5 des modèles loués
        $top5Modeles = $dashboardService->getTop5ModelesLoues();
        
        // Récupérer la répartition par statut
        $statuts = $dashboardService->getLocationsParStatut();
        
        // Récupérer les nombres
        $vehiculesDisponibles = $dashboardService->getNombreVehiculesDisponibles();
        $vehiculesLoues = $dashboardService->getNombreVehiculesLoues();
        $locationsActives = $dashboardService->getNombreLocationsActives();
        
        // Récupérer les statistiques du mois en cours
        $moisActuel = (int) date('m');
        $anneeActuelle = (int) date('Y');
        $tauxOccupation = $planningService->calculerTauxOccupation($anneeActuelle, $moisActuel);
        $caMois = $planningService->calculerCADuMois($anneeActuelle, $moisActuel);
        
        // Récupérer les alertes de retour (locations qui se terminent dans 3 jours)
        $alertesRetour = $planningService->getLocationsQuiTerminentBientot(3);
        
        return $this->render('admin/dashboard/index.html.twig', [
            'topModele' => $topModele,
            'top5Modeles' => $top5Modeles,
            'statuts' => $statuts,
            'vehiculesDisponibles' => $vehiculesDisponibles,
            'vehiculesLoues' => $vehiculesLoues,
            'locationsActives' => $locationsActives,
            'tauxOccupation' => round($tauxOccupation, 1),
            'caMois' => $caMois,
            'moisNom' => $planningService->getNomMois($moisActuel, $anneeActuelle),
            'alertesRetour' => $alertesRetour,
        ]);
    }
    
}