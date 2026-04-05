<?php

namespace App\Repository;

use App\Entity\Location;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Location>
 */
class LocationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Location::class);
    }

    // Recherche par client (nom ou téléphone)
    public function rechercherParClient(string $recherche): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.clientNomComplet LIKE :recherche')
            ->orWhere('l.clientTelephone LIKE :recherche')
            ->setParameter('recherche', '%' . $recherche . '%')
            ->orderBy('l.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Locations actives (réservée ou en_cours)
    public function findActives(): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.statut IN (:statuts)')
            ->setParameter('statuts', ['réservée', 'en_cours'])
            ->orderBy('l.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Top 5 modèles les plus loués
    public function findTop5ModelesLoues(): array
    {
        return $this->createQueryBuilder('l')
            ->select('ma.nomMarque as marque_nom, mo.nomModele as modele_nom, COUNT(l.idLocation) as total')
            ->leftJoin('l.vehicule', 'v')
            ->leftJoin('v.modele', 'mo')
            ->leftJoin('mo.marque', 'ma')
            ->groupBy('mo.idModele')
            ->orderBy('total', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    // Locations par statut (pour dashboard)
    public function countByStatut(): array
    {
        return $this->createQueryBuilder('l')
            ->select('l.statut as statut, COUNT(l.idLocation) as total')
            ->groupBy('l.statut')
            ->getQuery()
            ->getResult();
    }

    // Mise à jour automatique des statuts
    public function mettreAJourStatutsAutomatique(\DateTime $now): int
    {
        $em = $this->getEntityManager();
        
        // Réservée → En cours
        $qb1 = $em->createQueryBuilder()
            ->update('App\Entity\Location', 'l')
            ->set('l.statut', ':nouveauStatut')
            ->where('l.statut = :ancienStatut')
            ->andWhere('l.dateDebut <= :now')
            ->setParameter('nouveauStatut', 'en_cours')
            ->setParameter('ancienStatut', 'réservée')
            ->setParameter('now', $now);
        $count1 = $qb1->getQuery()->execute();
        
        // En cours → Terminée
        $qb2 = $em->createQueryBuilder()
            ->update('App\Entity\Location', 'l')
            ->set('l.statut', ':nouveauStatut')
            ->where('l.statut = :ancienStatut')
            ->andWhere('l.dateFinPrevue < :now')
            ->setParameter('nouveauStatut', 'terminée')
            ->setParameter('ancienStatut', 'en_cours')
            ->setParameter('now', $now);
        $count2 = $qb2->getQuery()->execute();
        
        return $count1 + $count2;
    }
}