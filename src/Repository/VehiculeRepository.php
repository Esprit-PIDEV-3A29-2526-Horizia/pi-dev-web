<?php

namespace App\Repository;

use App\Entity\Vehicule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vehicule>
 */
class VehiculeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vehicule::class);
    }

    // Recherche par immatriculation
    public function rechercherParImmatriculation(string $immat): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.immatriculation LIKE :immat')
            ->setParameter('immat', '%' . $immat . '%')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Véhicules disponibles
    public function findDisponibles(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.etat = :etat')
            ->setParameter('etat', 'disponible')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Véhicules avec modèle et marque
    public function findAllWithModeleAndMarque(): array
    {
        return $this->createQueryBuilder('v')
            ->leftJoin('v.modele', 'm')
            ->addSelect('m')
            ->leftJoin('m.marque', 'ma')
            ->addSelect('ma')
            ->orderBy('v.immatriculation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Vérifier si une immatriculation existe
    public function existeParImmatriculation(string $immat, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('v')
            ->select('COUNT(v.idVehicule)')
            ->where('v.immatriculation = :immat')
            ->setParameter('immat', $immat);
        
        if ($excludeId) {
            $qb->andWhere('v.idVehicule != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }
        
        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}