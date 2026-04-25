<?php

namespace App\Repository;

use App\Entity\Publication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Recherche des publications par mot-clé (titre ou description) et par catégorie.
     *
     * @param string|null $search   Mot-clé à rechercher (titre ou description)
     * @param string|null $category Catégorie à filtrer (ex: 'Plage', 'Montagne', etc.)
     *
     * @return Publication[]
     */
    public function findBySearchAndCategory(?string $search, ?string $category): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($search) {
            $qb->andWhere('p.titre LIKE :search OR p.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($category && $category !== 'Tous') {
            $qb->andWhere('p.categorie = :cat')
               ->setParameter('cat', $category);
        }

        return $qb->orderBy('p.date_creation', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    // Vous pouvez ajouter ici d’autres méthodes personnalisées si besoin
    // (ex: findTopLiked, countByCategory, etc.)
}