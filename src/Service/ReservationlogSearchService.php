<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ReservationlogRepository;
use Doctrine\ORM\QueryBuilder;

class ReservationlogSearchService
{
    private ReservationlogRepository $reservationRepository;

    public function __construct(ReservationlogRepository $reservationRepository)
    {
        $this->reservationRepository = $reservationRepository;
    }

    public function getReservationsQueryBuilder(?string $search, ?string $status, ?string $sort): QueryBuilder
    {
        $qb = $this->reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.logement', 'l')
            ->leftJoin('r.user', 'u')
            ->addSelect('l', 'u');

if (!empty($search)) {
                $qb->andWhere('l.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

if (!empty($status)) {
                $qb->andWhere('r.status = :status')->setParameter('status', $status);
        }

        switch ($sort) {
            case 'date_asc': $qb->orderBy('r.date_debut', 'ASC'); break;
            case 'date_desc': $qb->orderBy('r.date_debut', 'DESC'); break;
            case 'montant_asc': $qb->orderBy('r.montant', 'ASC'); break;
            case 'montant_desc': $qb->orderBy('r.montant', 'DESC'); break;
            default: $qb->orderBy('r.idreslog', 'DESC');
        }

        return $qb;
    }

    /** @return array<string, mixed> */

    public function getFilteredReservations(?string $search, ?string $status, ?string $sort, int $page = 1, int $limit = 10): array
    {
        $qb = $this->getReservationsQueryBuilder($search, $status, $sort);
        $total = (clone $qb)->select('COUNT(r.idreslog)')->getQuery()->getSingleScalarResult();
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $reservations = $qb->getQuery()->getResult();
        return ['reservations' => $reservations, 'total' => $total];
    }
/** @return array<string, mixed> */

    public function getFilteredReservationsByUser(User $user, ?string $search, ?string $status, ?string $sort, int $page = 1, int $limit = 10): array
{
    $qb = $this->getReservationsQueryBuilder($search, $status, $sort)
        ->andWhere('r.user = :user')
        ->setParameter('user', $user);
    
    $total = (clone $qb)->select('COUNT(r.idreslog)')->getQuery()->getSingleScalarResult();
    $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
    $reservations = $qb->getQuery()->getResult();
    return ['reservations' => $reservations, 'total' => $total];
}
}