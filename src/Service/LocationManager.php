<?php

namespace App\Service;

use App\Entity\Location;
use App\Entity\Vehicule;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class LocationManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Règles métier pour Location :
     * 1. La date de début ne peut pas être dans le passé
     * 2. La date de fin doit être après la date de début
     * 3. La durée minimale est de 1 jour
     * 4. Le véhicule doit être disponible sur la période
     * 5. Le montant total doit être calculé correctement
     */
    public function validate(Location $location): bool
    {
        $dateDebut = $location->getDateDebut();
        $dateFin = $location->getDateFinPrevue();
        $aujourdhui = new \DateTime('today');

        // Règle 1 : Date début pas dans le passé
        if ($dateDebut < $aujourdhui) {
            throw new InvalidArgumentException('La date de début ne peut pas être dans le passé.');
        }

        // Règle 2 : Date fin après date début
        if ($dateFin <= $dateDebut) {
            throw new InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        // Règle 3 : Durée minimale 1 jour
        $nbJours = $this->calculerNbJours($dateDebut, $dateFin);
        if ($nbJours < 1) {
            throw new InvalidArgumentException('La durée minimale de location est de 1 jour.');
        }

        // Règle 4 : Véhicule disponible
        $vehicule = $location->getVehicule();
        if (!$this->isVehiculeDisponible($vehicule, $dateDebut, $dateFin, $location)) {
            throw new InvalidArgumentException('Le véhicule n\'est pas disponible sur cette période.');
        }

        return true;
    }

    public function save(Location $location): Location
    {
        // Calculer automatiquement le montant total
        $location->calculerMontantTotal();
        
        $this->validate($location);
        $this->entityManager->persist($location);
        $this->entityManager->flush();
        
        return $location;
    }

    public function delete(Location $location): void
    {
        $this->entityManager->remove($location);
        $this->entityManager->flush();
    }

    public function annuler(Location $location): Location
    {
        $dateDebut = $location->getDateDebut();
        $aujourdhui = new \DateTime('today');
        $interval = $aujourdhui->diff($dateDebut);
        $joursAvant = (int) $interval->days;

        // Si la date de début est passée, on ajuste le signe
        if ($dateDebut < $aujourdhui) {
            $joursAvant = -$joursAvant;
        }

        // Règle : Annulation possible seulement si +7 jours avant
        if ($dateDebut > $aujourdhui && $joursAvant < 7) {
            throw new InvalidArgumentException('Annulation impossible : moins de 7 jours avant le début.');
        }

        $location->setStatut('annulee');
        $this->entityManager->flush();
        
        return $location;
    }

    public function calculerNbJours(\DateTimeInterface $debut, \DateTimeInterface $fin): int
    {
        $diff = $debut->diff($fin);
        return max(1, (int) $diff->days);
    }

    public function calculerMontantTotal(Vehicule $vehicule, int $nbJours): float
    {
        $prixParJour = (float) $vehicule->getPrixParJour();
        return $prixParJour * $nbJours;
    }

    public function calculerAvance(float $montantTotal): float
    {
        return $montantTotal * 0.3;
    }

    public function calculerResteAPayer(float $montantTotal, float $avance): float
    {
        $reste = $montantTotal - $avance;
        return $reste > 0 ? $reste : 0;
    }

    private function isVehiculeDisponible(Vehicule $vehicule, \DateTimeInterface $debut, \DateTimeInterface $fin, ?Location $currentLocation = null): bool
    {
        $qb = $this->entityManager
            ->getRepository(Location::class)
            ->createQueryBuilder('l')
            ->where('l.vehicule = :vehicule')
            ->andWhere('l.statut != :annule')
            ->setParameter('vehicule', $vehicule)
            ->setParameter('annule', 'annulee')
            ->andWhere('(l.dateDebut < :fin AND l.dateFinPrevue > :debut)')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin);

        if ($currentLocation && $currentLocation->getIdLocation()) {
            $qb->andWhere('l.idLocation != :currentId')
               ->setParameter('currentId', $currentLocation->getIdLocation());
        }

        $locationsExistantes = $qb->getQuery()->getResult();
        
        return count($locationsExistantes) === 0;
    }
}