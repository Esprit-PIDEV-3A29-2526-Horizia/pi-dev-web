<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Voyage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_dashboard_')]
class DashboardController extends AbstractController
{
    /**
     * Vérifie que l'utilisateur a les droits d'admin
     */
    private function checkAdminAccess(): void
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Accès réservé aux administrateurs.');
        }
    }

    /**
     * Tableau de bord principal
     */
    #[Route('/dashboard', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        // Statistiques générales
        $nbVoyages = $entityManager->getRepository(Voyage::class)->count([]);
        $nbReservations = $entityManager->getRepository(Reservation::class)->count([]);
        $nbUsers = $entityManager->getRepository(User::class)->count([]);
        $nbCategories = $entityManager->getRepository(Categorie::class)->count([]);

        // Statistiques des réservations par statut
        $nbReservationsConfirmees = $entityManager->getRepository(Reservation::class)->count(['statut' => 'CONFIRMEE']);
        $nbReservationsEnAttente = $entityManager->getRepository(Reservation::class)->count(['statut' => 'EN_ATTENTE']);
        $nbReservationsAnnulees = $entityManager->getRepository(Reservation::class)->count(['statut' => 'ANNULEE']);

        // Dernières réservations
        $recentReservations = $entityManager->getRepository(Reservation::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.voyage', 'v')
            ->addSelect('v')
            ->leftJoin('r.user', 'u')
            ->addSelect('u')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Derniers voyages
        $recentVoyages = $entityManager->getRepository(Voyage::class)
            ->createQueryBuilder('v')
            ->leftJoin('v.categorie', 'c')
            ->addSelect('c')
            ->orderBy('v.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Voyages avec places faibles (<= 10)
        $voyagesPlacesFaibles = $entityManager->getRepository(Voyage::class)
            ->createQueryBuilder('v')
            ->where('v.placesRestantes <= 10')
            ->andWhere('v.placesRestantes > 0')
            ->orderBy('v.placesRestantes', 'ASC')
            ->getQuery()
            ->getResult();

        $nbVoyagesPlacesFaibles = count($voyagesPlacesFaibles);

        // Derniers utilisateurs inscrits
        $recentUsers = $entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Données pour les graphiques
        $chartLabels = ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'];
        $chartData = [$nbVoyages, $nbReservations, $nbUsers, $nbCategories];

        // Données pour le graphique des réservations par statut
        $statusChartLabels = ['Confirmées', 'En attente', 'Annulées'];
        $statusChartData = [$nbReservationsConfirmees, $nbReservationsEnAttente, $nbReservationsAnnulees];
        $statusChartColors = ['#28a745', '#ffc107', '#dc3545'];

        // Données pour le graphique linéaire (exemple - à adapter selon vos besoins réels)
        $lineChartLabels = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
        
        // Calcul des réservations par mois (exemple - à adapter)
        $lineChartData = [];
        foreach ($lineChartLabels as $index => $month) {
            $monthNumber = $index + 1;
            $count = $entityManager->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('MONTH(r.dateReservation) = :month')
                ->setParameter('month', $monthNumber)
                ->getQuery()
                ->getSingleScalarResult();
            $lineChartData[] = (int) $count;
        }

        // Voyages les plus réservés
        $topVoyages = $entityManager->createQueryBuilder()
            ->select('v.titre, COUNT(r.id) as nbReservations')
            ->from(Voyage::class, 'v')
            ->leftJoin('v.reservations', 'r')
            ->groupBy('v.id')
            ->orderBy('nbReservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard/index.html.twig', [
            // Statistiques générales
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            
            // Statistiques des réservations
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            
            // Alertes
            'nbVoyagesPlacesFaibles' => $nbVoyagesPlacesFaibles,
            'voyagesPlacesFaibles' => $voyagesPlacesFaibles,
            
            // Listes récentes
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,
            'recentUsers' => $recentUsers,
            
            // Données pour les graphiques
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'statusChartLabels' => $statusChartLabels,
            'statusChartData' => $statusChartData,
            'statusChartColors' => $statusChartColors,
            'lineChartLabels' => $lineChartLabels,
            'lineChartData' => $lineChartData,
            
            // Top voyages
            'topVoyages' => $topVoyages,
        ]);
    }

    /**
     * Graphique des réservations (API pour AJAX)
     */
    #[Route('/dashboard/chart-data', name: 'chart_data', methods: ['GET'])]
    public function chartData(EntityManagerInterface $entityManager): Response
    {
        $this->checkAdminAccess();

        // Réservations par mois
        $months = [];
        $reservationsCount = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
            $count = $entityManager->getRepository(Reservation::class)
                ->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('MONTH(r.dateReservation) = :month')
                ->setParameter('month', $i)
                ->getQuery()
                ->getSingleScalarResult();
            $reservationsCount[] = (int) $count;
        }

        return $this->json([
            'labels' => $months,
            'data' => $reservationsCount,
        ]);
    }
}