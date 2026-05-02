<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Voyage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Voyage>
 */
class VoyageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Voyage::class);
    }

    /**
     * @return array<int, Voyage>
     */
    public function findAllVoyages(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->orderBy('v.dateDepart', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findAvailableVoyages(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('v.dateDepart', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findCompletedVoyages(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes <= 0')
            ->orderBy('v.dateDepart', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findBudgetVoyages(float $budgetMax, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->andWhere('v.prix <= :budgetMax')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('budgetMax', $budgetMax)
            ->orderBy('v.prix', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findBudgetVoyagesBetween3000And4000(?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->andWhere('v.prix < :minBudget')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->setParameter('minBudget', 3000)
            ->orderBy('v.prix', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, string>|null $themeKeywords
     * @param array<int, int>|null $months
     * @return array<int, Voyage>
     */
    public function searchSmart(
        ?string $destination = null,
        ?float $budgetMin = null,
        ?array $themeKeywords = null,
        ?int $minPlaces = null,
        ?array $months = null,
        int $limit = 12
    ): array {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'));

        if ($destination !== null && $destination !== '') {
            $qb->andWhere('LOWER(v.destination) LIKE LOWER(:destination)')
                ->setParameter('destination', '%' . $destination . '%');
        }

        if ($budgetMin !== null) {
            $qb->andWhere('v.prix <= :budgetMin')
                ->setParameter('budgetMin', $budgetMin);
        }

        if ($minPlaces !== null) {
            $qb->andWhere('v.placesRestantes >= :minPlaces')
                ->setParameter('minPlaces', $minPlaces);
        }

        if (!empty($themeKeywords)) {
            $orX = $qb->expr()->orX();

            foreach ($themeKeywords as $index => $keyword) {
                $param = 'theme_' . $index;

                $orX->add($qb->expr()->like('LOWER(v.titre)', ':' . $param));
                $orX->add($qb->expr()->like('LOWER(v.destination)', ':' . $param));
                $orX->add($qb->expr()->like('LOWER(v.description)', ':' . $param));

                $qb->setParameter($param, '%' . mb_strtolower($keyword) . '%');
            }

            $qb->andWhere($orX);
        }

        if (!empty($months)) {
            $year = (int) date('Y');
            $monthOr = $qb->expr()->orX();

            foreach ($months as $index => $month) {
                $start = new \DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
                $end = $start->modify('first day of next month');

                $startParam = 'month_start_' . $index;
                $endParam = 'month_end_' . $index;

                $monthOr->add("(v.dateDepart >= :{$startParam} AND v.dateDepart < :{$endParam})");

                $qb->setParameter($startParam, $start);
                $qb->setParameter($endParam, $end);
            }

            $qb->andWhere($monthOr);
        }

        return $qb
            ->orderBy('v.dateDepart', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findMostReservedVoyages(?int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('v')
            ->leftJoin(Reservation::class, 'r', 'WITH', 'r.voyage = v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->groupBy('v.id')
            ->orderBy('COUNT(r.id)', 'DESC')
            ->addOrderBy('v.dateDepart', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findByDestination(string $destination, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('v')
            ->andWhere('LOWER(v.destination) LIKE LOWER(:destination)')
            ->orWhere('LOWER(v.titre) LIKE LOWER(:destination)')
            ->setParameter('destination', '%' . $destination . '%')
            ->andWhere('v.placesRestantes > 0')
            ->orderBy('v.prix', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Voyage>
     */
    public function findCheapestVoyages(int $limit = 5): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.placesRestantes > 0')
            ->andWhere('v.dateDepart >= :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('v.prix', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}