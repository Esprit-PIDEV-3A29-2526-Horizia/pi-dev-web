<?php

namespace App\Repository;

use App\Entity\FavoriVoyage;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FavoriVoyage>
 */
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

    public function findOneByUserAndVoyage(User $user, Voyage $voyage): ?FavoriVoyage
    {
        return $this->findOneBy([
            'createdBy' => $user,
            'voyage' => $voyage,
        ]);
    }

    /**
     * @return array<int, FavoriVoyage>
     */
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

    /**
     * @return array<int, FavoriVoyage>
     */
    public function findUserFavorites(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.voyage', 'v')
            ->addSelect('v')
            ->andWhere('f.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}