<?php

namespace App\Repository;

use App\Entity\FavoriVoyage;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriVoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FavoriVoyage::class);
    }

    public function findOneByVisitorAndVoyage(string $visitorToken, Voyage $voyage): ?FavoriVoyage
    {
        return $this->findOneBy([
            'visitorToken' => $visitorToken,
            'voyage' => $voyage,
        ]);
    }

    public function findVisitorFavorites(string $visitorToken): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.voyage', 'v')
            ->addSelect('v')
            ->andWhere('f.visitorToken = :token')
            ->setParameter('token', $visitorToken)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}