<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    public function findOneByVisitorAndVoyage(string $visitorToken, Voyage $voyage): ?Favori
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