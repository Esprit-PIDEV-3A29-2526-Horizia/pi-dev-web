<?php

namespace App\Controller\Admin;

use App\Service\PlanningService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use DateTime;

#[Route('/admin/planning')]
class PlanningController extends AbstractController
{
    #[Route('/', name: 'admin_planning_index')]
   #[Route('/{annee}/{mois}', name: 'admin_planning_mois', requirements: ['annee' => '\d+', 'mois' => '\d+'])]
    public function index(PlanningService $planningService, ?int $annee = null, ?int $mois = null): Response
    {
        // Déterminer l'année et le mois
        if ($annee === null || $mois === null) {
            $annee = (int) date('Y');
            $mois = (int) date('m');
        }

        // Sécuriser les valeurs
        if ($mois < 1) {
            $mois = 12;
            $annee--;
        }
        if ($mois > 12) {
            $mois = 1;
            $annee++;
        }

        // Récupérer les locations du mois
        $locationsduMois = $planningService->getLocationsDuMois($annee, $mois);

        // Construire le calendrier indexé par date
        $calendrier = [];
        $premierJour = new DateTime("{$annee}-{$mois}-01");
        $nbJours = (int) $premierJour->format('t');
        $premierJourSemaine = (int) $premierJour->format('N') - 1; // 0 = Lundi

        foreach ($locationsduMois as $loc) {
            $debut = $loc->getDateDebut();
            $fin = $loc->getDateFinPrevue();
            if (!$debut || !$fin) continue;

            $debutMois = new DateTime("{$annee}-{$mois}-01");
            $finMois = new DateTime("{$annee}-{$mois}-{$nbJours} 23:59:59");
            
            $cursor = clone $debut;
            if ($cursor < $debutMois) $cursor = clone $debutMois;
            $borneMax = $fin < $finMois ? $fin : $finMois;

            while ($cursor <= $borneMax) {
                $key = $cursor->format('Y-m-d');
                if (!isset($calendrier[$key])) {
                    $calendrier[$key] = [];
                }
                $calendrier[$key][] = $loc;
                $cursor->modify('+1 day');
            }
        }

        // Statistiques
        $tauxOccupation = $planningService->calculerTauxOccupation($annee, $mois);
        $caMois = $planningService->calculerCADuMois($annee, $mois);
        $statuts = $planningService->getStatutsParMois($annee, $mois);
        $conflits = $planningService->detecterConflitsDuMois($annee, $mois);
        $alertesRetour = $planningService->getLocationsQuiTerminentBientot(3);

        return $this->render('admin/planning/index.html.twig', [
            'annee' => $annee,
            'mois' => $mois,
            'moisNom' => $planningService->getNomMois($mois, $annee),
            'nbJours' => $nbJours,
            'premierJourSemaine' => $premierJourSemaine,
            'calendrier' => $calendrier,
            'locationsduMois' => $locationsduMois,
            'tauxOccupation' => round($tauxOccupation, 1),
            'caMois' => $caMois,
            'statuts' => $statuts,
            'conflits' => $conflits,
            'alertesRetour' => $alertesRetour,
        ]);
    }

    #[Route('/jour/{date}', name: 'admin_planning_jour')]
    public function jour(string $date, PlanningService $planningService): Response
    {
        $dateTime = new DateTime($date);
        $locations = $planningService->getLocationsParDate($dateTime);

        return $this->render('admin/planning/jour.html.twig', [
            'date' => $dateTime,
            'locations' => $locations,
        ]);
    }
}