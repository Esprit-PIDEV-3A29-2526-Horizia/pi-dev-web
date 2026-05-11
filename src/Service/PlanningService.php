<?php

namespace App\Service;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\VehiculeRepository;
use DateTime;

class PlanningService
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
     * Récupère les locations d'un mois donné
     * @return array<int, Location>
     */
    public function getLocationsDuMois(int $annee, int $mois): array
    {
        $debut = new DateTime("{$annee}-{$mois}-01 00:00:00");
        $fin = new DateTime("{$annee}-{$mois}-" . date('t', $debut->getTimestamp()) . " 23:59:59");

        return $this->locationRepository->createQueryBuilder('l')
            ->where('l.dateDebut <= :fin')
            ->andWhere('l.dateFinPrevue >= :debut')
            ->andWhere('l.statut NOT IN (:statutsExclus)')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutsExclus', ['annulée', 'no_show'])
            ->orderBy('l.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les locations pour une période donnée
     * @return array<int, Location>
     */
    public function getLocationsPeriode(DateTime $debut, DateTime $fin): array
    {
        return $this->locationRepository->createQueryBuilder('l')
            ->where('l.dateDebut <= :fin')
            ->andWhere('l.dateFinPrevue >= :debut')
            ->andWhere('l.statut NOT IN (:statutsExclus)')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutsExclus', ['annulée', 'no_show'])
            ->orderBy('l.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les locations pour une date spécifique
     * @return array<int, Location>
     */
    public function getLocationsParDate(DateTime $date): array
    {
        $debut = clone $date;
        $debut->setTime(0, 0, 0);
        $fin = clone $date;
        $fin->setTime(23, 59, 59);

        return $this->getLocationsPeriode($debut, $fin);
    }

    /**
     * Vérifie si un véhicule est disponible pour une période
     */
    public function isVehiculeDisponible(int $idVehicule, DateTime $debut, DateTime $fin, ?int $excludeLocationId = null): bool
    {
        $qb = $this->locationRepository->createQueryBuilder('l')
            ->select('COUNT(l.idLocation)')
            ->where('l.vehicule = :idVehicule')
            ->andWhere('l.statut NOT IN (:statutsExclus)')
            ->andWhere('l.dateDebut < :fin')
            ->andWhere('l.dateFinPrevue > :debut')
            ->setParameter('idVehicule', $idVehicule)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutsExclus', ['annulée', 'no_show', 'terminée']);

        if ($excludeLocationId) {
            $qb->andWhere('l.idLocation != :excludeId')
               ->setParameter('excludeId', $excludeLocationId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count === 0;
    }

    /**
     * Détecte les conflits de location pour un mois donné
     * @return array<int, array<int, Location>>
     */
    public function detecterConflitsDuMois(int $annee, int $mois): array
    {
        $locations = $this->getLocationsDuMois($annee, $mois);
        $conflits = [];

        $parVehicule = [];
        foreach ($locations as $loc) {
            $idVehicule = $loc->getVehicule()?->getIdVehicule();
            if ($idVehicule) {
                $parVehicule[$idVehicule][] = $loc;
            }
        }

        foreach ($parVehicule as $idVehicule => $locs) {
            for ($i = 0; $i < count($locs); $i++) {
                for ($j = $i + 1; $j < count($locs); $j++) {
                    if ($this->seChevauchent($locs[$i], $locs[$j])) {
                        if (!isset($conflits[$idVehicule])) {
                            $conflits[$idVehicule] = [];
                        }
                        $conflits[$idVehicule][] = $locs[$i];
                        $conflits[$idVehicule][] = $locs[$j];
                    }
                }
            }
        }

        return $conflits;
    }

    /**
     * Vérifie si deux locations se chevauchent
     */
    public function seChevauchent(Location $l1, Location $l2): bool
    {
        $debut1 = $l1->getDateDebut();
        $fin1   = $l1->getDateFinPrevue();
        $debut2 = $l2->getDateDebut();
        $fin2   = $l2->getDateFinPrevue();

        if (!$debut1 || !$fin1 || !$debut2 || !$fin2) {
            return false;
        }

        return $debut1 < $fin2 && $debut2 < $fin1;
    }

    /**
     * Calcule le taux d'occupation pour un mois donné
     */
    public function calculerTauxOccupation(int $annee, int $mois): float
    {
        $timestamp = mktime(0, 0, 0, $mois, 1, $annee);
        $nbJoursMois = (int) date('t', $timestamp !== false ? $timestamp : null);
        $nbVehicules = $this->getNbVehiculesTotal();

        if ($nbVehicules === 0) {
            return 0;
        }

        $joursOccupes = $this->calculerJoursOccupes($annee, $mois, $nbJoursMois);

        return ($joursOccupes / ($nbVehicules * $nbJoursMois)) * 100;
    }

    /**
     * Calcule le chiffre d'affaires pour un mois donné
     */
    public function calculerCADuMois(int $annee, int $mois): float
    {
        $debut = new DateTime("{$annee}-{$mois}-01 00:00:00");
        $fin = new DateTime("{$annee}-{$mois}-" . date('t', $debut->getTimestamp()) . " 23:59:59");

        $result = $this->locationRepository->createQueryBuilder('l')
            ->select('SUM(l.montantTotal) as total')
            ->where('l.dateDebut BETWEEN :debut AND :fin')
            ->andWhere('l.statut NOT IN (:statutsExclus)')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statutsExclus', ['annulée', 'no_show'])
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Récupère les statuts des locations pour un mois
     * @return array<string, mixed>
     */
    public function getStatutsParMois(int $annee, int $mois): array
    {
        $debut = new DateTime("{$annee}-{$mois}-01 00:00:00");
        $fin = new DateTime("{$annee}-{$mois}-" . date('t', $debut->getTimestamp()) . " 23:59:59");

        $results = $this->locationRepository->createQueryBuilder('l')
            ->select('l.statut as statut, COUNT(l.idLocation) as total')
            ->where('l.dateDebut BETWEEN :debut AND :fin')
            ->groupBy('l.statut')
            ->orderBy('total', 'DESC')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();

        $statuts = [];
        foreach ($results as $result) {
            $statuts[$result['statut']] = $result['total'];
        }

        return $statuts;
    }

    /**
     * Récupère les locations qui se terminent bientôt
     * @return array<int, Location>
     */
    public function getLocationsQuiTerminentBientot(int $nbJours): array
    {
        $debut = new DateTime('now');
        $fin = new DateTime('+' . $nbJours . ' days');

        return $this->locationRepository->createQueryBuilder('l')
            ->where('l.dateFinPrevue BETWEEN :debut AND :fin')
            ->andWhere('l.statut = :statut')
            ->orderBy('l.dateFinPrevue', 'ASC')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statut', 'en_cours')
            ->getQuery()
            ->getResult();
    }

    public function getNomMois(int $mois, int $annee): string
    {
        $nomsMois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        return $nomsMois[$mois] . ' ' . $annee;
    }

    public function getCouleurStatut(?string $statut): string
    {
        if (!$statut) return '#95a5a6';

        return match ($statut) {
            'réservée' => '#3498db',
            'en_cours' => '#27ae60',
            'terminée' => '#95a5a6',
            'annulée'  => '#e74c3c',
            'no_show'  => '#e67e22',
            default    => '#95a5a6',
        };
    }

    public function getEmojiStatut(?string $statut): string
    {
        if (!$statut) return '❓';

        return match ($statut) {
            'réservée' => '📅',
            'en_cours' => '🚗',
            'terminée' => '✅',
            'annulée'  => '❌',
            'no_show'  => '⚠️',
            default    => '❓',
        };
    }

    private function getNbVehiculesTotal(): int
    {
        return (int) $this->vehiculeRepository->createQueryBuilder('v')
            ->select('COUNT(v.idVehicule)')
            ->where('v.etat != :etat')
            ->setParameter('etat', 'hors_service')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function calculerJoursOccupes(int $annee, int $mois, int $nbJoursMois): int
    {
        $locations  = $this->getLocationsDuMois($annee, $mois);
        $debutMois  = new DateTime("{$annee}-{$mois}-01");
        $finMois    = new DateTime("{$annee}-{$mois}-{$nbJoursMois}");
        $joursOccupes = [];

        foreach ($locations as $loc) {
            $vehicule = $loc->getVehicule();
            if (!$vehicule) continue;

            $idVehicule = $vehicule->getIdVehicule();
            $locDebut   = $loc->getDateDebut();
            $locFin     = $loc->getDateFinPrevue();

            if (!$locDebut || !$locFin) continue;

            $d = $locDebut > $debutMois ? clone $locDebut : clone $debutMois;
            $f = $locFin < $finMois ? clone $locFin : clone $finMois;

            while ($d <= $f) {
                $joursOccupes[$idVehicule . '-' . $d->format('Y-m-d')] = true;
                if ($d instanceof \DateTime) {
                    $d->modify('+1 day');
                }
            }
        }

        return count($joursOccupes);
    }
}