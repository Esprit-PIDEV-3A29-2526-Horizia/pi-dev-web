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

        $nbVoyagesPlacesFaibles = $entityManager->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(Voyage::class, 'v')
            ->where('v.placesRestantes <= :seuil')
            ->setParameter('seuil', 10)
            ->getQuery()
            ->getSingleScalarResult();

        $recentReservations = $entityManager->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
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

        return $this->render('admin/dashboard.html.twig', [
            'nbVoyages' => $nbVoyages,
            'nbReservations' => $nbReservations,
            'nbUsers' => $nbUsers,
            'nbCategories' => $nbCategories,
            'nbReservationsConfirmees' => $nbReservationsConfirmees,
            'nbReservationsEnAttente' => $nbReservationsEnAttente,
            'nbReservationsAnnulees' => $nbReservationsAnnulees,
            'nbVoyagesPlacesFaibles' => $nbVoyagesPlacesFaibles,
            'recentReservations' => $recentReservations,
            'recentVoyages' => $recentVoyages,

            'chartLabels' => ['Voyages', 'Réservations', 'Utilisateurs', 'Catégories'],
            'chartData' => [$nbVoyages, $nbReservations, $nbUsers, $nbCategories],

            'lineChartLabels' => ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
            'lineChartData' => array_values($monthlyReservations),
        ]);
    }
}