<?php

namespace App\Service;

use App\Repository\LocationRepository;
use App\Repository\VehiculeRepository;

class DashboardService
{
    private LocationRepository $locationRepository;
    private VehiculeRepository $vehiculeRepository;

    public function __construct(
        LocationRepository $locationRepository,
        VehiculeRepository $vehiculeRepository
    ) {
        $this->locationRepository = $locationRepository;
        $this->vehiculeRepository = $vehiculeRepository;
    }

    /**
     * Récupère le modèle de voiture le plus loué
     * @return array ['nomMarque' => string, 'nomModele' => string, 'total' => int]
     */
    public function getModeleLePlusLoue(): array
    {
        $top = $this->locationRepository->findTop5ModelesLoues();
        if (empty($top)) {
            return ['nomMarque' => 'Aucun', 'nomModele' => '', 'total' => 0];
        }
        return [
            'nomMarque' => $top[0]['marque_nom'],
            'nomModele' => $top[0]['modele_nom'],
            'total' => $top[0]['total']
        ];
    }

    /**
     * Récupère le top 5 des modèles loués
     * @return array<string, int> ['Marque Modèle' => nombre_locations]
     */
    public function getTop5ModelesLoues(): array
    {
        $result = [];
        foreach ($this->locationRepository->findTop5ModelesLoues() as $item) {
            $result[$item['marque_nom'] . ' ' . $item['modele_nom']] = $item['total'];
        }
        return $result;
    }

    /**
     * Récupère la répartition des locations par statut
     * @return array<string, int> [statut => nombre]
     */
    public function getLocationsParStatut(): array
    {
        $result = [];
        foreach ($this->locationRepository->countByStatut() as $item) {
            $result[$item['statut']] = $item['total'];
        }
        return $result;
    }

    /**
     * Récupère le nombre de véhicules disponibles
     */
    public function getNombreVehiculesDisponibles(): int
    {
        return count($this->vehiculeRepository->findDisponibles());
    }

    /**
     * Récupère le nombre de véhicules loués
     */
    public function getNombreVehiculesLoues(): int
    {
        // Requête personnalisée pour compter les véhicules avec état 'louee'
        $result = $this->vehiculeRepository->createQueryBuilder('v')
            ->select('COUNT(v.idVehicule)')
            ->where('v.etat = :etat')
            ->setParameter('etat', 'louee')
            ->getQuery()
            ->getSingleScalarResult();
        
        return (int) $result;
    }

    /**
     * Récupère le nombre de locations actives (réservée + en_cours)
     */
    public function getNombreLocationsActives(): int
    {
        return count($this->locationRepository->findActives());
    }
}