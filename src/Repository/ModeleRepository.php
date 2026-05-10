<?php

namespace App\Repository;

use App\Entity\Modele;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Modele>
 */
class ModeleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Modele::class);
    }

    // Recherche par nom de modèle
    public function rechercherParNom(string $recherche): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.nomModele LIKE :recherche')
            ->setParameter('recherche', '%' . $recherche . '%')
            ->orderBy('m.nomModele', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Modèles par marque
    public function findByMarque(int $idMarque): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.marque = :idMarque')
            ->setParameter('idMarque', $idMarque)
            ->orderBy('m.nomModele', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Tous les modèles avec leur marque
    public function findAllWithMarque(): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.marque', 'ma')
            ->addSelect('ma')
            ->orderBy('ma.nomMarque', 'ASC')
            ->addOrderBy('m.nomModele', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Vérifier si un modèle existe déjà pour une marque
    public function existeParNomEtMarque(string $nomModele, int $idMarque): bool
    {
        $result = $this->createQueryBuilder('m')
            ->select('COUNT(m.idModele)')
            ->where('m.nomModele = :nom')
            ->andWhere('m.marque = :idMarque')
            ->setParameter('nom', $nomModele)
            ->setParameter('idMarque', $idMarque)
            ->getQuery()
            ->getSingleScalarResult();
        
        return $result > 0;
    }
}