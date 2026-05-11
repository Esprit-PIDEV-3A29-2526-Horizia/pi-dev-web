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
    /**
     * @return array<int, Location>
     */
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
    /**
     * @return array<int, Location>
     */
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
    /**
     * @return array<int, array{marque_nom: string, modele_nom: string, total: int}>
     */
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
    /**
     * @return array<int, array{statut: string, total: int}>
     */
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

        $count1 = $em->createQueryBuilder()
            ->update('App\Entity\Location', 'l')
            ->set('l.statut', ':nouveauStatut')
            ->where('l.statut = :ancienStatut')
            ->andWhere('l.dateDebut <= :now')
            ->setParameter('nouveauStatut', 'en_cours')
            ->setParameter('ancienStatut', 'réservée')
            ->setParameter('now', $now)
            ->getQuery()->execute();

        $count2 = $em->createQueryBuilder()
            ->update('App\Entity\Location', 'l')
            ->set('l.statut', ':nouveauStatut')
            ->where('l.statut = :ancienStatut')
            ->andWhere('l.dateFinPrevue < :now')
            ->setParameter('nouveauStatut', 'terminée')
            ->setParameter('ancienStatut', 'en_cours')
            ->setParameter('now', $now)
            ->getQuery()->execute();

        return $count1 + $count2;
    }

    /**
     * Récupère les locations pour la carte Leaflet du dashboard.
     * - Exclut annulées et no_show
     * - Charge le véhicule en eager loading (évite N+1 queries)
     * - Limitée à 50 pour la performance
     */
    /**
     * @return array<int, Location>
     */
    public function findPourCartographie(): array
    {
        return $this->createQueryBuilder('l')
            ->leftJoin('l.vehicule', 'v')
            ->addSelect('v')
            ->where('l.statut NOT IN (:exclus)')
            ->setParameter('exclus', ['annulée', 'annulee', 'no_show'])
            ->orderBy('l.dateDebut', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }
}