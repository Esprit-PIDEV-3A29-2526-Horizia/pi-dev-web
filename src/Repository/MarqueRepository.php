<?php

namespace App\Repository;

use App\Entity\Marque;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Marque>
 */
class MarqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Marque::class);
    }

    // Recherche par nom (comme dans MarqueService.java)
    /**
     * @return array<int, Marque>
     */
    public function rechercherParNom(string $recherche): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nomMarque LIKE :recherche')
            ->setParameter('recherche', '%' . $recherche . '%')
            ->orderBy('m.nomMarque', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Toutes les marques en ordre alphabétique
    /**
     * @return array<int, Marque>
     */
    public function findAllAlphabetique(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.nomMarque', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Vérifier si une marque existe déjà
    public function existeParNom(string $nom): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('COUNT(m.idMarque)')
            ->where('m.nomMarque = :nom')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }
}