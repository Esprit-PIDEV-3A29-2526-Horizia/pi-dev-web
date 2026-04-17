<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $voyageRepository = $entityManager->getRepository(Voyage::class);
        $reservationRepository = $entityManager->getRepository(Reservation::class);
        $userRepository = $entityManager->getRepository(User::class);
        $categorieRepository = $entityManager->getRepository(Categorie::class);

        $nbVoyages = $voyageRepository->count([]);
        $nbReservations = $reservationRepository->count([]);
        $nbUsers = $userRepository->count([]);
        $nbCategories = $categorieRepository->count([]);

        $nbReservationsConfirmees = $reservationRepository->count(['statut' => 'CONFIRMEE']);
        $nbReservationsEnAttente = $reservationRepository->count(['statut' => 'EN_ATTENTE']);
        $nbReservationsAnnulees = $reservationRepository->count(['statut' => 'ANNULEE']);

        $recentReservations = $entityManager->createQueryBuilder()
            ->select('r', 'v', 'u')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('r.user', 'u')
            ->orderBy('r.dateReservation', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $recentVoyages = $entityManager->createQueryBuilder()
            ->select('v')
            ->from(Voyage::class, 'v')
            ->orderBy('v.dateDepart', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $reservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->getQuery()
            ->getResult();

        $monthlyReservations = array_fill(1, 12, 0);

        foreach ($reservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyReservations[$monthNumber]++;
            }
        }

        $confirmedReservationsForChart = $entityManager->createQueryBuilder()
            ->select('r.dateReservation')
            ->from(Reservation::class, 'r')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $monthlyConfirmedReservations = array_fill(1, 12, 0);

        foreach ($confirmedReservationsForChart as $row) {
            if (!empty($row['dateReservation']) && $row['dateReservation'] instanceof \DateTimeInterface) {
                $monthNumber = (int) $row['dateReservation']->format('n');
                $monthlyConfirmedReservations[$monthNumber]++;
            }
        }

        $topVoyagesRaw = $entityManager->createQueryBuilder()
            ->select('v.titre AS titre', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topVoyagesLabels = [];
        $topVoyagesData = [];

        foreach ($topVoyagesRaw as $row) {
            $topVoyagesLabels[] = $row['titre'] ?? 'Voyage';
            $topVoyagesData[] = (int) $row['nbReservations'];
        }

        $topClientsRaw = $entityManager->createQueryBuilder()
            ->select(
                "CONCAT(COALESCE(u.nom, ''), ' ', COALESCE(u.prenom, '')) AS clientNom",
                'COUNT(r.id) AS nbReservations'
            )
            ->from(Reservation::class, 'r')
            ->leftJoin('r.user', 'u')
            ->where('u.id IS NOT NULL')
            ->groupBy('u.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $topClientsLabels = [];
        $topClientsData = [];

        foreach ($topClientsRaw as $row) {
            $nom = trim((string) ($row['clientNom'] ?? ''));
            $topClientsLabels[] = $nom !== '' ? $nom : 'Utilisateur';
            $topClientsData[] = (int) $row['nbReservations'];
        }

        $categoriesStatsRaw = $entityManager->createQueryBuilder()
            ->select('c.nom AS categorieNom', 'COUNT(r.id) AS nbReservations')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->leftJoin('v.categorie', 'c')
            ->groupBy('c.id')
            ->orderBy('nbReservations', 'DESC')
            ->getQuery()
            ->getResult();

        $categoriesLabels = [];
        $categoriesData = [];

        foreach ($categoriesStatsRaw as $row) {
            $categoriesLabels[] = $row['categorieNom'] ?? 'Sans catégorie';
            $categoriesData[] = (int) $row['nbReservations'];
        }

        $confirmedRevenueRows = $entityManager->createQueryBuilder()
            ->select('r', 'v')
            ->from(Reservation::class, 'r')
            ->leftJoin('r.voyage', 'v')
            ->where('r.statut = :statut')
            ->setParameter('statut', 'CONFIRMEE')
            ->getQuery()
            ->getResult();

        $revenuTotalEstime = 0.0;

        foreach ($confirmedRevenueRows as $reservation) {
            $voyage = $reservation->getVoyage();

            if ($voyage) {
                $prixAdulte = (float) $voyage->getPrix();
                $nbAdultes = (int) ($reservation->getNbAdultes() ?? 0);
                $nbEnfants = (int) ($reservation->getNbEnfants() ?? 0);

                $revenuTotalEstime += ($prixAdulte * $nbAdultes) + (($prixAdulte * 0.5) * $nbEnfants);
            }
        }

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'revenuTotalEstime' => $revenuTotalEstime,

            'chartLabels' => ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'],
            'chartData' => [$nbVoyages, $nbReservations, $nbUsers, $nbCategories],

            'lineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'lineChartData' => array_values($monthlyReservations),

            'confirmedLineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'confirmedLineChartData' => array_values($monthlyConfirmedReservations),

            'topVoyagesLabels' => $topVoyagesLabels,
            'topVoyagesData' => $topVoyagesData,

            'topClientsLabels' => $topClientsLabels,
            'topClientsData' => $topClientsData,

            'categoriesLabels' => $categoriesLabels,
            'categoriesData' => $categoriesData,
        ]);
    }
}