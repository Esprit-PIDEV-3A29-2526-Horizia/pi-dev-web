<?php
// src/Service/LogementSearchService.php

namespace App\Service;

use App\Repository\LogementRepository;

class LogementSearchService
{
    private LogementRepository $logementRepository;

    public function __construct(LogementRepository $logementRepository)
    {
        $this->logementRepository = $logementRepository;
    }

    /**
     * Version front : recherche + type + tri (sans pagination, sans filtre disponibilité)
     */
    public function searchAndSort(?string $search, ?string $type, ?string $sort): array
    {
        $qb = $this->logementRepository->createQueryBuilder('l');

        if (!empty($search)) {
            $qb->andWhere('l.nom LIKE :search OR l.adresse LIKE :search OR l.type LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($type) && $type !== 'tous') {
            $qb->andWhere('l.type = :type')->setParameter('type', $type);
        }

        switch ($sort) {
            case 'prix_asc':
                $qb->orderBy('l.tarif_nuit', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('l.tarif_nuit', 'DESC');
                break;
            default:
                $qb->orderBy('l.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Version admin : recherche textuelle + filtre disponibilité + tri + pagination
     *
     * @return array Liste des logements (objets Logement)
     */
    public function searchAndSortForAdmin(
        ?string $search,
        ?string $disponibilite,
        ?string $sort,
        int $page = 1,
        int $limit = 9
    ): array {
        $qb = $this->logementRepository->createQueryBuilder('l');

        // Recherche textuelle (nom ou adresse)
        if (!empty($search)) {
            $qb->andWhere('l.nom LIKE :search OR l.adresse LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre disponibilité
        if ($disponibilite === 'available') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', true);
        } elseif ($disponibilite === 'unavailable') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', false);
        }

        // Tri
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('l.tarif_nuit', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('l.tarif_nuit', 'DESC');
                break;
            case 'dispo_asc':
                $qb->orderBy('l.disponibilite', 'ASC');
                break;
            case 'dispo_desc':
                $qb->orderBy('l.disponibilite', 'DESC');
                break;
            default:
                $qb->orderBy('l.id', 'DESC');
        }

        // Pagination
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre total de logements (admin) avec les mêmes filtres (sans pagination)
     */
    public function countForAdmin(?string $search, ?string $disponibilite): int
    {
        $qb = $this->logementRepository->createQueryBuilder('l');
        $qb->select('COUNT(l.id)');

        if (!empty($search)) {
            $qb->andWhere('l.nom LIKE :search OR l.adresse LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($disponibilite === 'available') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', true);
        } elseif ($disponibilite === 'unavailable') {
            $qb->andWhere('l.disponibilite = :dispo')->setParameter('dispo', false);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}